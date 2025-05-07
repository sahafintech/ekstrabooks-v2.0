<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesReturnItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SalesReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SalesReturn::with('customer');

        // Apply filters if any
        if ($request->has('filters')) {
            $filters = $request->filters;

            // Filter by customer
            if (!empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }

            // Filter by status
            if (isset($filters['status']) && $filters['status'] !== '') {
                $query->where('status', $filters['status']);
            }

            // Filter by date range
            if (!empty($filters['date_range'])) {
                if (!empty($filters['date_range']['start'])) {
                    $query->where('return_date', '>=', $filters['date_range']['start']);
                }
                if (!empty($filters['date_range']['end'])) {
                    $query->where('return_date', '<=', $filters['date_range']['end']);
                }
            }
        }

        if (!empty($request->search)) {
            $query->where('return_number', 'like', "%{$request->search}%")
                ->whereHas('customer', function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%");
                });
        }

        // Handle sorting
        $sortField = $request->get('sort_field', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        $returns = $query->paginate($perPage);
        $accounts = Account::all();

        return Inertia::render('Backend/User/SalesReturn/List', [
            'returns' => $returns->items(),
            'accounts' => $accounts,
            'meta' => [
                'total' => $returns->total(),
                'per_page' => $returns->perPage(),
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'from' => $returns->firstItem(),
                'to' => $returns->lastItem(),
            ],
            'filters' => $request->filters ?? []
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sales_return_title = get_business_option('sales_return_title', 'Return');
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $accounts = Account::all();

        return Inertia::render('Backend/User/SalesReturn/Create', [
            'sales_return_title' => $sales_return_title,
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'base_currency' => get_business_option('currency')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'    => $request->type == 'credit' ? 'required' : 'nullable',
            'title'          => 'required',
            'type'           => 'required',
            'return_date'   => 'required|date',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('sales_returns.create')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $return                  = new SalesReturn();
        $return->customer_id     = $request->input('customer_id') ?? NULL;
        $return->title           = $request->input('title');
        $return->return_number   = get_business_option('sales_return_number', 'SR');
        $return->return_date     = Carbon::parse($request->input('return_date'))->format('Y-m-d');
        $return->sub_total       = $summary['subTotal'];
        $return->grand_total     = $summary['grandTotal'];
        $return->currency        = $request['currency'];
        $return->converted_total = $request->input('converted_total');
        $return->type            = $request->input('type');
        $return->exchange_rate   = $request->input('exchange_rate');
        $return->paid            = 0;
        $return->discount        = $summary['discountAmount'];
        $return->discount_type   = $request->input('discount_type');
        $return->discount_value  = $request->input('discount_value');
        $return->note            = $request->input('note');
        $return->footer          = $request->input('footer');
        $return->short_code      = rand(100000, 9999999) . uniqid();

        $return->save();

        $default_accounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_code = '1100';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_code = '2200';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_code = '4009';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_type = 'Other Current Asset';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_type = 'Other Income';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Receivable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $returnItem = $return->items()->save(new SalesReturnItem([
                'sales_return_id'   => $return->id,
                'product_id'        => $request->product_id[$i],
                'product_name'      => $request->product_name[$i],
                'description'       => $request->description[$i],
                'quantity'          => $request->quantity[$i],
                'unit_cost'         => $request->unit_cost[$i],
                'sub_total'         => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $returnItem->sub_total);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $returnItem->sub_total));
                $transaction->description = _lang('Sales Return') . ' #' . $return->return_number;
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 's return';
                $transaction->save();
            }

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity);
                $transaction->description = $returnItem->product_name . 'Sales Returned #' . $returnItem->quantity;
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity));
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 's return';
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity));
                $transaction->ref_type    = 's return';
                $transaction->ref_id      = $return->id;
                $transaction->description = 'Sales Return #' . $return->return_number;
                $transaction->save();
            }

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $returnItem->taxes()->save(new SalesReturnItemTax([
                        'sales_return_id' => $return->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($returnItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $return->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate);
                    $transaction->description = _lang('Sales Return Tax') . ' #' . $return->return_number;
                    $transaction->ref_id      = $return->id;
                    $transaction->ref_type    = 's return tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $returnItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $request->quantity[$i];
                $product->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'sales_return_number')->increment('value');

        DB::commit();

        if ($request->type == 'credit') {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Accounts Receivable')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $return->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
            $transaction->ref_id      = $return->id;
            $transaction->ref_type    = 's return';
            $transaction->description = 'Sales Return #' . $return->return_number;
            $transaction->save();
        }

        if ($request->type == 'cash') {
            $transaction              = new Transaction();
            $transaction->customer_id  = $request->input('customer_id') ?? NULL;
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->input('account_id');
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->transaction_method = $request->input('method');
            $transaction->currency_rate           = $return->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
            $transaction->description = 'Sales Return #' . $return->return_number;
            $transaction->ref_id      = $return->id;
            $transaction->ref_type    = 's return';
            $transaction->save();

            $return->paid = $summary['grandTotal'];
            $return->status = 1; //Paid
            $return->save();
        }


        if ($request->input('discount_value') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Sales Discount Allowed')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $return->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
            $transaction->description = _lang('Sales Return Discount') . ' #' . $return->return_number;
            $transaction->ref_id      = $return->id;
            $transaction->ref_type    = 's return';
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Sales Return Created' . ' ' . $return->return_number;
        $audit->save();

        return redirect()->route('sales_returns.show', $return->id)->with('success', _lang('Saved Successfully'));
    }

    /**
     * Preview Private Invoice
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $sales_return = SalesReturn::with([
            'business',
            'items',
            'customer',
            'taxes'
        ])->find($id);

        $attachments = Attachment::where('ref_id', $id)
            ->where('ref_type', 'sales_return')
            ->get();

        return Inertia::render('Backend/User/SalesReturn/View', [
            'sales_return' => $sales_return,
            'attachments' => $attachments,
        ]);
    }

    public function refund_store(Request $request, $id)
    {
        $request->validate(
            [
                'refund_date' => 'required',
                'amount'      => 'required|numeric',
                'account_id'  => 'required',
            ],
            [
                'account_id.required' => _lang('The account field is required.'),
                'amount.required'      => _lang('The amount field is required.'),
                'amount.numeric'       => _lang('The amount must be a number.'),
                'refund_date.required' => _lang('The refund date field is required.'),
            ]
        );

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $salesReturn = SalesReturn::with('customer')->where('id', $id)->first();

        if ($request->amount > $salesReturn->grand_total - $salesReturn->paid) {
            return back()->with('error', _lang('Refund amount can not be greater than due amount'));
        }

        $default_accounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_code = '1100';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_code = '2200';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_code = '4009';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_type = 'Other Current Asset';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_type = 'Other Income';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Receivable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $currentTime = Carbon::now();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = $request->account_id;
        $transaction->transaction_method      = $request->method;
        $transaction->dr_cr       = 'cr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $salesReturn->currency, $request->amount);
        $transaction->transaction_currency    = $salesReturn->currency;
        $transaction->currency_rate = $salesReturn->exchange_rate;
        $transaction->base_currency_amount = convert_currency($salesReturn->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $salesReturn->currency, $request->amount));
        $transaction->reference   = $request->reference;
        $transaction->description = _lang('Sales Return Refund') . ' #' . $salesReturn->return_number;
        $transaction->attachment  = $attachment;
        $transaction->ref_id      = $id;
        $transaction->ref_type    = 's refund';
        $transaction->customer_id = $request->customer_id ?? NULL;
        $transaction->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->input('trans_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = get_account('Accounts Receivable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $salesReturn->currency, $request->amount);
        $transaction->transaction_currency    = $salesReturn->currency;
        $transaction->currency_rate = $salesReturn->exchange_rate;
        $transaction->base_currency_amount = convert_currency($salesReturn->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $salesReturn->currency, $request->amount));
        $transaction->ref_id      = $id;
        $transaction->ref_type    = 's refund';
        $transaction->description = 'Sales Return Refund #' . $salesReturn->return_number;
        $transaction->save();

        $salesReturn->paid   = $salesReturn->paid + $request->amount;
        $salesReturn->status = 2; //Partially refund
        if ($salesReturn->paid >= $salesReturn->grand_total) {
            $salesReturn->status = 1; //Paid
        }
        $salesReturn->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Sales Return Refund' . ' ' . $salesReturn->return_number;
        $audit->save();

        return redirect()->route('sales_returns.index')->with('success', _lang('Saved Successfully'));
    }

    public function send_email(Request $request, $id)
    {
        if (!$request->ajax()) {
            return back();
        }
        if ($request->isMethod('get')) {
            $email_templates = EmailTemplate::whereIn('slug', ['NEW_INVOICE_CREATED', 'INVOICE_PAYMENT_REMINDER'])
                ->where('email_status', 1)->get();
            $sales_return = SalesReturn::find($id);
            return view('backend.user.sales_return.modal.send_email', compact('sales_return', 'id', 'email_templates'));
        } else {
            $validator = Validator::make($request->all(), [
                'email'   => 'required',
                'subject' => 'required',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            }

            $customMessage = [
                'subject' => $request->subject,
                'message' => $request->message,
            ];

            $sales_return         = SalesReturn::find($id);
            $customer        = $sales_return->customer;
            $customer->email = $request->email;

            try {
                Notification::send($customer, new SendInvoice($sales_return, $customMessage, $request->template));
                $sales_return->email_send    = 1;
                $sales_return->email_send_at = now();
                $sales_return->save();
                return response()->json(['result' => 'success', 'message' => _lang('Email has been sent')]);
            } catch (\Exception $e) {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    /**
     * Preview Public Invoice
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function export_pdf(Request $request, $id)
    {
        $sales_return = SalesReturn::with(['business', 'items'])->find($id);
        $pdf     = Pdf::loadView('backend.user.sales_return.pdf', compact('sales_return', 'id'));

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Sales Return PDF Exported' . ' ' . $sales_return->return_number;
        $audit->save();

        return $pdf->download('sales return#-' . $sales_return->return_number . '.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $sales_return = SalesReturn::with('items')
            ->where('id', $id)
            ->where('status', '!=', 1)
            ->first();

        if ($sales_return == null) {
            return back()->with('error', _lang('This sales return is already paid'));
        }

        // Get required data for the edit form
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $accounts = Account::all();

        $paymentTransaction = Transaction::where('ref_type', 's refund')
            ->where('ref_id', $id)
            ->first();

        $taxIds = $sales_return->taxes
            ->pluck('tax_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        return Inertia::render('Backend/User/SalesReturn/Edit', [
            'sales_return' => $sales_return,
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'paymentTransaction' => $paymentTransaction,
            'taxIds' => $taxIds
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'    => $request->type == 'credit' ? 'required' : 'nullable',
            'title'          => 'required',
            'type'           => 'required',
            'return_date'   => 'required|date',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('sales_returns.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $default_accounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_code = '1100';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_code = '2200';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_code = '4009';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_type = 'Other Current Asset';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_type = 'Other Income';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Receivable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $return                  = SalesReturn::find($id);
        $return->customer_id     = $request->input('customer_id') ?? NULL;
        $return->title           = $request->input('title');
        $return->return_date     = Carbon::parse($request->input('return_date'))->format('Y-m-d');
        $return->sub_total       = $summary['subTotal'];
        $return->grand_total     = $summary['grandTotal'];
        $return->currency        = $request['currency'];
        $return->converted_total = $request->input('converted_total');
        $return->type            = $request->input('type');
        $return->exchange_rate   = $request->input('exchange_rate');
        $return->paid            = 0;
        $return->discount        = $summary['discountAmount'];
        $return->discount_type   = $request->input('discount_type');
        $return->discount_value  = $request->input('discount_value');
        $return->note            = $request->input('note');
        $return->footer          = $request->input('footer');
        $return->save();

        $currentTime = Carbon::now();

        //Update return item
        foreach ($return->items as $return_item) {
            $product = $return_item->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock - $return_item->quantity;
                $product->save();
            }
            $return_item->delete();

            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return')
                ->where('account_id', $product->income_account_id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }

            if ($product->stock_management == 1) {
                $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return')
                    ->where('account_id', get_account('Inventory')->id)
                    ->first();

                if ($transaction != null) {
                    $transaction->delete();
                }
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return')
                    ->where('account_id', $product->expense_account_id)
                    ->first();

                if ($transaction != null) {
                    $transaction->delete();
                }
            }
        }

        for ($i = 0; $i < count($request->product_id); $i++) {
            $returnItem = $return->items()->save(new SalesReturnItem([
                'sales_return_id'   => $return->id,
                'product_id'        => $request->product_id[$i],
                'product_name'      => $request->product_name[$i],
                'description'       => $request->description[$i],
                'quantity'          => $request->quantity[$i],
                'unit_cost'         => $request->unit_cost[$i],
                'sub_total'         => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $returnItem->sub_total);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $returnItem->sub_total));
                $transaction->description = _lang('Sales Return') . ' #' . $return->return_number;
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 's return';
                $transaction->save();
            }

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity);
                $transaction->description = $returnItem->product_name . ' Sales Returned #' . $returnItem->quantity;
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity));
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 's return';
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity));
                $transaction->ref_type    = 's return';
                $transaction->ref_id      = $return->id;
                $transaction->description = 'Sales Return #' . $return->return_number;
                $transaction->save();
            }

            if (isset($request->taxes)) {
                $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return tax')
                    ->get();

                foreach ($transaction as $trans) {
                    $trans->delete();
                }

                $returnItem->taxes()->delete();

                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $returnItem->taxes()->save(new SalesReturnItemTax([
                        'sales_return_id' => $return->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($returnItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $return->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate);
                    $transaction->description = _lang('Sales Return Tax') . ' #' . $return->return_number;
                    $transaction->ref_id      = $return->id;
                    $transaction->ref_type    = 's return tax';
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $returnItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $request->quantity[$i];
                $product->save();
            }
        }

        DB::commit();

        if ($request->type == 'credit') {
            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return')
                ->where('account_id', get_account('Accounts Receivable')->id)
                ->first();
            $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
            $transaction->save();
        }

        if ($request->input('discount_value') == 0) {
            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();
            if ($transaction != null) {
                $transaction->delete();
            }
        }

        if ($request->input('discount_value') > 0) {
            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();
            if ($transaction == null) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Sales Discount Allowed')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Sales Return Discount') . ' #' . $return->return_number;
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 's return';
                $transaction->save();
            } else {
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->save();
            }
        }

        if ($request->type == 'cash') {
            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 's return')
                ->where('account_id', $request->input('account_id'))
                ->first();

            if ($transaction != null) {
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                $transaction->transaction_method = $request->input('method');
                $transaction->save();
            } else {
                $transaction              = new Transaction();
                $transaction->customer_id  = $request->input('customer_id') ?? NULL;
                $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->input('account_id');
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->transaction_method = $request->input('method');
                $transaction->currency_rate           = $return->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                $transaction->description = 'Sales Return #' . $return->return_number;
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 's return';
                $transaction->save();
            }

            $return->paid = $summary['grandTotal'];
            $return->status = 1; //Paid
            $return->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Sales Return Updated' . ' ' . $return->return_number;
        $audit->save();

        if ($return->id > 0) {
            return redirect()->route('sales_returns.show', $return->id)->with('success', _lang('Updated Successfully'));
        } else {
            return back()->with('error', _lang('Something going wrong, Please try again'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $return = SalesReturn::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Sales Return Deleted' . ' ' . $return->return_number;
        $audit->save();

        // descrease stock
        foreach ($return->items as $returnItem) {
            $product = $returnItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock - $returnItem->quantity;
                $product->save();
            }
        }
        // delete transactions
        $transactions = Transaction::where('ref_id', $return->id)
            ->where(function ($query) {
                $query->where('ref_type', 's return')
                    ->orWhere('ref_type', 's return tax');
            })
            ->get();
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        // delete refund transactions
        $transactions = Transaction::where('ref_id', $return->id)->where('ref_type', 's refund')->get();
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        $return->delete();
        return redirect()->route('sales_returns.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $return = SalesReturn::find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Sales Return Deleted' . ' ' . $return->return_number;
            $audit->save();

            // descrease stock
            foreach ($return->items as $returnItem) {
                $product = $returnItem->product;
                if ($product->type == 'product' && $product->stock_management == 1) {
                    $product->stock = $product->stock - $returnItem->quantity;
                    $product->save();
                }
            }
            // delete transactions
            $transactions = Transaction::where('ref_id', $return->id)
                ->where(function ($query) {
                    $query->where('ref_type', 's return')
                        ->orWhere('ref_type', 's return tax');
                })
                ->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            // delete refund transactions
            $transactions = Transaction::where('ref_id', $return->id)->where('ref_type', 's refund')->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $return->delete();
        }
        return redirect()->route('sales_returns.index')->with('success', _lang('Deleted Successfully'));
    }

    private function calculateTotal(Request $request)
    {
        $subTotal       = 0;
        $taxAmount      = 0;
        $discountAmount = 0;
        $grandTotal     = 0;

        for ($i = 0; $i < count($request->product_id); $i++) {
            //Calculate Sub Total
            $line_qnt       = $request->quantity[$i];
            $line_unit_cost = $request->unit_cost[$i];
            $line_total     = ($line_qnt * $line_unit_cost);

            //Show Sub Total
            $subTotal = ($subTotal + $line_total);

            //Calculate Taxes
            if (isset($request->taxes)) {
                for ($j = 0; $j < count($request->taxes); $j++) {
                    $taxId       = $request->taxes[$j];
                    $tax         = Tax::find($taxId);
                    $product_tax = ($line_total / 100) * $tax->rate;
                    $taxAmount += $product_tax;
                }
            }

            //Calculate Discount
            if ($request->discount_type == '0') {
                $discountAmount = ($subTotal / 100) * $request->discount_value;
            } else if ($request->discount_type == '1') {
                $discountAmount = $request->discount_value;
            }
        }

        //Calculate Grand Total
        $grandTotal = ($subTotal + $taxAmount) - $discountAmount;

        return array(
            'subTotal'       => $subTotal / $request->exchange_rate,
            'taxAmount'      => $taxAmount / $request->exchange_rate,
            'discountAmount' => $discountAmount / $request->exchange_rate,
            'grandTotal'     => $grandTotal / $request->exchange_rate,
        );
    }

    public function get_returns(Request $request)
    {
        $salesReturns = SalesReturn::with('customer')->where('customer_id', $request->id)->get();
        return $salesReturns;
    }
}
