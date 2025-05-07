<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Business;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\quotationItem;
use App\Models\quotationItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Notifications\SendQuotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Validator;

class QuotationController extends Controller
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
            if ($route_name == 'quotations.store' || $route_name == 'quotations.duplicate') {
                if (has_limit('quotations', 'quotation_limit', false) <= 0) {
                    if (!$request->ajax()) {
                        return back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
                    } else {
                        return response()->json(['result' => 'error', 'message' => _lang('Sorry, Your have already reached your package quota !')]);
                    }
                }
            }

            if ($route_name == 'quotations.convert_to_invoice') {
                if (has_limit('invoices', 'invoice_limit', false) <= 0) {
                    if (!$request->ajax()) {
                        return back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
                    } else {
                        return response()->json(['result' => 'error', 'message' => _lang('Sorry, Your have already reached your package quota !')]);
                    }
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
        $query = Quotation::with('customer');

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
                    $query->where('quotation_date', '>=', $filters['date_range']['start']);
                }
                if (!empty($filters['date_range']['end'])) {
                    $query->where('quotation_date', '<=', $filters['date_range']['end']);
                }
            }
        }

        // Handle sorting
        $sortField = $request->get('sort_field', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        $quotations = $query->paginate($perPage);

        return Inertia::render('Backend/User/Quotation/List', [
            'quotations' => $quotations->items(),
            'meta' => [
                'total' => $quotations->total(),
                'per_page' => $quotations->perPage(),
                'current_page' => $quotations->currentPage(),
                'last_page' => $quotations->lastPage(),
                'from' => $quotations->firstItem(),
                'to' => $quotations->lastItem(),
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
        $quotation_title = get_business_option('quotation_title', 'Quotation');
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();

        return Inertia::render('Backend/User/Quotation/Create', [
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'quotation_title' => $quotation_title,
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
            'customer_id'      => 'required',
            'title'            => 'required',
            'quotation_date'   => 'required',
            'expired_date'     => 'required',
            'product_id'       => 'required',
            'currency'          => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('quotations.create')
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

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $quotation                   = new Quotation();
        $quotation->customer_id      = $request->input('customer_id');
        $quotation->title            = $request->input('title');
        $quotation->quotation_number = BusinessSetting::where('name', 'quotation_number')->first()->value;
        $quotation->po_so_number     = $request->input('po_so_number');
        $quotation->quotation_date   = Carbon::parse($request->input('quotation_date'))->format('Y-m-d');
        $quotation->expired_date     = Carbon::parse($request->input('expired_date'))->format('Y-m-d');
        $quotation->sub_total        = $summary['subTotal'];
        $quotation->grand_total      = $summary['grandTotal'];
        $quotation->currency         = $request['currency'];
        $quotation->converted_total  = $request->input('converted_total');
        $quotation->exchange_rate    = Currency::where('name', $request->currency)->first()->exchange_rate;
        $quotation->discount         = $summary['discountAmount'];
        $quotation->discount_type    = $request->input('discount_type');
        $quotation->discount_value   = $request->input('discount_value') ?? 0;
        $quotation->template_type    = is_numeric($request->template) ? 1 : 0;
        $quotation->template         = $request->input('template') ?? 'default';
        $quotation->note             = $request->input('note');
        $quotation->footer           = $request->input('footer');
        $quotation->short_code       = rand(100000, 9999999) . uniqid();

        $quotation->save();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $quotationItem = $quotation->items()->save(new quotationItem([
                'quotation_id' => $quotation->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $quotationItem->taxes()->save(new quotationItemTax([
                        'quotation_id' => $quotation->id,
                        'tax_id'       => $taxId,
                        'name'         => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'       => ($quotationItem->sub_total / 100) * $tax->rate,
                    ]));
                }
            }
        }

        //Increment Quotation Number
        BusinessSetting::where('name', 'quotation_number')->increment('value');

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotation Created' . ' ' . $quotation->quotation_number;
        $audit->save();

        return redirect()->route('quotations.show', $quotation->id)->with('success', _lang('Saved Successfully'));
    }

    /**
     * Preview Private quotation
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $quotation = quotation::with(['business', 'items', 'customer', 'taxes'])->find($id);

        return Inertia::render('Backend/User/Quotation/View', [
            'quotation' => $quotation,
        ]);
    }

    public function get_quotation_link(Request $request, $id)
    {
        $quotation = quotation::find($id);
        if ($request->ajax()) {
            return view('backend.user.quotation.modal.share-link', compact('quotation', 'id'));
        }
        return back();
    }

    public function send_email(Request $request, $id)
    {
        if (!$request->ajax()) {
            return back();
        }
        if ($request->isMethod('get')) {
            $email_templates = EmailTemplate::whereIn('slug', ['NEW_QUOTATION_CREATED'])
                ->where('email_status', 1)->get();
            $quotation = Quotation::find($id);
            return view('backend.user.quotation.modal.send_email', compact('quotation', 'id', 'email_templates'));
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

            $quotation       = Quotation::find($id);
            $customer        = $quotation->customer;
            $customer->email = $request->email;

            try {
                Notification::send($customer, new SendQuotation($quotation, $customMessage, $request->template));
                return response()->json(['result' => 'success', 'message' => _lang('Email has been sent')]);
            } catch (\Exception $e) {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    /**
     * Preview Public quotation
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show_public_quotation($short_code, $export = 'preview')
    {
        $alert_col = 'col-lg-8 offset-lg-2';
        $quotation = Quotation::withoutGlobalScopes()->with(['customer', 'business', 'items', 'taxes'])
            ->where('short_code', $short_code)
            ->first();
        if ($export == 'pdf') {
            $pdf = Pdf::loadView('backend.user.quotation.pdf', compact('quotation'));
            return $pdf->download('quotation#-' . $quotation->quotation_number . '.pdf');
        }

        return view('backend.guest.quotation.view', compact('quotation', 'alert_col'));
    }

    public function export_pdf(Request $request, $id)
    {
        $quotation = Quotation::with(['business', 'items'])->find($id);
        $pdf = Pdf::loadView('backend.user.quotation.pdf', compact('quotation', 'id'));
        return $pdf->download('quotation#-' . $quotation->quotation_number . '.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $quotation = Quotation::with('items')->find($id);
        $taxIds = $quotation->taxes
            ->pluck('tax_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Get required data for the edit form
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();

        $decimalPlace = get_business_option('decimal_place', 2);

        return Inertia::render('Backend/User/Quotation/Edit', [
            'quotation' => $quotation,
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'decimalPlace' => $decimalPlace,
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
            'customer_id'      => 'required',
            'title'            => 'required',
            'quotation_date'   => 'required',
            'expired_date'     => 'required',
            'product_id'       => 'required',
            'template'         => 'required',
            'currency'         => 'required'
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('quotations.edit', $id)
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

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $quotation                   = Quotation::find($id);
        $quotation->customer_id      = $request->input('customer_id');
        $quotation->title            = $request->input('title');
        $quotation->po_so_number     = $request->input('po_so_number');
        $quotation->quotation_date   = Carbon::parse($request->input('quotation_date'))->format('Y-m-d');
        $quotation->expired_date     = Carbon::parse($request->input('expired_date'))->format('Y-m-d');
        $quotation->sub_total        = $summary['subTotal'];
        $quotation->grand_total      = $summary['grandTotal'];
        $quotation->currency         = $request['currency'];
        $quotation->converted_total  = $request->input('converted_total');
        $quotation->exchange_rate    = Currency::where('name', $request->currency)->first()->exchange_rate;
        $quotation->discount         = $summary['discountAmount'];
        $quotation->discount_type    = $request->input('discount_type');
        $quotation->discount_value   = $request->input('discount_value') ?? 0;
        $quotation->template_type    = is_numeric($request->template) ? 1 : 0;
        $quotation->template         = $request->input('template');
        $quotation->note             = $request->input('note');
        $quotation->footer           = $request->input('footer');

        $quotation->save();

        $quotation->items()->delete();
        for ($i = 0; $i < count($request->product_id); $i++) {
            $quotationItem = $quotation->items()->save(new quotationItem([
                'quotation_id' => $quotation->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            if (isset($request->taxes)) {
                $quotationItem->taxes()->delete();
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $quotationItem->taxes()->save(new quotationItemTax([
                        'quotation_id' => $quotation->id,
                        'tax_id'       => $taxId,
                        'name'         => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'       => ($quotationItem->sub_total / 100) * $tax->rate,
                    ]));
                }
            }
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotation Updated' . ' ' . $quotation->quotation_number;
        $audit->save();

        return redirect()->route('quotations.show', $quotation->id)->with('success', _lang('Updated Successfully'));
    }

    /** Duplicate Invoice */
    public function duplicate($id)
    {
        DB::beginTransaction();
        $quotation                      = Quotation::find($id);
        $newQuotation                   = $quotation->replicate();
        $newQuotation->quotation_number = get_business_option('quotation_number', rand());
        $newQuotation->short_code       = rand(100000, 9999999) . uniqid();
        $newQuotation->save();

        foreach ($quotation->items as $quotationItem) {
            $newquotationItem               = $quotationItem->replicate();
            $newquotationItem->quotation_id = $newQuotation->id;
            $newquotationItem->save();

            foreach ($quotationItem->taxes as $QuotationItemTax) {
                $newQuotationItemTax                    = $QuotationItemTax->replicate();
                $newQuotationItemTax->quotation_id      = $newQuotation->id;
                $newQuotationItemTax->quotation_item_id = $newquotationItem->id;
                $newQuotationItemTax->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'quotation_number')->increment('value');

        DB::commit();

        return redirect()->route('quotations.edit', $newQuotation->id);
    }

    /** Convert to Invoice **/
    public function convert_to_invoice(Request $request, $id)
    {
        DB::beginTransaction();

        $quotation = Quotation::find($id);

        $invoice                  = new Invoice();
        $invoice->customer_id     = $quotation->customer_id;
        $invoice->title           = get_business_option('invoice_title', 'Invoice');
        $invoice->invoice_number  = get_business_option('invoice_number', '100001');
        $invoice->order_number    = $quotation->po_so_number;
        $invoice->invoice_date    = date('Y-m-d');
        $invoice->due_date        = date('Y-m-d');
        $invoice->sub_total       = $quotation->sub_total;
        $invoice->grand_total     = $quotation->grand_total;
        $invoice->currency        = $quotation->currency;
        $invoice->converted_total = $quotation->converted_total;
        $invoice->exchange_rate   = $quotation->exchange_rate;
        $invoice->paid            = 0;
        $invoice->discount        = $quotation->discount;
        $invoice->discount_type   = $quotation->discount_type;
        $invoice->discount_value  = $quotation->discount_value;
        $invoice->template_type   = $quotation->template_type;
        $invoice->template        = $quotation->template;
        $invoice->note            = $quotation->note;
        $invoice->footer          = $quotation->footer;
        $invoice->short_code      = rand(100000, 9999999) . uniqid();

        $invoice->save();

        $currentTime = Carbon::now();

        foreach ($quotation->items as $item) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product_name,
                'description'  => $item->description,
                'quantity'     => $item->quantity,
                'unit_cost'    => $item->unit_cost,
                'sub_total'    => $item->sub_total,
            ]));

            $product = Product::where('id', $item->product_id)->first();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoiceItem->sub_total));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoiceItem->sub_total);
                $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';

                $transaction->save();
            }

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $invoice->currency;
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoiceItem->quantity;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency    = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type    = 'invoice';
                $transaction->ref_id      = $invoice->id;
                $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
                $transaction->save();
            }

            if (isset($quotation->taxes)) {
                foreach ($quotation->taxes as $tax) {
                    $tax = Tax::find($tax->tax_id);

                    $invoiceItem->taxes()->save(new InvoiceItemTax([
                        'invoice_id' => $invoice->id,
                        'tax_id'     => $$tax->id,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $invoice->currency;
                    $transaction->currency_rate = $invoice->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, ($invoiceItem->sub_total / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, ($invoiceItem->sub_total / 100) * $tax->rate);
                    $transaction->description = _lang('Credit Invoice Tax') . ' #' . $invoice->invoice_number;
                    $transaction->ref_id      = $invoice->id;
                    $transaction->ref_type    = 'invoice tax';
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $invoiceItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                //Check Available Stock Quantity
                if ($product->stock < $item->quantity) {
                    DB::rollBack();
                    return back()->with('error', $product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $item->quantity;
                $product->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'invoice_number')->increment('value');

        DB::commit();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = get_account('Accounts Receivable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->grand_total);
        $transaction->transaction_currency    = $invoice->currency;
        $transaction->currency_rate           = $invoice->exchange_rate;
        $transaction->base_currency_amount    = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->grand_total));
        $transaction->ref_id      = $invoice->id;
        $transaction->ref_type    = 'invoice';
        $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
        $transaction->save();

        if ($quotation->discount_value > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Sales Discount Allowed')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->discount);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->discount));
            $transaction->description = _lang('Credit Invoice Discount') . ' #' . $invoice->invoice_number;
            $transaction->ref_id      = $invoice->id;
            $transaction->ref_type    = 'invoice';
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotation Converted to Invoice' . ' ' . $quotation->quotation_number . ' -> ' . $invoice->invoice_number;
        $audit->save();

        return redirect()->route('invoices.edit', $invoice->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $quotation = Quotation::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotation Deleted' . ' ' . $quotation->quotation_number;
        $audit->save();

        $quotation->delete();
        return redirect()->route('quotations.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $quotation = Quotation::find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Quotation Deleted' . ' ' . $quotation->quotation_number;
            $audit->save();

            $quotation->delete();
        }
        return redirect()->route('quotations.index')->with('success', _lang('Deleted Successfully'));
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
}
