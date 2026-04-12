<?php

namespace App\Http\Controllers\User;

use App\Exports\QuotationExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\DefferedEarning;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\InsuranceBenefit;
use App\Models\InsuranceFamilySize;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\quotationItem;
use App\Models\quotationItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Notifications\SendQuotation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use function Spatie\LaravelPdf\Support\pdf;

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
        Gate::authorize('quotations.view');

        $query = $this->buildQuotationQuery($request, ['customer']);
        $allQuotations = $this->buildQuotationQuery($request)->get();

        $summary = [
            'total_quotations' => $allQuotations->count(),
            'grand_total' => $allQuotations->sum('grand_total'),
            'active_quotations' => $allQuotations->filter(function ($quotation) {
                return Carbon::parse($quotation->getRawOriginal('expired_date'))->greaterThanOrEqualTo(Carbon::today());
            })->count(),
            'expired_quotations' => $allQuotations->filter(function ($quotation) {
                return Carbon::parse($quotation->getRawOriginal('expired_date'))->lessThan(Carbon::today());
            })->count(),
        ];

        // Handle sorting
        $sorting = $this->resolveQuotationSorting($request);
        $this->applyQuotationSorting($query, $sorting);

        // Handle pagination
        $perPage = $request->get('per_page', 50);
        $quotations = $query->paginate($perPage);

        // Get all customers for the filter
        $customers = Customer::all();

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
            'filters' => array_merge($request->all(), [
                'sorting' => $sorting,
            ]),
            'customers' => $customers,
            'summary' => $summary,
            'trashed_quotations' => Quotation::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('quotations.view');

        $query = $this->buildQuotationQuery($request, ['customer'], true);

        // Handle sorting
        $sorting = $this->resolveQuotationSorting($request);
        $this->applyQuotationSorting($query, $sorting);

        // Handle pagination
        $perPage = $request->get('per_page', 50);
        $quotations = $query->paginate($perPage);

        // Get all customers for the filter
        $customers = Customer::all();

        return Inertia::render('Backend/User/Quotation/Trash', [
            'quotations' => $quotations->items(),
            'meta' => [
                'total' => $quotations->total(),
                'per_page' => $quotations->perPage(),
                'current_page' => $quotations->currentPage(),
                'last_page' => $quotations->lastPage(),
                'from' => $quotations->firstItem(),
                'to' => $quotations->lastItem(),
            ],
            'filters' => array_merge($request->all(), [
                'sorting' => $sorting,
            ]),
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Gate::authorize('quotations.create');

        $quotation_title = get_business_option('quotation_title', 'Quotation');
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $familySizes = InsuranceFamilySize::all();
        $benefits = InsuranceBenefit::all();

        return Inertia::render('Backend/User/Quotation/Create', [
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'familySizes' => $familySizes,
            'benefits' => $benefits,
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
        Gate::authorize('quotations.create');

        $validator = Validator::make($request->all(), [
            'customer_id'      => 'required',
            'title'            => 'required',
            'quotation_date'   => 'required',
            'expired_date'     => 'required',
            'product_id'       => 'required',
            'currency'         => 'required',
            'is_deffered'      => 'nullable|in:0,1',
            'invoice_category' => 'nullable|required_if:is_deffered,1|in:medical,gpa,other',
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
        $quotation->po_so_number     = $this->getQuotationReferenceNumber($request);
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
        $quotation->is_deffered      = $this->isDeferredQuotation($request);
        $quotation->invoice_category = $this->isDeferredQuotation($request) ? $request->input('invoice_category') : null;
        $quotation->short_code       = rand(100000, 9999999) . uniqid();

        $quotation->save();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $quotationItem = $quotation->items()->save(new quotationItem(
                array_merge(
                    ['quotation_id' => $quotation->id],
                    $this->buildQuotationItemPayload($request, $i)
                )
            ));

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
        Gate::authorize('quotations.view');

        $quotation = quotation::with(['business.systemSettings', 'items', 'customer', 'taxes'])->find($id);
        $email_templates = EmailTemplate::whereIn('slug', ['NEW_QUOTATION_CREATED'])
            ->where('email_status', 1)->get();
        $decimalPlace = get_business_option('decimal_place', 2);

        return Inertia::render('Backend/User/Quotation/View', [
            'quotation' => $quotation,
            'email_templates' => $email_templates,
            'decimalPlace' => $decimalPlace,
        ]);
    }

    public function get_quotation_link(Request $request, $id)
    {
        Gate::authorize('quotations.view');

        $quotation = quotation::find($id);
        if ($request->ajax()) {
            return view('backend.user.quotation.modal.share-link', compact('quotation', 'id'));
        }
        return back();
    }

    public function send_email(Request $request, $id)
    {
        Gate::authorize('quotations.send_email');

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
            return redirect()->back()->with('success', _lang('Email has been sent'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Preview Public quotation
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pdf($id)
    {
        Gate::authorize('quotations.pdf');

        $quotation = Quotation::with(['business', 'items', 'taxes', 'customer'])->find($id);
        return pdf()
        ->view('backend.user.pdf.quotation', compact('quotation'))
        ->name('quotation-' . $quotation->quotation_number . '.pdf')
        ->download();
    }

    public function show_public_quotation($short_code)
    {
        $quotation = Quotation::withoutGlobalScopes()->with(['customer', 'business.systemSettings', 'items', 'taxes'])
            ->where('short_code', $short_code)
            ->first();

        return Inertia::render('Backend/User/Quotation/PublicView', [
            'quotation' => $quotation
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        Gate::authorize('quotations.update');

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
        $familySizes = InsuranceFamilySize::all();
        $benefits = InsuranceBenefit::all();

        $decimalPlace = get_business_option('decimal_place', 2);

        return Inertia::render('Backend/User/Quotation/Edit', [
            'quotation' => $quotation,
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'familySizes' => $familySizes,
            'benefits' => $benefits,
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
        Gate::authorize('quotations.update');

        $validator = Validator::make($request->all(), [
            'customer_id'      => 'required',
            'title'            => 'required',
            'quotation_date'   => 'required',
            'expired_date'     => 'required',
            'product_id'       => 'required',
            'currency'         => 'required',
            'is_deffered'      => 'nullable|in:0,1',
            'invoice_category' => 'nullable|required_if:is_deffered,1|in:medical,gpa,other',
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
        $quotation->po_so_number     = $this->getQuotationReferenceNumber($request);
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
        $quotation->is_deffered      = $this->isDeferredQuotation($request);
        $quotation->invoice_category = $this->isDeferredQuotation($request) ? $request->input('invoice_category') : null;

        $quotation->save();

        quotationItemTax::where('quotation_id', $quotation->id)->delete();
        $quotation->items()->delete();
        for ($i = 0; $i < count($request->product_id); $i++) {
            $quotationItem = $quotation->items()->save(new quotationItem(
                array_merge(
                    ['quotation_id' => $quotation->id],
                    $this->buildQuotationItemPayload($request, $i)
                )
            ));

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
        Gate::authorize('quotations.duplicate');

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
        Gate::authorize('quotations.convert_to_invoice');

        $quotation = Quotation::with(['items.taxes'])->findOrFail($id);

        if ((int) ($quotation->status ?? 0) === 1) {
            return back()->with('error', _lang('This quotation has already been accepted.'));
        }

        if ((int) ($quotation->status ?? 0) === 2) {
            return back()->with('error', _lang('Rejected quotations cannot be converted.'));
        }

        if ((int) $quotation->is_deffered === 1 && package()->deffered_invoice != 1) {
            return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
        }

        DB::beginTransaction();

        try {
            if ((int) $quotation->is_deffered === 1) {
                $invoice = $this->createDeferredInvoiceFromQuotation($request, $quotation);
                $redirectRoute = 'deffered_invoices.edit';
                $auditEvent = 'Quotation Converted to Deffered Invoice';
            } else {
                $invoice = $this->createNormalInvoiceFromQuotation($request, $quotation);
                $redirectRoute = 'invoices.edit';
                $auditEvent = 'Quotation Converted to Invoice';
            }

            $quotation->status = 1;
            $quotation->save();

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = $auditEvent . ' ' . $quotation->quotation_number . ' -> ' . $invoice->invoice_number;
            $audit->save();

            DB::commit();

            return redirect()->route($redirectRoute, $invoice->id)
                ->with('success', _lang('Quotation converted successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        Gate::authorize('quotations.view');

        $sorting = $this->resolveQuotationSorting($request);
        $query = $this->buildQuotationQuery($request, [
            'customer',
            'taxes',
            'items.product',
            'items.taxes',
        ]);

        $this->applyQuotationSorting($query, $sorting);

        $quotations = $query->get();

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotations Exported';
        $audit->save();

        return Excel::download(
            new QuotationExport($quotations),
            'quotations ' . now()->format('d m Y') . '.xlsx'
        );
    }

    public function reject($id)
    {
        Gate::authorize('quotations.update');

        $quotation = Quotation::findOrFail($id);

        if ((int) ($quotation->status ?? 0) === 1) {
            return back()->with('error', _lang('Accepted quotations cannot be rejected.'));
        }

        if ((int) ($quotation->status ?? 0) === 2) {
            return back()->with('error', _lang('This quotation has already been rejected.'));
        }

        $quotation->status = 2;
        $quotation->save();

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotation Rejected ' . $quotation->quotation_number;
        $audit->save();

        return redirect()->route('quotations.index')->with('success', _lang('Rejected Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Gate::authorize('quotations.delete');

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
        Gate::authorize('quotations.delete');

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

    public function permanent_destroy($id)
    {
        Gate::authorize('quotations.delete');

        $quotation = Quotation::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotation Permanently Deleted' . ' ' . $quotation->quotation_number;
        $audit->save();

        $quotation->forceDelete();
        return redirect()->route('quotations.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('quotations.delete');

        foreach ($request->ids as $id) {
            $quotation = Quotation::onlyTrashed()->find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Quotation Permanently Deleted' . ' ' . $quotation->quotation_number;
            $audit->save();

            $quotation->forceDelete();
        }
        return redirect()->route('quotations.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function restore($id)
    {
        Gate::authorize('quotations.restore');

        $quotation = Quotation::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Quotation Restored' . ' ' . $quotation->quotation_number;
        $audit->save();

        $quotation->restore();
        return redirect()->route('quotations.trash')->with('success', _lang('Restored Successfully'));
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('quotations.restore');

        foreach ($request->ids as $id) {
            $quotation = Quotation::onlyTrashed()->find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Quotation Restored' . ' ' . $quotation->quotation_number;
            $audit->save();

            $quotation->restore();
        }
        return redirect()->route('quotations.trash')->with('success', _lang('Restored Successfully'));
    }

    private function buildQuotationQuery(Request $request, array $relations = [], bool $onlyTrashed = false)
    {
        $query = $onlyTrashed ? Quotation::onlyTrashed() : Quotation::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        $this->applyQuotationFilters($query, $request);

        return $query;
    }

    private function applyQuotationFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('customer_id') && $request->customer_id !== '') {
            $query->where('customer_id', $request->customer_id);
        }

        $dateRange = $request->input('date_range');
        if (is_array($dateRange) && count($dateRange) >= 2 && $dateRange[0] && $dateRange[1]) {
            $query->where(function ($q) use ($dateRange) {
                $q->whereDate('quotation_date', '>=', $dateRange[0])
                    ->whereDate('quotation_date', '<=', $dateRange[1]);
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $this->applyQuotationStatusFilter($query, (string) $request->status);
        }
    }

    private function resolveQuotationSorting(Request $request): array
    {
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);
        $allowedColumns = [
            'id',
            'quotation_number',
            'is_deffered',
            'quotation_date',
            'expired_date',
            'grand_total',
            'customer.name',
        ];

        $sortColumn = $sorting['column'] ?? 'id';
        if (!in_array($sortColumn, $allowedColumns, true)) {
            $sortColumn = 'id';
        }

        $sortDirection = strtolower((string) ($sorting['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return [
            'column' => $sortColumn,
            'direction' => $sortDirection,
        ];
    }

    private function applyQuotationSorting($query, array $sorting): void
    {
        if ($sorting['column'] === 'customer.name') {
            $query->join('customers', 'quotations.customer_id', '=', 'customers.id')
                ->orderBy('customers.name', $sorting['direction'])
                ->select('quotations.*');

            return;
        }

        $query->orderBy('quotations.' . $sorting['column'], $sorting['direction']);
    }

    private function applyQuotationStatusFilter($query, string $status): void
    {
        switch ($status) {
            case '0':
                $query->where('status', 0)
                    ->whereDate('expired_date', '>=', Carbon::today());
                break;
            case '1':
                $query->where('status', 0)
                    ->whereDate('expired_date', '<', Carbon::today());
                break;
            case '2':
                $query->where('status', 1);
                break;
            case '3':
                $query->where('status', 2);
                break;
        }
    }

    private function ensureDefaultAccounts(Request $request, bool $includeUnearnedRevenue = false): void
    {
        $defaultAccounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        if ($includeUnearnedRevenue) {
            $defaultAccounts[] = 'Unearned Revenue';
        }

        foreach ($defaultAccounts as $accountName) {
            if (Account::where('account_name', $accountName)->where('business_id', $request->activeBusiness->id)->exists()) {
                continue;
            }

            $account = new Account();

            switch ($accountName) {
                case 'Accounts Receivable':
                    $account->account_code = '1100';
                    $account->account_type = 'Other Current Asset';
                    $account->dr_cr = 'dr';
                    break;
                case 'Sales Tax Payable':
                    $account->account_code = '2200';
                    $account->account_type = 'Current Liability';
                    $account->dr_cr = 'cr';
                    break;
                case 'Sales Discount Allowed':
                    $account->account_code = '4009';
                    $account->account_type = 'Other Income';
                    $account->dr_cr = 'dr';
                    break;
                case 'Inventory':
                    $account->account_code = '1000';
                    $account->account_type = 'Other Current Asset';
                    $account->dr_cr = 'dr';
                    break;
                case 'Unearned Revenue':
                    $account->account_code = '2300';
                    $account->account_type = 'Current Liability';
                    $account->dr_cr = 'cr';
                    break;
            }

            $account->account_name = $accountName;
            $account->business_id = $request->activeBusiness->id;
            $account->user_id = $request->activeBusiness->user->id;
            $account->opening_date = now()->format('Y-m-d');
            $account->save();
        }
    }

    private function isDeferredQuotation(Request $request): bool
    {
        return (string) $request->input('is_deffered', '0') === '1';
    }

    private function getQuotationReferenceNumber(Request $request): ?string
    {
        return $request->input('po_so_number', $request->input('order_number'));
    }

    private function buildQuotationItemPayload(Request $request, int $index): array
    {
        $isDeferred = $this->isDeferredQuotation($request);
        $invoiceCategory = $isDeferred ? $request->input('invoice_category') : null;

        return [
            'product_id'   => $request->product_id[$index],
            'product_name' => $request->product_name[$index],
            'description'  => $request->description[$index] ?? null,
            'quantity'     => $request->quantity[$index],
            'unit_cost'    => $request->unit_cost[$index],
            'sub_total'    => ($request->unit_cost[$index] * $request->quantity[$index]),
            'benefits'     => $isDeferred ? ($request->benefits[$index] ?? null) : null,
            'family_size'  => $isDeferred && $invoiceCategory === 'medical' ? ($request->family_size[$index] ?? null) : null,
            'sum_insured'  => $isDeferred && $invoiceCategory === 'other'
                ? (($request->input("sum_insured.$index") !== null && $request->input("sum_insured.$index") !== '') ? $request->input("sum_insured.$index") : 0)
                : 0,
        ];
    }

    private function buildDeferredEarningsSchedule(Quotation $quotation): array
    {
        $decimalPlace = (int) get_business_option('decimal_place', 2);
        $startDate = Carbon::parse($quotation->getRawOriginal('quotation_date'))->startOfDay();
        $endDate = Carbon::parse($quotation->getRawOriginal('expired_date'))->startOfDay();

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        $totalDays = max($startDate->diffInDays($endDate) + 1, 1);
        $factor = (int) pow(10, $decimalPlace);
        $subTotalUnits = (int) round(((float) $quotation->getRawOriginal('sub_total')) * $factor);
        $unitsPerDay = intdiv($subTotalUnits, $totalDays);

        $scheduleUnits = [];
        $cursor = $startDate->copy();
        $usedUnits = 0;

        while ($cursor->lte($endDate)) {
            $sliceStart = $cursor->copy();
            $sliceEnd = $cursor->copy()->endOfMonth();

            if ($sliceEnd->gt($endDate)) {
                $sliceEnd = $endDate->copy();
            }

            $days = $sliceStart->diffInDays($sliceEnd) + 1;
            $sliceUnits = $unitsPerDay * $days;

            $scheduleUnits[] = [
                'start_date' => $sliceStart->format('Y-m-d'),
                'end_date' => $sliceEnd->format('Y-m-d'),
                'number_of_days' => $days,
                'slice_units' => $sliceUnits,
            ];

            $usedUnits += $sliceUnits;
            $cursor = $sliceEnd->copy()->addDay()->startOfDay();
        }

        $remainder = $subTotalUnits - $usedUnits;

        if (!empty($scheduleUnits) && $remainder !== 0) {
            $scheduleUnits[array_key_last($scheduleUnits)]['slice_units'] += $remainder;
        }

        $schedule = array_map(function ($entry) use ($quotation, $factor, $decimalPlace) {
            return [
                'start_date' => $entry['start_date'],
                'end_date' => $entry['end_date'],
                'number_of_days' => $entry['number_of_days'],
                'currency' => $quotation->currency,
                'exchange_rate' => $quotation->exchange_rate,
                'transaction_amount' => round($entry['slice_units'] / $factor, $decimalPlace),
            ];
        }, $scheduleUnits);

        $costPerDay = $totalDays > 0 ? round(($subTotalUnits / $factor) / $totalDays, $decimalPlace) : 0;

        return [
            'schedule' => $schedule,
            'total_days' => $totalDays,
            'cost_per_day' => $costPerDay,
        ];
    }

    private function createNormalInvoiceFromQuotation(Request $request, Quotation $quotation): Invoice
    {
        $this->ensureDefaultAccounts($request);

        $invoice = new Invoice();
        $invoice->customer_id = $quotation->customer_id;
        $invoice->title = $quotation->title ?: get_business_option('invoice_title', 'Invoice');
        $invoice->invoice_number = get_business_option('invoice_number', '100001');
        $invoice->order_number = $quotation->po_so_number;
        $invoice->invoice_date = Carbon::today()->format('Y-m-d');
        $invoice->due_date = Carbon::today()->format('Y-m-d');
        $invoice->sub_total = $quotation->getRawOriginal('sub_total');
        $invoice->grand_total = $quotation->getRawOriginal('grand_total');
        $invoice->currency = $quotation->currency;
        $invoice->converted_total = $quotation->converted_total;
        $invoice->exchange_rate = $quotation->exchange_rate;
        $invoice->paid = 0;
        $invoice->discount = $quotation->getRawOriginal('discount');
        $invoice->discount_type = $quotation->discount_type;
        $invoice->discount_value = $quotation->discount_value;
        $invoice->template_type = $quotation->template_type;
        $invoice->template = $quotation->template;
        $invoice->note = $quotation->note;
        $invoice->footer = $quotation->footer;
        $invoice->short_code = rand(100000, 9999999) . uniqid();
        $invoice->save();

        $currentTime = Carbon::now();
        $transactionDate = Carbon::parse($invoice->getRawOriginal('invoice_date'))
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');

        foreach ($quotation->items as $item) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id' => $invoice->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_cost' => $item->getRawOriginal('unit_cost'),
                'sub_total' => $item->getRawOriginal('sub_total'),
            ]));

            $product = Product::find($item->product_id);

            if (!$product) {
                throw new \RuntimeException(_lang('Selected product is not available!'));
            }

            if ($product->allow_for_selling == 1) {
                $transaction = new Transaction();
                $transaction->trans_date = $transactionDate;
                $transaction->account_id = $product->income_account_id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoiceItem->getRawOriginal('sub_total')));
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoiceItem->getRawOriginal('sub_total'));
                $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                $transaction->ref_id = $invoice->id;
                $transaction->ref_type = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->stock_management == 1) {
                $transaction = new Transaction();
                $transaction->trans_date = $transactionDate;
                $transaction->account_id = get_account('Inventory')->id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoiceItem->quantity;
                $transaction->ref_id = $invoice->id;
                $transaction->ref_type = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction = new Transaction();
                $transaction->trans_date = $transactionDate;
                $transaction->account_id = $product->expense_account_id;
                $transaction->dr_cr = 'dr';
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type = 'invoice';
                $transaction->ref_id = $invoice->id;
                $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            foreach ($item->taxes as $quotationItemTax) {
                $tax = Tax::find($quotationItemTax->tax_id);

                if (!$tax) {
                    continue;
                }

                $taxAmount = $quotationItemTax->getRawOriginal('amount');

                $invoiceItem->taxes()->save(new InvoiceItemTax([
                    'invoice_id' => $invoice->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => $taxAmount,
                ]));

                $transaction = new Transaction();
                $transaction->trans_date = $transactionDate;
                $transaction->account_id = $tax->account_id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $taxAmount));
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $taxAmount);
                $transaction->description = _lang('Credit Invoice Tax') . ' #' . $invoice->invoice_number;
                $transaction->ref_id = $invoice->id;
                $transaction->ref_type = 'invoice tax';
                $transaction->tax_id = $tax->id;
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->type == 'product' && $product->stock_management == 1) {
                if ($product->stock < $item->quantity) {
                    throw new \RuntimeException($product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $item->quantity;
                $product->save();
            }
        }

        BusinessSetting::where('name', 'invoice_number')->increment('value');

        $transaction = new Transaction();
        $transaction->trans_date = $transactionDate;
        $transaction->account_id = get_account('Accounts Receivable')->id;
        $transaction->dr_cr = 'dr';
        $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('grand_total'));
        $transaction->transaction_currency = $invoice->currency;
        $transaction->currency_rate = $invoice->exchange_rate;
        $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('grand_total')));
        $transaction->ref_id = $invoice->id;
        $transaction->ref_type = 'invoice';
        $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
        $transaction->customer_id = $invoice->customer_id;
        $transaction->save();

        if ((float) ($quotation->discount_value ?? 0) > 0) {
            $transaction = new Transaction();
            $transaction->trans_date = $transactionDate;
            $transaction->account_id = get_account('Sales Discount Allowed')->id;
            $transaction->dr_cr = 'dr';
            $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('discount'));
            $transaction->transaction_currency = $invoice->currency;
            $transaction->currency_rate = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('discount')));
            $transaction->description = _lang('Credit Invoice Discount') . ' #' . $invoice->invoice_number;
            $transaction->ref_id = $invoice->id;
            $transaction->ref_type = 'invoice';
            $transaction->customer_id = $invoice->customer_id;
            $transaction->save();
        }

        return $invoice;
    }

    private function createDeferredInvoiceFromQuotation(Request $request, Quotation $quotation): Invoice
    {
        $this->ensureDefaultAccounts($request, true);

        $earnings = $this->buildDeferredEarningsSchedule($quotation);

        $invoice = new Invoice();
        $invoice->customer_id = $quotation->customer_id;
        $invoice->title = $quotation->title ?: get_business_option('invoice_title', 'Invoice');
        $invoice->invoice_number = get_business_option('invoice_number', '100001');
        $invoice->order_number = $quotation->po_so_number;
        $invoice->invoice_date = Carbon::today()->format('Y-m-d');
        $invoice->due_date = Carbon::today()->format('Y-m-d');
        $invoice->sub_total = $quotation->getRawOriginal('sub_total');
        $invoice->grand_total = $quotation->getRawOriginal('grand_total');
        $invoice->currency = $quotation->currency;
        $invoice->converted_total = $quotation->converted_total;
        $invoice->exchange_rate = $quotation->exchange_rate;
        $invoice->paid = 0;
        $invoice->discount = $quotation->getRawOriginal('discount');
        $invoice->discount_type = $quotation->discount_type;
        $invoice->discount_value = $quotation->discount_value;
        $invoice->template_type = $quotation->template_type;
        $invoice->template = $quotation->template;
        $invoice->note = $quotation->note;
        $invoice->footer = $quotation->footer;
        $invoice->short_code = rand(100000, 9999999) . uniqid();
        $invoice->is_deffered = 1;
        $invoice->invoice_category = $quotation->invoice_category;
        $invoice->deffered_start = $quotation->getRawOriginal('quotation_date');
        $invoice->deffered_end = $quotation->getRawOriginal('expired_date');
        $invoice->active_days = $earnings['total_days'];
        $invoice->cost_per_day = $earnings['cost_per_day'];
        $invoice->save();

        foreach ($earnings['schedule'] as $earning) {
            $defferedEarning = new DefferedEarning();
            $defferedEarning->invoice_id = $invoice->id;
            $defferedEarning->start_date = $earning['start_date'];
            $defferedEarning->end_date = $earning['end_date'];
            $defferedEarning->days = $earning['number_of_days'];
            $defferedEarning->currency = $earning['currency'];
            $defferedEarning->exchange_rate = $earning['exchange_rate'];
            $defferedEarning->base_currency_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $earning['transaction_amount']);
            $defferedEarning->transaction_amount = $earning['transaction_amount'];
            $defferedEarning->save();
        }

        $currentTime = Carbon::now();
        $transactionDate = Carbon::parse($invoice->getRawOriginal('invoice_date'))
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');

        foreach ($quotation->items as $item) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id' => $invoice->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'description' => $item->description,
                'sum_insured' => $item->getRawOriginal('sum_insured'),
                'benefits' => $item->benefits,
                'family_size' => $item->family_size,
                'quantity' => $item->quantity,
                'unit_cost' => $item->getRawOriginal('unit_cost'),
                'sub_total' => $item->getRawOriginal('sub_total'),
            ]));

            $product = Product::find($item->product_id);

            if (!$product) {
                throw new \RuntimeException(_lang('Selected product is not available!'));
            }

            if ($product->stock_management == 1) {
                $transaction = new Transaction();
                $transaction->trans_date = $transactionDate;
                $transaction->account_id = get_account('Inventory')->id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoiceItem->quantity;
                $transaction->ref_id = $invoice->id;
                $transaction->ref_type = 'd invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction = new Transaction();
                $transaction->trans_date = $transactionDate;
                $transaction->account_id = $product->expense_account_id;
                $transaction->dr_cr = 'dr';
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type = 'd invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->ref_id = $invoice->id;
                $transaction->description = 'Deffered Invoice #' . $invoice->invoice_number;
                $transaction->save();
            }

            foreach ($item->taxes as $quotationItemTax) {
                $tax = Tax::find($quotationItemTax->tax_id);

                if (!$tax) {
                    continue;
                }

                $taxAmount = $quotationItemTax->getRawOriginal('amount');

                $invoiceItem->taxes()->save(new InvoiceItemTax([
                    'invoice_id' => $invoice->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => $taxAmount,
                ]));

                $transaction = new Transaction();
                $transaction->trans_date = $transactionDate;
                $transaction->account_id = $tax->account_id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $invoice->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $taxAmount));
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $taxAmount);
                $transaction->description = _lang('Deffered Invoice Tax') . ' #' . $invoice->invoice_number;
                $transaction->ref_id = $invoice->id;
                $transaction->ref_type = 'd invoice tax';
                $transaction->tax_id = $tax->id;
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->type == 'product' && $product->stock_management == 1) {
                if ($product->stock < $item->quantity) {
                    throw new \RuntimeException($product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $item->quantity;
                $product->save();
            }
        }

        BusinessSetting::where('name', 'invoice_number')->increment('value');

        $transaction = new Transaction();
        $transaction->trans_date = $transactionDate;
        $transaction->account_id = get_account('Unearned Revenue')->id;
        $transaction->dr_cr = 'cr';
        $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('sub_total'));
        $transaction->transaction_currency = $invoice->currency;
        $transaction->currency_rate = $invoice->exchange_rate;
        $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('sub_total')));
        $transaction->description = _lang('Deffered Invoice Liability') . ' #' . $invoice->invoice_number;
        $transaction->ref_id = $invoice->id;
        $transaction->ref_type = 'd invoice';
        $transaction->customer_id = $invoice->customer_id;
        $transaction->save();

        $transaction = new Transaction();
        $transaction->trans_date = $transactionDate;
        $transaction->account_id = get_account('Accounts Receivable')->id;
        $transaction->dr_cr = 'dr';
        $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('grand_total'));
        $transaction->transaction_currency = $invoice->currency;
        $transaction->currency_rate = $invoice->exchange_rate;
        $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('grand_total')));
        $transaction->ref_id = $invoice->id;
        $transaction->ref_type = 'd invoice';
        $transaction->customer_id = $invoice->customer_id;
        $transaction->description = 'Deffered Invoice #' . $invoice->invoice_number;
        $transaction->save();

        if ((float) ($quotation->discount_value ?? 0) > 0) {
            $transaction = new Transaction();
            $transaction->trans_date = $transactionDate;
            $transaction->account_id = get_account('Sales Discount Allowed')->id;
            $transaction->dr_cr = 'dr';
            $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('discount'));
            $transaction->transaction_currency = $invoice->currency;
            $transaction->currency_rate = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice->getRawOriginal('discount')));
            $transaction->description = _lang('Deffered Invoice Discount') . ' #' . $invoice->invoice_number;
            $transaction->ref_id = $invoice->id;
            $transaction->ref_type = 'd invoice';
            $transaction->customer_id = $invoice->customer_id;
            $transaction->save();
        }

        return $invoice;
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
}
