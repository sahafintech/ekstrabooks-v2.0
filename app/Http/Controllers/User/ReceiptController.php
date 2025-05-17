<?php

namespace App\Http\Controllers\User;

use App\Exports\CashInvoiceExport;
use App\Http\Controllers\Controller;
use App\Imports\CashInvoiceImport;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\HoldPosInvoice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\Prescription;
use App\Models\PrescriptionProduct;
use App\Models\PrescriptionProductItem;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\ReceiptItemTax;
use App\Models\SubCategory;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReceiptController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware(function ($request, $next) {

    //         if (package()->pos != 1) {
    //             if (!$request->ajax()) {
    //                 return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
    //             } else {
    //                 return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
    //             }
    //         }

    //         return $next($request);
    //     });
    // }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 50);
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $customerId = $request->get('customer_id', '');
        $dateRange = $request->get('date_range', []);

        $query = Receipt::with('customer');

        if ($sortColumn === 'customer.name') {
            $query->join('customers', 'receipts.customer_id', '=', 'customers.id')
                ->orderBy('customers.name', $sortDirection)
                ->select('receipts.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%$search%")
                    ->orWhere('title', 'like', "%$search%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        // Filter by customer
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        // Filter by date range
        if (!empty($dateRange)) {
            $query->whereDate('receipt_date', '>=', $dateRange[0])
                ->whereDate('receipt_date', '<=', $dateRange[1]);
        }

        // Get summary statistics for all receipts matching filters
        $allReceipts = Receipt::query();
        
        if ($search) {
            $allReceipts->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%$search%")
                    ->orWhere('title', 'like', "%$search%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($customerId) {
            $allReceipts->where('customer_id', $customerId);
        }

        if (!empty($dateRange)) {
            $allReceipts->whereDate('receipt_date', '>=', $dateRange[0])
                ->whereDate('receipt_date', '<=', $dateRange[1]);
        }

        $allReceipts = $allReceipts->get();

        $summary = [
            'total_invoices' => $allReceipts->count(),
            'grand_total' => $allReceipts->sum('grand_total'),
            'unique_customers' => $allReceipts->pluck('customer_id')->unique()->count(),
            'today_invoices' => $allReceipts->filter(function ($receipt) {
                return Carbon::createFromFormat(get_date_format(), $receipt->receipt_date)->isToday();
            })->count(),
        ];

        $receipts = $query->paginate($perPage);

        return Inertia::render('Backend/User/CashInvoice/List', [
            'receipts' => $receipts->items(),
            'meta' => [
                'current_page' => $receipts->currentPage(),
                'per_page' => $receipts->perPage(),
                'from' => $receipts->firstItem(),
                'to' => $receipts->lastItem(),
                'total' => $receipts->total(),
                'last_page' => $receipts->lastPage(),
            ],
            'filters' => request()->only(['search', 'per_page', 'customer_id', 'date_range']),
            'customers' => Customer::select('id', 'name')->orderBy('id')->get(),
            'summary' => $summary,
        ]);
    }

    public function create()
    {
        $customers = \App\Models\Customer::all();
        $products = Product::all();
        $currencies = Currency::all();
        $taxes = Tax::all();
        $accounts = Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash');
        })->get();

        $receipt_title = get_business_option('receipt_title', 'Cash Invoice');
        $accounts = Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash');
        })->get();

        return Inertia::render('Backend/User/CashInvoice/Create', [
            'customers' => $customers,
            'products' => $products,
            'currencies' => $currencies,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'receipt_title' => $receipt_title,
            'accounts' => $accounts,
            'base_currency' => get_business_option('currency'),
        ]);
    }

    public function show($id)
    {
        $receipt = Receipt::with(['business', 'items', 'taxes', 'customer'])
            ->where('id', $id)
            ->first();

        $business = Business::find($receipt->business_id);

        return Inertia::render('Backend/User/CashInvoice/View', [
            'receipt' => $receipt,
            'business' => $business
        ]);
    }

    public function show_public_cash_invoice($short_code)
    {
        $receipt   = Receipt::withoutGlobalScopes()->with(['customer', 'business', 'items', 'taxes'])
            ->where('short_code', $short_code)
            ->first();

        $request = request();
        // add activeBusiness object to request
        $request->merge(['activeBusiness' => $receipt->business]);

        return Inertia::render('Backend/User/CashInvoice/PublicView', [
            'receipt' => $receipt,
        ]);
    }

    public function edit($id)
    {
        $receipt = Receipt::with(['items.taxes', 'customer'])
            ->where('id', $id)
            ->first();

        $transaction = Transaction::where('ref_id', $receipt->id)
            ->where('ref_type', 'receipt')
            ->whereHas('account', function ($query) {
                $query->where('account_type', 'Bank')
                    ->orWhere('account_type', 'Cash');
            })
            ->with('account')
            ->first();

        $customers = \App\Models\Customer::all();
        $products = Product::all();
        $currencies = Currency::all();
        $taxes = Tax::all();
        $accounts = Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash');
        })->get();
        $taxIds = $receipt->taxes
            ->pluck('tax_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        return Inertia::render('Backend/User/CashInvoice/Edit', [
            'receipt' => $receipt,
            'transaction' => $transaction,
            'customers' => $customers,
            'products' => $products,
            'currencies' => $currencies,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'taxIds' => $taxIds
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'    => 'nullable',
            'title'          => 'required',
            'receipt_date'   => 'required|date',
            'product_id'     => 'required',
            'currency'       => 'required',
            'account_id'     => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('receipts.index')->with('success')->with('success', 'Saved Successfully');
        }

        // if quantity is less than 1 or null then return with error
        if (in_array(null, $request->quantity) || in_array('', $request->quantity) || in_array(0, $request->quantity)) {
            return redirect()->route('receipts.create')->withInput()->with('error', _lang('Quantity is required'));
        }

        // if unit cost is less than 0 or null then return with error
        if (in_array(null, $request->unit_cost) || in_array('', $request->unit_cost)) {
            return redirect()->route('receipts.create')->withInput()->with('error', _lang('Unit Cost is required'));
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
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = auth()->id();
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $month = Carbon::parse($request->receipt_date)->format('F');
        $year = Carbon::parse($request->receipt_date)->format('Y');
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

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $receipt                  = new Receipt();
        $receipt->customer_id     = $request->input('customer_id');
        $receipt->title           = $request->input('title');
        $receipt->receipt_number  = get_business_option('receipt_number');
        $receipt->order_number    = $request->input('order_number');
        $receipt->receipt_date    = Carbon::parse($request->input('receipt_date'))->format('Y-m-d');
        $receipt->sub_total       = $summary['subTotal'];
        $receipt->grand_total     = $summary['grandTotal'];
        $receipt->currency        = $request['currency'];
        $receipt->converted_total = $request->input('converted_total');
        $receipt->exchange_rate   = $request->input('exchange_rate');
        $receipt->discount        = $summary['discountAmount'];
        $receipt->discount_type   = $request->input('discount_type');
        $receipt->discount_value  = $request->input('discount_value') ?? 0;
        $receipt->note            = $request->input('note');
        $receipt->footer          = $request->input('footer');
        $receipt->user_id         = auth()->id();
        $receipt->business_id     = request()->activeBusiness->id;
        $receipt->short_code      = rand(100000, 9999999) . uniqid();

        $receipt->save();

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $receiptItem = $receipt->items()->save(new ReceiptItem([
                'receipt_id'   => $receipt->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                'user_id'      => auth()->id(),
                'business_id'  => request()->activeBusiness->id,
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $receiptItem->sub_total / $receipt->exchange_rate);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $receipt->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $receiptItem->sub_total / $receipt->exchange_rate));
                $transaction->description = _lang('Cash Invoice Income') . ' #' . $receipt->receipt_number;
                $transaction->reference   = $request->input('reference');
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->save();
            }

            if ($product->stock_management == 1 && $product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $receipt->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity));
                $transaction->description = $receiptItem->product_name . ' Sales #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->save();

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $receipt->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity));
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->ref_id      = $receipt->id;
                $transaction->description = 'Cash Invoice #' . $receipt->receipt_number;
                $transaction->save();
            }

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $receiptItem->taxes()->save(new ReceiptItemTax([
                        'receipt_id' => $receipt->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($receiptItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $receipt->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Cash Invoice Tax') . ' #' . $receipt->receipt_number;
                    $transaction->ref_id      = $receipt->id;
                    $transaction->ref_type    = 'receipt tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $receiptItem->product;
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

        //Increment Receipt Number
        BusinessSetting::where('name', 'receipt_number')->increment('value');

        DB::commit();

        $transaction              = new Transaction();
        $transaction->customer_id  = $request->input('customer_id') ?? NULL;
        $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = $request->input('account_id');
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->transaction_currency    = $request->currency;
        $transaction->currency_rate           = $receipt->exchange_rate;
        $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->description = 'Cash Invoice Payment #' . $receipt->receipt_number;
        $transaction->transaction_method      = $request->input('method');
        $transaction->reference   = $request->input('reference');
        $transaction->ref_id      = $receipt->id;
        $transaction->ref_type    = 'receipt';
        $transaction->customer_id = $receipt->customer_id;
        $transaction->save();

        if ($request->input('discount_value') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Sales Discount Allowed')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate           = $receipt->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
            $transaction->description = _lang('Cash Invoice Discount') . ' #' . $receipt->receipt_number;
            $transaction->ref_id      = $receipt->id;
            $transaction->ref_type    = 'receipt';
            $transaction->customer_id = $receipt->customer_id;
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Cash Invoice Created' . ' ' . $receipt->receipt_number;
        $audit->save();

        if ($receipt->id > 0) {
            return redirect()->route('receipts.show', $receipt->id)->with('success', _lang('Saved Successfully'));
        } else {
            return back()->with('error', _lang('Something going wrong, Please try again'));
        }
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

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'    => 'nullable',
            'title'          => 'required',
            'receipt_date'   => 'required|date',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('receipts.edit', $id)
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
            if (!Account::where('account_name', $account)->where('business_id', NULL)->exists() && !Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
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
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = auth()->id();
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        DB::beginTransaction();

        $currentTime = Carbon::now();

        $summary = $this->calculateTotal($request);

        $receipt = Receipt::where('id', $id)->first();

        $receipt->customer_id     = $request->input('customer_id');
        $receipt->title           = $request->input('title');
        $receipt->order_number    = $request->input('order_number');
        $receipt->receipt_date    = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d');
        $receipt->sub_total       = $summary['subTotal'];
        $receipt->grand_total     = $summary['grandTotal'];
        $receipt->currency        = $request['currency'];
        $receipt->converted_total = $request->input('converted_total');
        $receipt->exchange_rate   = $request->input('exchange_rate');
        $receipt->discount        = $summary['discountAmount'];
        $receipt->discount_type   = $request->input('discount_type');
        $receipt->discount_value  = $request->input('discount_value') ?? 0;
        $receipt->note            = $request->input('note');
        $receipt->footer          = $request->input('footer');
        $receipt->user_id         = auth()->id();
        $receipt->business_id     = request()->activeBusiness->id;

        $receipt->save();

        //Update Invoice item
        foreach ($receipt->items as $receipt_item) {
            $product = $receipt_item->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $receipt_item->quantity;
                $product->save();
            }

            $receipt_item->delete();

            $transaction = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt')
                ->where('account_id', $product->income_account_id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }

            $transaction = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt')
                ->where('account_id', get_account('Inventory')->id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }

            $transaction = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt')
                ->where('account_id', $product->expense_account_id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $receiptItem = $receipt->items()->save(new ReceiptItem([
                'receipt_id'   => $receipt->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                'user_id'      => auth()->id(),
                'business_id'  => request()->activeBusiness->id,
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $receiptItem->sub_total / $receipt->exchange_rate);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $receipt->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $receiptItem->sub_total / $receipt->exchange_rate));
                $transaction->description = _lang('Cash Invoice Income') . ' #' . $receipt->receipt_number;
                $transaction->reference   = $request->input('reference');
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->save();
            }

            if ($product->stock_management == 1 && $product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $receipt->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity));
                $transaction->description = $receiptItem->product_name . ' Sales #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->save();

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $receipt->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity));
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->ref_id      = $receipt->id;
                $transaction->description = 'Cash Invoice #' . $receipt->receipt_number;
                $transaction->save();
            }

            if (isset($request->taxes)) {
                $transaction = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt tax')
                    ->get();

                foreach ($transaction as $taxTransaction) {
                    $taxTransaction->delete();
                }

                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $receiptItem->taxes()->save(new ReceiptItemTax([
                        'receipt_id' => $receipt->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($receiptItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $receipt->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Cash Invoice Tax') . ' #' . $receipt->receipt_number;
                    $transaction->ref_id      = $receipt->id;
                    $transaction->ref_type    = 'receipt tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $receiptItem->product;
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

        $transaction = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt')
            ->with('account', function ($query) {
                $query->where('account_type', 'Bank')
                    ->orWhere('account_type', 'Cash');
            })
            ->first();

        if ($transaction->account->id != $request->input('account_id')) {
            $transaction->trans_date = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
            $transaction->account_id = $request->input('account_id');
            $transaction->save();
        } else {
            $transaction->trans_date = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
            $transaction->save();
        }

        if ($transaction->transaction_method != $request->input('method')) {
            $transaction->transaction_method = $request->input('method');
            $transaction->save();
        }

        if ($request->input('discount_value') == 0) {
            $transaction->trans_date = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();
            if ($transaction != null) {
                $transaction->delete();
            }
        }

        if ($request->input('discount_value') > 0) {
            $transaction = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();

            if ($transaction == null) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Sales Discount Allowed')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $receipt->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Cash Invoice Discount') . ' #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->save();
            } else {
                $transaction->trans_date = Carbon::parse($request->input('receipt_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Cash Invoice Updated' . ' ' . $receipt->receipt_number;
        $audit->save();

        return redirect()->route('receipts.show', $receipt->id)->with('success', _lang('Updated Successfully'));
    }

    public function destroy($id)
    {
        $receipt = Receipt::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Cash Invoice Deleted' . ' ' . $receipt->receipt_number;
        $audit->save();

        // increaase stock
        foreach ($receipt->items as $receipt_item) {
            $product = $receipt_item->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $receipt_item->quantity;
                $product->save();
            }
        }
        // delete transactions
        Transaction::where('ref_id', $receipt->id)
            ->where(function ($query) {
                $query->where('ref_type', 'receipt')
                    ->orWhere('ref_type', 'receipt tax');
            })
            ->delete();
        $receipt->delete();
        return redirect()->route('receipts.index')->with('success', _lang('Deleted Successfully'));
    }

    public function pos()
    {
        $products = Product::all();
        $categories = SubCategory::all();
        $currencies = Currency::all();
        $accounts = Account::where('account_type', 'cash')->orWhere('account_type', 'bank')->get();
        $customers = Customer::all();
        $methods = TransactionMethod::all();
        $baseCurrency = Currency::where('base_currency', 1)->first();
        $holdList = HoldPosInvoice::with('items')->get();
        $todayList = Receipt::where('receipt_date', date('Y-m-d'))->get();
        $prescriptionProducts = PrescriptionProduct::with('items', 'prescription')->where('status', 0)->get();
        $pos_default_taxes = json_decode(get_business_option('pos_default_taxes', ""), true);
        $pos_product_image = get_business_option('pos_product_image', 0);
        $pos_default_currency_change = get_business_option('pos_default_currency_change', "");
        $taxes = Tax::all();

        return Inertia::render('Backend/User/Pos/Pos', [
            'products' => $products,
            'categories' => $categories,
            'currencies' => $currencies,
            'accounts' => $accounts,
            'customers' => $customers,
            'methods' => $methods,
            'baseCurrency' => $baseCurrency,
            'holdList' => $holdList,
            'todayList' => $todayList,
            'prescriptionProducts' => $prescriptionProducts,
            'pos_default_taxes' => $pos_default_taxes,
            'pos_product_image' => $pos_product_image,
            'pos_default_currency_change' => $pos_default_currency_change,
            'taxes' => $taxes,
        ]);
    }

    public function import_receipts(Request $request)
    {
        $request->validate([
            'receipts_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new CashInvoiceImport, $request->file('receipts_file'));

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->id();
            $audit->event = 'Cash Invoices Imported';
            $audit->save();

            return redirect()->route('receipts.index')->with('success', _lang('Invoices Imported'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receipts_filter(Request $request)
    {
        $from =  explode('to', $request->date_range)[0] ?? '';
        $to = explode('to', $request->date_range)[1] ?? '';

        $query = Receipt::select('receipts.*')
            ->with('customer');

        if ($request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($from != '' && $to != '') {
            $query->whereDate('receipt_date', '>=', Carbon::parse($from)->format('Y-m-d'))
                ->whereDate('receipt_date', '<=', Carbon::parse($to)->format('Y-m-d'));
        }

        $receipts = $query->get();

        $customer_id = $request->customer_id;
        $date_range = $request->date_range;

        return Inertia::render('Backend/User/CashInvoice/List', [
            'receipts' => $receipts,
            'customer_id' => $customer_id,
            'date_range' => $date_range,
        ]);
    }

    private function calculatePosTotal(Request $request)
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
            if (isset($request->tax_amount)) {
                foreach ($request->tax_amount as $index => $amount) {
                    if ($amount == 0) {
                        continue;
                    }
                    $tax         = Tax::find($index);
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
            'subTotal'       => $subTotal,
            'taxAmount'      => $taxAmount,
            'discountAmount' => $discountAmount,
            'grandTotal'     => $grandTotal,
        );
    }

    public function pos_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'    => $request->credit_cash == 'provider' ? 'required' : 'nullable',
            'client_id'      => $request->credit_cash == 'credit' || $request->credit_cash == 'provider' ? 'required' : 'nullable',
            'product_id'     => 'required',
            'currency'       => 'required',
            'account_id'     => $request->credit_cash == 'cash' ? 'required' : 'nullable',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('receipts.pos')
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
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = auth()->id();
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $month = Carbon::parse($request->receipt_date)->format('F');
        $year = Carbon::parse($request->receipt_date)->format('Y');
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

        $summary = $this->calculatePosTotal($request);

        if ($request->credit_cash == 'cash') {

            DB::beginTransaction();

            $receipt                  = new Receipt();
            $receipt->customer_id     = $request->input('client_id') ?? NULL;
            $receipt->title           = get_business_option('receipt_title', 'Cash Invoice');
            $receipt->receipt_number  = get_business_option('receipt_number');
            $receipt->receipt_date    = Carbon::parse($request->input('invoice_date'))->format('Y-m-d');
            $receipt->sub_total       = $summary['subTotal'];
            $receipt->grand_total     = $summary['grandTotal'];
            $receipt->currency        = $request['currency'];
            $receipt->converted_total = $request->input('converted_total');
            $receipt->exchange_rate   = $request->input('exchange_rate');
            $receipt->discount        = $summary['discountAmount'];
            $receipt->discount_type   = $request->input('discount_type');
            $receipt->discount_value  = $request->input('discount_value') ?? 0;
            $receipt->user_id         = auth()->id();
            $receipt->business_id     = request()->activeBusiness->id;
            $receipt->short_code      = rand(100000, 9999999) . uniqid();
            if ($request->appointment == 1) {
                $receipt->queue_number    = BusinessSetting::where('name', 'queue_number')->first()->value;
            }
            $receipt->save();

            for ($i = 0; $i < count($request->product_id); $i++) {
                $receiptItem = $receipt->items()->save(new ReceiptItem([
                    'receipt_id'   => $receipt->id,
                    'product_id'   => $request->product_id[$i],
                    'product_name' => $request->product_name[$i],
                    'description'  => null,
                    'quantity'     => $request->quantity[$i],
                    'unit_cost'    => $request->unit_cost[$i],
                    'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                    'user_id'      => auth()->id(),
                    'business_id'  => request()->activeBusiness->id,
                ]));

                $product = Product::where('id', $request->product_id[$i])->first();

                if ($product->allow_for_selling == 1) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                    $transaction->account_id  = $product->income_account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $receiptItem->sub_total / $receipt->exchange_rate);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate           = $receipt->exchange_rate;
                    $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $receiptItem->sub_total / $receipt->exchange_rate));
                    $transaction->description = _lang('Cash Invoice Income') . ' #' . $receipt->receipt_number;
                    $transaction->reference   = $request->input('reference');
                    $transaction->ref_id      = $receipt->id;
                    $transaction->ref_type    = 'receipt';
                    $transaction->customer_id = $receipt->customer_id;
                    $transaction->save();
                }

                if ($product->stock_management == 1 && $product->allow_for_purchasing == 1) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Inventory')->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate           = $receipt->exchange_rate;
                    $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity));
                    $transaction->description = $receiptItem->product_name . ' Sales #' . $receipt->receipt_number;
                    $transaction->ref_id      = $receipt->id;
                    $transaction->ref_type    = 'receipt';
                    $transaction->customer_id = $receipt->customer_id;
                    $transaction->save();

                    $transaction              = new Transaction();
                    $transaction->trans_date  = now()->format('Y-m-d H:i');
                    $transaction->account_id  = $product->expense_account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $receipt->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $receiptItem->quantity));
                    $transaction->ref_type    = 'receipt';
                    $transaction->customer_id = $receipt->customer_id;
                    $transaction->ref_id      = $receipt->id;
                    $transaction->description = 'Cash Invoice #' . $receipt->receipt_number;
                    $transaction->save();
                }

                if (isset($request->tax_amount)) {
                    foreach ($request->tax_amount as $index => $amount) {
                        $tax = Tax::find($index);

                        $receiptItem->taxes()->save(new ReceiptItemTax([
                            'receipt_id' => $receipt->id,
                            'tax_id'     => $index,
                            'name'       => $tax->name . ' ' . $tax->rate . ' %',
                            'amount'     => ($receiptItem->sub_total / 100) * $tax->rate,
                        ]));

                        $transaction              = new Transaction();
                        $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                        $transaction->account_id  = $tax->account_id;
                        $transaction->dr_cr       = 'cr';
                        $transaction->transaction_currency    = $request->currency;
                        $transaction->currency_rate = $receipt->exchange_rate;
                        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $tax->rate));
                        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $tax->rate);
                        $transaction->description = _lang('Cash Invoice Tax') . ' #' . $receipt->receipt_number;
                        $transaction->ref_id      = $receipt->id;
                        $transaction->ref_type    = 'receipt tax';
                        $transaction->tax_id      = $tax->id;
                        $transaction->save();
                    }
                }

                //Update Stock
                $product = $receiptItem->product;
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

            //Increment Receipt Number
            BusinessSetting::where('name', 'receipt_number')->increment('value');

            if ($request->appointment == 1) {
                // increment queue number
                BusinessSetting::where('name', 'queue_number')->increment('value');
            }

            DB::commit();

            $transaction              = new Transaction();
            $transaction->customer_id  = $request->input('customer_id') ?? NULL;
            $transaction->trans_date  = now()->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->input('account_id');
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate           = $receipt->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
            $transaction->description = 'Cash Invoice Payment #' . $receipt->receipt_number;
            $transaction->transaction_method      = $request->input('method');
            $transaction->reference   = $request->input('reference');
            $transaction->ref_id      = $receipt->id;
            $transaction->ref_type    = 'receipt';
            $transaction->customer_id = $receipt->customer_id;
            $transaction->save();

            if ($request->input('discount_value') > 0) {
                $transaction              = new Transaction();
                $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Sales Discount Allowed')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $receipt->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Cash Invoice Discount') . ' #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->save();
            }

            if ($request->hold_pos_id != '') {
                $holdPosInvoice = HoldPosInvoice::find($request->hold_pos_id);

                if ($holdPosInvoice) {
                    $holdPosInvoice->delete();
                }
            }

            if ($request->prescription_products_id != "") {
                $prescription_products = PrescriptionProduct::find($request->prescription_products_id);
                $prescription_products->status = 1;
                $prescription_products->save();

                // delete all items
                $prescription_products->items()->delete();

                for ($i = 0; $i < count($request->product_id); $i++) {
                    $prescription_products->items()->save(new PrescriptionProductItem([
                        'prescription_products_id'   => $prescription_products->id,
                        'product_id'   => $request->product_id[$i],
                        'product_name' => $request->product_name[$i],
                        'description'  => null,
                        'quantity'     => $request->quantity[$i],
                        'unit_cost'    => $request->unit_cost[$i],
                        'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                        'user_id'      => auth()->id(),
                        'business_id'  => request()->activeBusiness->id,
                    ]));
                }
            }
        } elseif ($request->credit_cash == 'credit') {

            DB::beginTransaction();

            $invoice                  = new Invoice();
            $invoice->customer_id     = $request->input('client_id');
            $invoice->title           = get_business_option('invoice_title', 'Invoice');
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
            if ($request->appointment == 1) {
                $invoice->queue_number    = BusinessSetting::where('name', 'queue_number')->first()->value;
            }
            $invoice->save();

            $currentTime = Carbon::now();

            for ($i = 0; $i < count($request->product_id); $i++) {
                $invoiceItem = $invoice->items()->save(new InvoiceItem([
                    'invoice_id'   => $invoice->id,
                    'product_id'   => $request->product_id[$i],
                    'product_name' => $request->product_name[$i],
                    'description'  => null,
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
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total);
                    $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                    $transaction->ref_id      = $invoice->id;
                    $transaction->ref_type    = 'invoice';
                    $transaction->customer_id = $invoice->customer_id;

                    $transaction->save();
                }

                if ($product->stock_management == 1 && $product->allow_for_purchasing == 1) {
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

                if (isset($request->tax_amount)) {
                    foreach ($request->tax_amount as $index => $amount) {
                        $tax = Tax::find($index);

                        $invoiceItem->taxes()->save(new InvoiceItemTax([
                            'invoice_id' => $invoice->id,
                            'tax_id'     => $index,
                            'name'       => $tax->name . ' ' . $tax->rate . ' %',
                            'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                        ]));

                        $transaction              = new Transaction();
                        $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                        $transaction->account_id  = $tax->account_id;
                        $transaction->dr_cr       = 'cr';
                        $transaction->transaction_currency    = $request->currency;
                        $transaction->currency_rate = $invoice->exchange_rate;
                        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate));
                        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate);
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

            if ($request->appointment == 1) {
                // increment queue number
                BusinessSetting::where('name', 'queue_number')->increment('value');
            }

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

            if ($request->hold_pos_id != '') {
                $holdPosInvoice = HoldPosInvoice::find($request->hold_pos_id);

                if ($holdPosInvoice) {
                    $holdPosInvoice->delete();
                }
            }

            if ($request->prescription_products_id != "") {
                $prescription_products = PrescriptionProduct::find($request->prescription_products_id);
                $prescription_products->status = 1;
                $prescription_products->save();

                // delete all items
                $prescription_products->items()->delete();

                for ($i = 0; $i < count($request->product_id); $i++) {
                    $prescription_products->items()->save(new PrescriptionProductItem([
                        'prescription_products_id'   => $prescription_products->id,
                        'product_id'   => $request->product_id[$i],
                        'product_name' => $request->product_name[$i],
                        'description'  => null,
                        'quantity'     => $request->quantity[$i],
                        'unit_cost'    => $request->unit_cost[$i],
                        'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                    ]));
                }
            }
        } elseif ($request->credit_cash == 'provider') {
            DB::beginTransaction();

            $invoice                  = new Invoice();
            $invoice->customer_id     = $request->input('customer_id');
            $invoice->title           = get_business_option('invoice_title', 'Invoice');
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
            $invoice->client_id       = $request->input('client_id');
            if ($request->appointment == 1) {
                $invoice->queue_number    = BusinessSetting::where('name', 'queue_number')->first()->value;
            }
            $invoice->save();

            $currentTime = Carbon::now();

            for ($i = 0; $i < count($request->product_id); $i++) {
                $invoiceItem = $invoice->items()->save(new InvoiceItem([
                    'invoice_id'   => $invoice->id,
                    'product_id'   => $request->product_id[$i],
                    'product_name' => $request->product_name[$i],
                    'description'  => null,
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
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $invoiceItem->sub_total);
                    $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                    $transaction->ref_id      = $invoice->id;
                    $transaction->ref_type    = 'invoice';
                    $transaction->customer_id = $invoice->customer_id;

                    $transaction->save();
                }

                if ($product->stock_management == 1 && $product->allow_for_purchasing == 1) {
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

                if (isset($request->tax_amount)) {
                    foreach ($request->tax_amount as $index => $amount) {
                        $tax = Tax::find($index);

                        $invoiceItem->taxes()->save(new InvoiceItemTax([
                            'invoice_id' => $invoice->id,
                            'tax_id'     => $index,
                            'name'       => $tax->name . ' ' . $tax->rate . ' %',
                            'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                        ]));

                        $transaction              = new Transaction();
                        $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                        $transaction->account_id  = $tax->account_id;
                        $transaction->dr_cr       = 'cr';
                        $transaction->transaction_currency    = $request->currency;
                        $transaction->currency_rate = $invoice->exchange_rate;
                        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate));
                        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate);
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

            if ($request->appointment == 1) {
                // increment queue number
                BusinessSetting::where('name', 'queue_number')->increment('value');
            }

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

            // Delete hold pos invoice if exists
            if ($request->hold_pos_id != '') {
                $holdPosInvoice = HoldPosInvoice::find($request->hold_pos_id);

                if ($holdPosInvoice) {
                    $holdPosInvoice->delete();
                }
            }

            if ($request->prescription_products_id != "") {
                $prescription_products = PrescriptionProduct::find($request->prescription_products_id);
                $prescription_products->status = 1;
                $prescription_products->save();

                // delete all items
                $prescription_products->items()->delete();

                for ($i = 0; $i < count($request->product_id); $i++) {
                    $prescription_products->items()->save(new PrescriptionProductItem([
                        'prescription_products_id'   => $prescription_products->id,
                        'product_id'   => $request->product_id[$i],
                        'product_name' => $request->product_name[$i],
                        'description'  => null,
                        'quantity'     => $request->quantity[$i],
                        'unit_cost'    => $request->unit_cost[$i],
                        'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                    ]));
                }
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        if ($request->credit_cash == 'credit') {
            $audit->event = 'Created Credit Invoice #' . $invoice->invoice_number;
        } elseif ($request->credit_cash == 'provider') {
            $audit->event = 'Created Credit Invoice for Provider #' . $invoice->invoice_number;
        } else {
            $audit->event = 'Created Cash Invoice #' . $receipt->receipt_number;
        }
        $audit->save();

        if ($request->credit_cash == 'cash') {
            return redirect()->route('receipts.invoice_pos', $receipt->id)->with('success', _lang('Saved Successfully'));
        } else {
            return redirect()->route('receipts.credit_invoice_pos', $invoice->id)->with('success', _lang('Saved Successfully'));
        }
    }

    public function invoice_pos($id)
    {
        $receipt = Receipt::with(['items', 'taxes', 'customer'])->find($id);
        return Inertia::render('Backend/User/Pos/InvoicePos', [
            'receipt' => $receipt,
            'business' => request()->activeBusiness
        ]);
    }

    public function credit_invoice_pos($id)
    {
        $invoice = Invoice::with(['items', 'taxes', 'customer'])->find($id);
        return Inertia::render('Backend/User/Pos/CreditInvoicePos', [
            'invoice' => $invoice,
            'business' => request()->activeBusiness
        ]);
    }

    public function pos_products()
    {
        return Product::where('business_id', request()->activeBusiness->id)
            ->where('type', 'product')
            ->where('status', 1)
            ->get();
    }

    public function pos_currency()
    {
        return Currency::where('business_id', request()->activeBusiness->id)
            ->whereIn('id', json_decode(get_business_option('pos_currency')))
            ->where('status', 1)
            ->get();
    }

    public function pos_tax()
    {
        return Tax::where('business_id', request()->activeBusiness->id)->get();
    }

    public function bulk_destroy(Request $request)
    {
        if ($request->ids == null) {
            return redirect()->route('receipts.index')->with('error', _lang('Please Select invoice'));
        }

        $receipts = Receipt::whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Deleted Cash Invoices #' . $receipts->pluck('receipt_number')->implode(', ');
        $audit->save();

        foreach ($receipts as $receipt) {
            // increaase stock
            foreach ($receipt->items as $receipt_item) {
                $product = $receipt_item->product;
                if ($product->type == 'product' && $product->stock_management == 1) {
                    $product->stock = $product->stock + $receipt_item->quantity;
                    $product->save();
                }
            }
            // delete transactions
            $transactions = Transaction::where('ref_id', $receipt->id)->where('ref_type', 'receipt')->get();

            foreach ($transactions as $trans) {
                $trans->delete();
            }

            $receipt->delete();
        }

        return redirect()->route('receipts.index')->with('success', _lang('Deleted Successfully'));
    }

    public function export_receipts()
    {
        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Exported Cash Invoices';
        $audit->save();

        return Excel::download(new CashInvoiceExport, 'cash invoices ' . now()->format('d m Y') . '.xlsx');
    }

    public function pos_products_category($id)
    {
        return Product::where('sub_category_id', $id)->get();
    }
}
