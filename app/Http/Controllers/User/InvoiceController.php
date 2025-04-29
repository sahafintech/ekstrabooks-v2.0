<?php

namespace App\Http\Controllers\User;

use App\Exports\CreditInvoiceExport;
use App\Http\Controllers\Controller;
use App\Imports\CreditInvoiceImport;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\ReceivePayment;
use App\Models\Tax;
use App\Models\Transaction;
use App\Notifications\SendInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            $route_name = request()->route()->getName();
            if ($route_name == 'invoices.store' || $route_name == 'invoices.duplicate') {
                if (has_limit('invoices', 'invoice_limit', false) <= 0) {
                    return back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Invoice::with('customer')
            ->where('is_deffered', 0);

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
                    $query->where('invoice_date', '>=', $filters['date_range']['start']);
                }
                if (!empty($filters['date_range']['end'])) {
                    $query->where('invoice_date', '<=', $filters['date_range']['end']);
                }
            }
        }

        // Handle sorting
        $sortField = $request->get('sort_field', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        $invoices = $query->paginate($perPage);

        return Inertia::render('Backend/User/Invoice/List', [
            'invoices' => $invoices->items(),
            'meta' => [
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem(),
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
        $invoice_title = get_business_option('invoice_title', 'Invoice');
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();

        return Inertia::render('Backend/User/Invoice/Create', [
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'invoice_title' => $invoice_title,
            'base_currency' => get_business_option('currency'),
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
            'customer_id'    => 'required',
            'title'          => 'required',
            'invoice_date'   => 'required|date',
            'due_date'       => 'required|after_or_equal:invoice_date',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoices.create')
                ->withErrors($validator)
                ->withInput();
        }

        // if quantity is less than 1 or null then return with error
        if (in_array(null, $request->quantity) || in_array('', $request->quantity) || in_array(0, $request->quantity)) {
            return redirect()->back()->withInput()->with('error', _lang('Quantity is required'));
        }

        // if unit cost is less than 0 or null then return with error
        if (in_array(null, $request->unit_cost) || in_array('', $request->unit_cost)) {
            return redirect()->back()->withInput()->with('error', _lang('Unit Cost is required'));
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

        $month = Carbon::parse($request->invoice_date)->format('F');
        $year = Carbon::parse($request->invoice_date)->format('Y');
        $today = now()->format('d');

        // financial year
        $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
        $end_month = explode(',', $financial_year)[1];
        $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
        $end_day = $start_day + 5;

        // if login as this user dont check the financial year
        if (false) {
            if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
            }
        }

        // if attachments then upload
        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $invoice                  = new Invoice();
        $invoice->customer_id     = $request->input('customer_id');
        $invoice->title           = $request->input('title');
        $invoice->invoice_number  = get_business_option('invoice_number');
        $invoice->order_number    = $request->input('order_number');
        $invoice->invoice_date    = Carbon::parse($request->input('invoice_date'))->format('Y-m-d');
        $invoice->due_date        = Carbon::parse($request->input('due_date'))->format('Y-m-d');
        $invoice->sub_total       = $summary['subTotal'];
        $invoice->grand_total     = $summary['grandTotal'];
        $invoice->currency        = $request['currency'];
        $invoice->converted_total = $request->input('converted_total');
        $invoice->exchange_rate   = Currency::where('name', $request->currency)->first()->exchange_rate;
        $invoice->paid            = 0;
        $invoice->discount        = $summary['discountAmount'];
        $invoice->discount_type   = $request->input('discount_type');
        $invoice->discount_value  = $request->input('discount_value') ?? 0;
        $invoice->template_type   = is_numeric($request->template) ? 1 : 0;
        $invoice->template        = 'default';
        $invoice->note            = $request->input('note');
        $invoice->footer          = $request->input('footer');
        $invoice->short_code      = rand(100000, 9999999) . uniqid();
        $invoice->attachments     = $attachment;

        $invoice->save();

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total / $invoice->exchange_rate));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total / $invoice->exchange_rate);
                $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->customer_id = $invoice->customer_id;

                $transaction->save();
            }

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type    = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->ref_id      = $invoice->id;
                $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
                $transaction->save();
            }

            if (isset($request->taxes[$invoiceItem->product_id])) {
                foreach ($request->taxes[$invoiceItem->product_id] as $taxId) {
                    $tax = Tax::find($taxId);

                    $invoiceItem->taxes()->save(new InvoiceItemTax([
                        'invoice_id' => $invoice->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $invoice->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($invoiceItem->sub_total / $invoice->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($invoiceItem->sub_total / $invoice->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Credit Invoice Tax') . ' #' . $invoice->invoice_number;
                    $transaction->ref_id      = $invoice->id;
                    $transaction->ref_type    = 'invoice tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $invoiceItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                //Check Available Stock Quantity
                if ($product->stock < $request->quantity[$i]) {
                    DB::rollBack();
                    return back()->with('error', $product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $request->quantity[$i];
                $product->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'invoice_number')->increment('value');

        DB::commit();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = get_account('Accounts Receivable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->transaction_currency    = $request->currency;
        $transaction->currency_rate           = $invoice->exchange_rate;
        $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->ref_id      = $invoice->id;
        $transaction->ref_type    = 'invoice';
        $transaction->customer_id = $invoice->customer_id;
        $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
        $transaction->save();

        if ($request->input('discount_value') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Sales Discount Allowed')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
            $transaction->description = _lang('Credit Invoice Discount') . ' #' . $invoice->invoice_number;
            $transaction->ref_id      = $invoice->id;
            $transaction->ref_type    = 'invoice';
            $transaction->customer_id = $invoice->customer_id;
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Invoice ' . $invoice->invoice_number;
        $audit->save();

        if ($invoice->id > 0) {
            return redirect()->route('invoices.show', $invoice->id)->with('success', _lang('Saved Successfully'));
        } else {
            return back()->with('error', _lang('Something going wrong, Please try again'));
        }
    }

    /**
     * Preview Private Invoice
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $invoice = Invoice::with([
            'business',
            'items',
            'customer',
            'taxes'
        ])->find($id);

        $attachments = Attachment::where('ref_id', $id)
            ->where('ref_type', 'invoice')
            ->get();

        $decimalPlaces = get_business_option('decimal_places', 2);

        return Inertia::render('Backend/User/Invoice/View', [
            'invoice' => $invoice,
            'attachments' => $attachments,
            'decimalPlaces' => $decimalPlaces
        ]);
    }

    public function get_invoice_link(Request $request, $id)
    {
        $invoice = Invoice::find($id);
        if ($request->ajax()) {
            return view('backend.user.invoice.modal.share-link', compact('invoice', 'id'));
        }
        return back();
    }

    public function send_email(Request $request, $id)
    {
        if ($request->isMethod('get')) {
            $email_templates = EmailTemplate::whereIn('slug', ['NEW_INVOICE_CREATED', 'INVOICE_PAYMENT_REMINDER'])
                ->where('email_status', 1)->get();
            $invoice = Invoice::find($id);
            return view('backend.user.invoice.modal.send_email', compact('invoice', 'id', 'email_templates'));
        } else {
            $validator = Validator::make($request->all(), [
                'email'   => 'required',
                'subject' => 'required',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->errors()->all());
            }

            $customMessage = [
                'subject' => $request->subject,
                'message' => $request->message,
            ];

            $invoice         = Invoice::find($id);
            $customer        = $invoice->customer;
            $customer->email = $request->email;

            try {
                Notification::send($customer, new SendInvoice($invoice, $customMessage, $request->template));
                $invoice->email_send    = 1;
                $invoice->email_send_at = now();
                $invoice->save();
                return redirect()->back()->with('success', _lang('Email has been sent'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Sent Invoice ' . $invoice->invoice_number . ' to ' . $customer->email;
            $audit->save();
        }
    }

    /**
     * Preview Public Invoice
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show_public_invoice($short_code, $export = 'preview')
    {
        $invoice   = Invoice::withoutGlobalScopes()->with(['customer', 'business', 'items', 'taxes'])
            ->where('short_code', $short_code)
            ->first();

        $request = request();
        // add activeBusiness object to request
        $request->merge(['activeBusiness' => $invoice->business]);

        if ($export == 'pdf') {
            $pdf = Pdf::loadView('backend.user.invoice.pdf', compact('invoice'));
            return $pdf->download('invoice#-' . $invoice->invoice_number . '.pdf');
        }

        return view('backend.guest.invoice.view', compact('invoice'));
    }

    public function export_pdf(Request $request, $id)
    {
        $invoice = Invoice::with(['business', 'items'])->find($id);
        $pdf     = Pdf::loadView('backend.user.invoice.pdf', compact('invoice', 'id'));

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Exported Invoice ' . $invoice->invoice_number . ' to PDF';
        $audit->save();

        return $pdf->download('invoice#-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $invoice = Invoice::with(['items', 'taxes', 'customer'])
            ->where('id', $id)
            ->where('status', '!=', 2)
            ->where('is_recurring', 0)
            ->where('is_deffered', 0)
            ->first();

        if ($invoice == null) {
            return back()->with('error', _lang('This invoice is already paid'));
        }

        // Get required data for the edit form
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();

        return Inertia::render('Backend/User/Invoice/Edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
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
            'customer_id'    => 'required',
            'title'          => 'required',
            'invoice_date'   => 'required|date',
            'due_date'       => 'required|date|after_or_equal:invoice_date',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoices.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        // if quantity is less than 1 or null then return with error
        if (in_array(null, $request->quantity) || in_array('', $request->quantity) || in_array(0, $request->quantity)) {
            return redirect()->back()->withInput()->with('error', _lang('Quantity is required'));
        }

        // if unit cost is less than 0 or null then return with error
        if (in_array(null, $request->unit_cost) || in_array('', $request->unit_cost)) {
            return redirect()->back()->withInput()->with('error', _lang('Unit Cost is required'));
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

        $month = Carbon::parse($request->invoice_date)->format('F');
        $year = Carbon::parse($request->invoice_date)->format('Y');
        $today = now()->format('d');

        // financial year
        $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
        $end_month = explode(',', $financial_year)[1];
        $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
        $end_day = $start_day + 5;

        // if login as this user dont check the financial year
        if (false) {
            if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
            }
        }

        // delete old attachments
        $invoice = Invoice::where('id', $id)
            ->first();

        if ($invoice->attachments != null && ($request->hasfile('attachment') != $invoice->attachments)) {
            $attachment = $invoice->attachments;
            if (file_exists($attachment)) {
                unlink($attachment);
            }
        }

        // if attachments then upload		
        $attachment = '';
        if ($request->hasfile('attachment') && $request->file('attachment')->isValid()) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $invoice = Invoice::where('id', $id)
            ->where('status', '!=', 2)
            ->where('is_recurring', 0)
            ->first();
        $invoice->customer_id     = $request->input('customer_id');
        $invoice->title           = $request->input('title');
        $invoice->order_number    = $request->input('order_number');
        $invoice->invoice_date    = Carbon::parse($request->input('invoice_date'))->format('Y-m-d');
        $invoice->due_date        = Carbon::parse($request->input('due_date'))->format('Y-m-d');
        $invoice->sub_total       = $summary['subTotal'];
        $invoice->grand_total     = $summary['grandTotal'];
        $invoice->currency        = $request['currency'];
        $invoice->converted_total = $request->input('converted_total');
        $invoice->exchange_rate   = $request->input('exchange_rate');
        $invoice->discount        = $summary['discountAmount'];
        $invoice->discount_type   = $request->input('discount_type');
        $invoice->discount_value  = $request->input('discount_value') ?? 0;
        $invoice->template_type   = $invoice->template_type   = is_numeric($request->template) ? 1 : 0;
        $invoice->template        = 'default';
        $invoice->note            = $request->input('note');
        $invoice->footer          = $request->input('footer');
        if ($attachment != '') {
            $invoice->attachments     = $attachment;
        }

        $invoice->save();

        //Update Invoice item
        foreach ($invoice->items as $invoice_item) {
            $product = $invoice_item->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $invoice_item->quantity;
                $product->save();
            }
            $invoice_item->delete();

            $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice')
                ->where('account_id', $product->income_account_id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }

            if ($product->stock_management == 1) {
                $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice')
                    ->where('account_id', get_account('Inventory')->id)
                    ->first();

                if ($transaction != null) {
                    $transaction->delete();
                }
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice')
                    ->where('account_id', $product->expense_account_id)
                    ->first();

                if ($transaction != null) {
                    $transaction->delete();
                }
            }
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total / $invoice->exchange_rate));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total / $invoice->exchange_rate);
                $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->customer_id = $invoice->customer_id;

                $transaction->save();
            }

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type    = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->ref_id      = $invoice->id;
                $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
                $transaction->save();
            }

            if (isset($request->taxes[$invoiceItem->product_id])) {
                $invoiceItem->taxes()->delete();
                $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice tax')
                    ->get();
                foreach ($transaction as $t) {
                    $t->delete();
                }

                foreach ($request->taxes[$invoiceItem->product_id] as $taxId) {
                    $tax = Tax::find($taxId);

                    $invoiceItem->taxes()->save(new InvoiceItemTax([
                        'invoice_id' => $invoice->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $invoice->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($invoiceItem->sub_total / $invoice->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($invoiceItem->sub_total / $invoice->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Credit Invoice Tax') . ' #' . $invoice->invoice_number;
                    $transaction->ref_id      = $invoice->id;
                    $transaction->ref_type    = 'invoice tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $invoiceItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                //Check Available Stock Quantity
                if ($product->stock < $request->quantity[$i]) {
                    DB::rollBack();
                    return back()->with('error', $product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $request->quantity[$i];
                $product->save();
            }
        }

        DB::commit();

        $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice')
            ->where('account_id', get_account('Accounts Receivable')->id)
            ->first();
        $transaction->trans_date = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->save();

        if ($request->input('discount_value') == 0) {
            $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();
            if ($transaction != null) {
                $transaction->delete();
            }
        }

        if ($request->input('discount_value') > 0) {
            $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();
            if ($transaction == null) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Sales Discount Allowed')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Credit Invoice Discount') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            } else {
                $transaction->trans_date = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Invoice ' . $invoice->invoice_number;
        $audit->save();

        return redirect()->route('invoices.show', $invoice->id)->with('success', _lang('Updated Successfully'));
    }

    /** Duplicate Invoice */
    public function duplicate($id)
    {
        DB::beginTransaction();
        $invoice                    = Invoice::find($id);
        $newInvoice                 = $invoice->replicate();
        $newInvoice->status         = 0;
        $newInvoice->paid           = 0;
        $newInvoice->invoice_number = get_business_option('invoice_number', rand());
        $newInvoice->short_code     = rand(100000, 9999999) . uniqid();
        $newInvoice->save();

        foreach ($invoice->items as $invoiceItem) {
            $newInvoiceItem             = $invoiceItem->replicate();
            $newInvoiceItem->invoice_id = $newInvoice->id;
            $newInvoiceItem->save();

            foreach ($invoiceItem->taxes as $InvoiceItemTax) {
                $newInvoiceItemTax                  = $InvoiceItemTax->replicate();
                $newInvoiceItemTax->invoice_id      = $newInvoice->id;
                $newInvoiceItemTax->invoice_item_id = $newInvoiceItem->id;
                $newInvoiceItemTax->save();
            }

            //Update Stock
            $product = $invoiceItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                //Check Available Stock Quantity
                if ($product->stock < $newInvoiceItem->quantity) {
                    DB::rollBack();
                    return back()->with('error', $product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $newInvoiceItem->quantity;
                $product->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'invoice_number')->increment('value');

        DB::commit();

        return redirect()->route('invoices.edit', $newInvoice->id);
    }

    public function get_invoices(Request $request)
    {
        return Customer::where('id', $request->id)
            ->with(['invoices' => function ($query) {
                $query->where('status', '!=', 2);
            }])
            ->first();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $invoice = Invoice::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Invoice ' . $invoice->invoice_number;
        $audit->save();

        // increase product stock
        foreach ($invoice->items as $invoiceItem) {
            $product = $invoiceItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $invoiceItem->quantity;
                $product->save();
            }
        }
        // delete transactions
        Transaction::where('ref_id', $invoice->id)
            ->where(function ($query) {
                $query->where('ref_type', 'invoice')
                    ->orWhere('ref_type', 'invoice tax');
            })
            ->delete();

        $invoice_payments = InvoicePayment::where('invoice_id', $invoice->id)->get();

        foreach ($invoice_payments as $invoice_payment) {
            $receive_payment = ReceivePayment::find($invoice_payment->payment_id);
            if ($receive_payment) {
                $receive_payment->amount = $receive_payment->amount - $invoice_payment->amount;
                $receive_payment->save();

                if ($receive_payment->amount == 0) {
                    $receive_payment->delete();
                }

                // delete transactions
                $transactions = Transaction::where('ref_id', $invoice->id . ',' . $receive_payment->id)->where('ref_type', 'invoice payment')->get();
                foreach ($transactions as $transaction) {
                    $transaction->delete();
                }
            }
            $invoice_payment->delete();

            if ($receive_payment->invoices == null) {
                $receive_payment->delete();
            }
        }

        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', _lang('Deleted Successfully'));
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
            if (isset($request->taxes[$request->product_id[$i]])) {
                for ($j = 0; $j < count($request->taxes[$request->product_id[$i]]); $j++) {
                    $taxId       = $request->taxes[$request->product_id[$i]][$j];
                    $tax         = Tax::find($taxId);
                    $product_tax = ($line_total / 100) * $tax->rate;
                    $taxAmount += $product_tax;
                }
            }

            //Calculate Discount
            if ($request->discount_type == '0') {
                $discountAmount = ($subTotal / 100) * $request->discount_value ?? 0;
            } else if ($request->discount_type == '1') {
                $discountAmount = $request->discount_value ?? 0;
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

    public function import_invoices(Request $request)
    {
        $request->validate([
            'invoices_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new CreditInvoiceImport, $request->file('invoices_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Imported Invoices';
        $audit->save();

        return redirect()->route('invoices.index')->with('success', _lang('Invoices Imported'));
    }

    public function invoices_filter(Request $request)
    {
        $from =  explode('to', $request->date_range)[0] ?? '';
        $to = explode('to', $request->date_range)[1] ?? '';

        $query = Invoice::select('invoices.*')
            ->with('customer')
            ->where('is_recurring', 0)
            ->where('is_deffered', 0);

        if ($request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->status != '') {
            $query->where('status', $request->status);
        }

        if ($from != '' && $to != '') {
            $query->whereDate('invoice_date', '>=', Carbon::parse($from)->format('Y-m-d'))
                ->whereDate('invoice_date', '<=', Carbon::parse($to)->format('Y-m-d'));
        }

        $invoices = $query->get();

        $status = $request->status;
        $customer_id = $request->customer_id;
        $date_range = $request->date_range;

        return view('backend.user.invoice.list', compact('invoices', 'status', 'customer_id', 'date_range'));
    }

    public function invoices_all(Request $request)
    {
        if ($request->invoices == null) {
            return redirect()->route('invoices.index')->with('error', _lang('Please Select invoice'));
        }

        $invoices = Invoice::whereIn('id', $request->invoices)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Invoices ' . implode(',', $invoices->pluck('invoice_number')->toArray());
        $audit->save();

        foreach ($invoices as $invoice) {

            // increase product stock
            foreach ($invoice->items as $invoiceItem) {
                $product = $invoiceItem->product;
                if ($product->type == 'product' && $product->stock_management == 1) {
                    $product->stock = $product->stock + $invoiceItem->quantity;
                    $product->save();
                }
            }

            // delete transactions
            $transactions = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'invoice')->get();

            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $invoice_payments = InvoicePayment::where('invoice_id', $invoice->id)->get();

            foreach ($invoice_payments as $invoice_payment) {
                $receive_payment = ReceivePayment::find($invoice_payment->payment_id);
                if ($receive_payment) {
                    $receive_payment->amount = $receive_payment->amount - $invoice_payment->amount;
                    $receive_payment->save();

                    if ($receive_payment->amount == 0) {
                        $receive_payment->delete();
                    }

                    // delete transactions
                    $transactions = Transaction::where('ref_id', $invoice->id . ',' . $receive_payment->id)->where('ref_type', 'invoice payment')->get();
                    foreach ($transactions as $transaction) {
                        $transaction->delete();
                    }
                }
                $invoice_payment->delete();

                if ($receive_payment->invoices == null) {
                    $receive_payment->delete();
                }
            }

            $invoice->delete();
        }

        return redirect()->route('invoices.index')->with('success', _lang('Deleted Successfully'));
    }

    public function export_invoices()
    {
        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Exported Invoices';
        $audit->save();

        return Excel::download(new CreditInvoiceExport, 'invoices ' . now()->format('d m Y') . '.xlsx');
    }

    public function find_currency(Request $request)
    {
        $currency = Currency::where('status', 1)->where('name', $request->name)->first();
        return $currency;
    }

    public function find_taxes()
    {
        $taxes = Tax::all();
        return $taxes;
    }
}
