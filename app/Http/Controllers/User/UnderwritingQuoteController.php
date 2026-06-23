<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\DefferedEarning;
use App\Models\EmailTemplate;
use App\Models\InsuranceCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\InsuranceFamilySize;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationItemTax;
use App\Models\QuotationSection;
use App\Models\RatingRule;
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
use function Spatie\LaravelPdf\Support\pdf;

class UnderwritingQuoteController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $route_name = request()->route()->getName();

            if (in_array($route_name, ['underwriting_quotes.store', 'underwriting_quotes.duplicate'])) {
                if (has_limit('quotations', 'quotation_limit', false) <= 0) {
                    return $request->ajax()
                        ? response()->json(['result' => 'error', 'message' => _lang('Sorry, Your have already reached your package quota !')])
                        : back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
                }
            }

            if ($route_name === 'underwriting_quotes.convert_to_invoice') {
                if (has_limit('invoices', 'invoice_limit', false) <= 0) {
                    return $request->ajax()
                        ? response()->json(['result' => 'error', 'message' => _lang('Sorry, Your have already reached your package quota !')])
                        : back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
                }
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        Gate::authorize('quotations.view');

        $query      = $this->buildQuery($request, ['customer', 'insuranceCategory']);
        $allQuotes  = $this->buildQuery($request)->get();

        $summary = [
            'total_quotations'   => $allQuotes->count(),
            'grand_total'        => $allQuotes->sum('grand_total'),
            'active_quotations'  => $allQuotes->filter(fn($q) => Carbon::parse($q->getRawOriginal('expired_date'))->greaterThanOrEqualTo(Carbon::today()))->count(),
            'expired_quotations' => $allQuotes->filter(fn($q) => Carbon::parse($q->getRawOriginal('expired_date'))->lessThan(Carbon::today()))->count(),
        ];

        $sorting = $this->resolveSorting($request);
        $this->applySorting($query, $sorting);

        $perPage = $request->get('per_page', 50);
        $quotes  = $query->paginate($perPage);

        return Inertia::render('Backend/User/UnderwritingQuotes/List', [
            'quotations' => $quotes->items(),
            'meta' => [
                'total'        => $quotes->total(),
                'per_page'     => $quotes->perPage(),
                'current_page' => $quotes->currentPage(),
                'last_page'    => $quotes->lastPage(),
                'from'         => $quotes->firstItem(),
                'to'           => $quotes->lastItem(),
            ],
            'filters'            => array_merge($request->all(), ['sorting' => $sorting]),
            'customers'          => Customer::all(),
            'insuranceCategories' => InsuranceCategory::select('id', 'name')->orderBy('name')->get(),
            'summary'            => $summary,
            'trashed_quotations' => Quotation::onlyTrashed()->where('is_deffered', 1)->count(),
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('quotations.view');

        $query = $this->buildQuery($request, ['customer'], true);
        $sorting = $this->resolveSorting($request);
        $this->applySorting($query, $sorting);

        $perPage = $request->get('per_page', 50);
        $quotes  = $query->paginate($perPage);

        return Inertia::render('Backend/User/UnderwritingQuotes/Trash', [
            'quotations' => $quotes->items(),
            'meta' => [
                'total'        => $quotes->total(),
                'per_page'     => $quotes->perPage(),
                'current_page' => $quotes->currentPage(),
                'last_page'    => $quotes->lastPage(),
                'from'         => $quotes->firstItem(),
                'to'           => $quotes->lastItem(),
            ],
            'filters'   => array_merge($request->all(), ['sorting' => $sorting]),
            'customers' => Customer::all(),
        ]);
    }

    public function create()
    {
        Gate::authorize('quotations.create');

        return Inertia::render('Backend/User/UnderwritingQuotes/Create', [
            'customers'           => Customer::all(),
            'currencies'          => Currency::all(),
            'products'            => Product::all(),
            'taxes'               => Tax::all(),
            'quotation_title'     => get_business_option('quotation_title', 'Quotation'),
            'base_currency'       => get_business_option('currency'),
            'insuranceCategories' => $this->getInsuranceCategoriesWithSections(),
            'ratingRules'         => $this->getActiveRatingRules(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('quotations.create');

        $validator = Validator::make($request->all(), [
            'customer_id'           => 'required',
            'title'                 => 'required',
            'quotation_date'        => 'required',
            'expired_date'          => 'required',
            'product_id'            => 'required',
            'currency'              => 'required|exists:currency,name',
            'insurance_category_id' => 'required|exists:insurance_categories,id',
            'sections'              => 'nullable|array',
            'sections.*.title'      => 'required|string|max:255',
            'sections.*.type'       => 'required|string|in:fields,table,benefits,remarks,rich_text,calculation,premium_summary,text,note,terms,exclusions,signature',
            'rating_rule_id'         => 'nullable|array',
            'rating_rule_id.*'       => 'nullable|exists:rating_rules,id',
            'calculation_type'       => 'nullable|array',
            'calculation_type.*'     => 'nullable|in:percentage_of_amount,fixed_per_quantity,fixed_amount,manual_premium,tiered_rate,contribution_table',
            'rate_type'              => 'nullable|array',
            'rate_type.*'            => 'nullable|in:percentage,fixed,manual,range',
            'rate_value'             => 'nullable|array',
            'rate_value.*'           => 'nullable|numeric|min:0',
            'basis_amount'           => 'nullable|array',
            'basis_amount.*'         => 'nullable|numeric|min:0',
            'basis_quantity'         => 'nullable|array',
            'basis_quantity.*'       => 'nullable|numeric|min:0',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('underwriting_quotes.create')->withErrors($validator)->withInput();
        }

        if (in_array(null, $request->quantity) || in_array('', $request->quantity) || in_array(0, $request->quantity)) {
            return redirect()->back()->withInput()->with('error', _lang('Quantity is required'));
        }

        if (in_array(null, $request->unit_cost) || in_array('', $request->unit_cost)) {
            return redirect()->back()->withInput()->with('error', _lang('Unit Cost is required'));
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $quotation                       = new Quotation();
        $quotation->customer_id          = $request->input('customer_id');
        $quotation->title                = $request->input('title');
        $quotation->quotation_number     = BusinessSetting::where('name', 'quotation_number')->first()->value;
        $quotation->quotation_date       = Carbon::parse($request->input('quotation_date'))->format('Y-m-d');
        $quotation->expired_date         = Carbon::parse($request->input('expired_date'))->format('Y-m-d');
        $quotation->sub_total            = $summary['subTotal'];
        $quotation->grand_total          = $summary['grandTotal'];
        $quotation->currency             = $request['currency'];
        $quotation->converted_total      = $request->input('converted_total');
        $quotation->exchange_rate        = Currency::where('name', $request->currency)->value('exchange_rate') ?? 1;
        $quotation->discount             = $summary['discountAmount'];
        $quotation->discount_type        = $request->input('discount_type');
        $quotation->discount_value       = $request->input('discount_value') ?? 0;
        $quotation->template_type        = is_numeric($request->template) ? 1 : 0;
        $quotation->template             = $request->input('template') ?? 'default';
        $quotation->note                 = $request->input('note');
        $quotation->footer               = $request->input('footer');
        $quotation->insurance_category_id = $request->input('insurance_category_id');
        $quotation->is_deffered          = 1;
        $quotation->invoice_category     = $request->input('invoice_category', 'other');
        $quotation->short_code           = rand(100000, 9999999) . uniqid();
        $quotation->save();

        $this->saveQuotationSections($quotation, $request);

        for ($i = 0; $i < count($request->product_id); $i++) {
            $quotationItem = $quotation->items()->save(new QuotationItem(
                array_merge(['quotation_id' => $quotation->id], $this->buildItemPayload($request, $i))
            ));

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);
                    $quotationItem->taxes()->save(new QuotationItemTax([
                        'quotation_id' => $quotation->id,
                        'tax_id'       => $taxId,
                        'name'         => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'       => ($quotationItem->sub_total / 100) * $tax->rate,
                    ]));
                }
            }
        }

        BusinessSetting::where('name', 'quotation_number')->increment('value');
        DB::commit();

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Underwriting Quote Created ' . $quotation->quotation_number;
        $audit->save();

        return redirect()->route('underwriting_quotes.show', $quotation->id)->with('success', _lang('Saved Successfully'));
    }

    public function show(Request $request, $id)
    {
        Gate::authorize('quotations.view');

        $quotation       = Quotation::where('is_deffered', 1)->with(['business.systemSettings', 'items', 'customer', 'taxes', 'sections', 'insuranceCategory'])->findOrFail($id);
        $this->hydrateQuotationItemMetadata($quotation);
        $email_templates = EmailTemplate::whereIn('slug', ['NEW_QUOTATION_CREATED'])->where('email_status', 1)->get();
        $decimalPlace    = get_business_option('decimal_place', 2);

        return Inertia::render('Backend/User/UnderwritingQuotes/View', [
            'quotation'       => $quotation,
            'email_templates' => $email_templates,
            'decimalPlace'    => $decimalPlace,
        ]);
    }

    public function edit(Request $request, $id)
    {
        Gate::authorize('quotations.update');

        $quotation = Quotation::where('is_deffered', 1)->with(['items', 'sections'])->findOrFail($id);
        $taxIds    = $quotation->taxes->pluck('tax_id')->map(fn($id) => (string) $id)->toArray();

        return Inertia::render('Backend/User/UnderwritingQuotes/Edit', [
            'quotation'           => $quotation,
            'customers'           => Customer::all(),
            'currencies'          => Currency::all(),
            'products'            => Product::all(),
            'taxes'               => Tax::all(),
            'taxIds'              => $taxIds,
            'insuranceCategories' => $this->getInsuranceCategoriesWithSections(),
            'ratingRules'         => $this->getActiveRatingRules(),
        ]);
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('quotations.update');

        $validator = Validator::make($request->all(), [
            'customer_id'           => 'required',
            'title'                 => 'required',
            'quotation_date'        => 'required',
            'expired_date'          => 'required',
            'product_id'            => 'required',
            'currency'              => 'required|exists:currency,name',
            'insurance_category_id' => 'required|exists:insurance_categories,id',
            'sections'              => 'nullable|array',
            'sections.*.title'      => 'required|string|max:255',
            'sections.*.type'       => 'required|string|in:fields,table,benefits,remarks,rich_text,calculation,premium_summary,text,note,terms,exclusions,signature',
            'rating_rule_id'         => 'nullable|array',
            'rating_rule_id.*'       => 'nullable|exists:rating_rules,id',
            'calculation_type'       => 'nullable|array',
            'calculation_type.*'     => 'nullable|in:percentage_of_amount,fixed_per_quantity,fixed_amount,manual_premium,tiered_rate,contribution_table',
            'rate_type'              => 'nullable|array',
            'rate_type.*'            => 'nullable|in:percentage,fixed,manual,range',
            'rate_value'             => 'nullable|array',
            'rate_value.*'           => 'nullable|numeric|min:0',
            'basis_amount'           => 'nullable|array',
            'basis_amount.*'         => 'nullable|numeric|min:0',
            'basis_quantity'         => 'nullable|array',
            'basis_quantity.*'       => 'nullable|numeric|min:0',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('underwriting_quotes.edit', $id)->withErrors($validator)->withInput();
        }

        if (in_array(null, $request->quantity) || in_array('', $request->quantity) || in_array(0, $request->quantity)) {
            return redirect()->back()->withInput()->with('error', _lang('Quantity is required'));
        }

        if (in_array(null, $request->unit_cost) || in_array('', $request->unit_cost)) {
            return redirect()->back()->withInput()->with('error', _lang('Unit Cost is required'));
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $quotation                       = Quotation::where('is_deffered', 1)->findOrFail($id);
        $quotation->customer_id          = $request->input('customer_id');
        $quotation->title                = $request->input('title');
        $quotation->quotation_date       = Carbon::parse($request->input('quotation_date'))->format('Y-m-d');
        $quotation->expired_date         = Carbon::parse($request->input('expired_date'))->format('Y-m-d');
        $quotation->sub_total            = $summary['subTotal'];
        $quotation->grand_total          = $summary['grandTotal'];
        $quotation->currency             = $request['currency'];
        $quotation->converted_total      = $request->input('converted_total');
        $quotation->exchange_rate        = Currency::where('name', $request->currency)->value('exchange_rate') ?? 1;
        $quotation->discount             = $summary['discountAmount'];
        $quotation->discount_type        = $request->input('discount_type');
        $quotation->discount_value       = $request->input('discount_value') ?? 0;
        $quotation->template_type        = is_numeric($request->template) ? 1 : 0;
        $quotation->template             = $request->input('template') ?? 'default';
        $quotation->note                 = $request->input('note');
        $quotation->footer               = $request->input('footer');
        $quotation->insurance_category_id = $request->input('insurance_category_id');
        $quotation->is_deffered          = 1;
        $quotation->invoice_category     = $request->input('invoice_category', $quotation->invoice_category ?: 'other');
        $quotation->save();

        QuotationItemTax::where('quotation_id', $quotation->id)->delete();
        $quotation->items()->delete();
        $quotation->sections()->delete();
        $this->saveQuotationSections($quotation, $request);

        for ($i = 0; $i < count($request->product_id); $i++) {
            $quotationItem = $quotation->items()->save(new QuotationItem(
                array_merge(['quotation_id' => $quotation->id], $this->buildItemPayload($request, $i))
            ));

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);
                    $quotationItem->taxes()->save(new QuotationItemTax([
                        'quotation_id' => $quotation->id,
                        'tax_id'       => $taxId,
                        'name'         => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'       => ($quotationItem->sub_total / 100) * $tax->rate,
                    ]));
                }
            }
        }

        DB::commit();

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Underwriting Quote Updated ' . $quotation->quotation_number;
        $audit->save();

        return redirect()->route('underwriting_quotes.show', $quotation->id)->with('success', _lang('Updated Successfully'));
    }

    public function duplicate($id)
    {
        Gate::authorize('quotations.duplicate');

        DB::beginTransaction();
        $quotation                    = Quotation::where('is_deffered', 1)->with(['items.taxes', 'sections'])->findOrFail($id);
        $newQuotation                 = $quotation->replicate();
        $newQuotation->quotation_number = get_business_option('quotation_number', rand());
        $newQuotation->short_code     = rand(100000, 9999999) . uniqid();
        $newQuotation->save();

        foreach ($quotation->items as $item) {
            $newItem               = $item->replicate();
            $newItem->quotation_id = $newQuotation->id;
            $newItem->save();

            foreach ($item->taxes as $tax) {
                $newTax                    = $tax->replicate();
                $newTax->quotation_id      = $newQuotation->id;
                $newTax->quotation_item_id = $newItem->id;
                $newTax->save();
            }
        }

        foreach ($quotation->sections as $section) {
            $newSection               = $section->replicate();
            $newSection->quotation_id = $newQuotation->id;
            $newSection->save();
        }

        BusinessSetting::where('name', 'quotation_number')->increment('value');
        DB::commit();

        return redirect()->route('underwriting_quotes.edit', $newQuotation->id);
    }

    public function accept($id)
    {
        Gate::authorize('quotations.update');

        $quotation = Quotation::where('is_deffered', 1)->findOrFail($id);

        if ((int) ($quotation->status ?? 0) !== 0) {
            return back()->with('error', _lang('Only pending quotations can be accepted.'));
        }

        $quotation->status = 1;
        $quotation->save();

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Underwriting Quote Accepted ' . $quotation->quotation_number;
        $audit->save();

        return redirect()->route('underwriting_quotes.index')->with('success', _lang('Accepted Successfully'));
    }

    public function convert_to_invoice(Request $request, $id)
    {
        Gate::authorize('quotations.convert_to_invoice');

        $quotation = Quotation::where('is_deffered', 1)->with(['items.taxes'])->findOrFail($id);
        $status    = (int) ($quotation->status ?? 0);

        if ($status !== 1) {
            return back()->with('error', _lang('Only accepted quotations can be converted to an invoice.'));
        }

        if (package()->deffered_invoice != 1) {
            return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
        }

        DB::beginTransaction();

        try {
            $invoice = $this->createDeferredInvoice($request, $quotation);

            $quotation->status = 3;
            $quotation->save();

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Underwriting Quote Converted to Deferred Invoice ' . $quotation->quotation_number . ' -> ' . $invoice->invoice_number;
            $audit->save();

            DB::commit();

            return redirect()->route('deffered_invoices.edit', $invoice->id)->with('success', _lang('Quotation converted successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
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

        $quotation       = Quotation::where('is_deffered', 1)->findOrFail($id);
        $customer        = $quotation->customer;
        $customer->email = $request->email;

        try {
            Notification::send($customer, new SendQuotation($quotation, ['subject' => $request->subject, 'message' => $request->message], $request->template));
            return redirect()->back()->with('success', _lang('Email has been sent'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function pdf($id)
    {
        Gate::authorize('quotations.pdf');

        $quotation = Quotation::where('is_deffered', 1)->with(['business', 'items', 'taxes', 'customer', 'sections', 'insuranceCategory'])->findOrFail($id);
        $this->hydrateQuotationItemMetadata($quotation);
        return pdf()
            ->view('backend.user.pdf.quotation', compact('quotation'))
            ->name('quotation-' . $quotation->quotation_number . '.pdf')
            ->download();
    }

    public function reject($id)
    {
        Gate::authorize('quotations.update');

        $quotation = Quotation::where('is_deffered', 1)->findOrFail($id);
        $status    = (int) ($quotation->status ?? 0);

        if ($status === 1) {
            return back()->with('error', _lang('Accepted quotations cannot be rejected.'));
        }

        if ($status === 2) {
            return back()->with('error', _lang('This quotation has already been rejected.'));
        }

        if ($status === 3) {
            return back()->with('error', _lang('Converted quotations cannot be rejected.'));
        }

        $quotation->status = 2;
        $quotation->save();

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Underwriting Quote Rejected ' . $quotation->quotation_number;
        $audit->save();

        return redirect()->route('underwriting_quotes.index')->with('success', _lang('Rejected Successfully'));
    }

    public function destroy($id)
    {
        Gate::authorize('quotations.delete');

        $quotation = Quotation::where('is_deffered', 1)->findOrFail($id);

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Underwriting Quote Deleted ' . $quotation->quotation_number;
        $audit->save();

        $quotation->delete();
        return redirect()->route('underwriting_quotes.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        Gate::authorize('quotations.delete');

        foreach ($request->ids as $id) {
            $quotation = Quotation::where('is_deffered', 1)->find($id);
            if (!$quotation) continue;

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Underwriting Quote Deleted ' . $quotation->quotation_number;
            $audit->save();

            $quotation->delete();
        }

        return redirect()->route('underwriting_quotes.index')->with('success', _lang('Deleted Successfully'));
    }

    public function permanent_destroy($id)
    {
        Gate::authorize('quotations.delete');

        $quotation = Quotation::onlyTrashed()->where('is_deffered', 1)->findOrFail($id);

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Underwriting Quote Permanently Deleted ' . $quotation->quotation_number;
        $audit->save();

        $quotation->forceDelete();
        return redirect()->route('underwriting_quotes.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('quotations.delete');

        foreach ($request->ids as $id) {
            $quotation = Quotation::onlyTrashed()->where('is_deffered', 1)->find($id);
            if (!$quotation) continue;

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Underwriting Quote Permanently Deleted ' . $quotation->quotation_number;
            $audit->save();

            $quotation->forceDelete();
        }

        return redirect()->route('underwriting_quotes.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function restore($id)
    {
        Gate::authorize('quotations.restore');

        $quotation = Quotation::onlyTrashed()->where('is_deffered', 1)->findOrFail($id);

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Underwriting Quote Restored ' . $quotation->quotation_number;
        $audit->save();

        $quotation->restore();
        return redirect()->route('underwriting_quotes.trash')->with('success', _lang('Restored Successfully'));
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('quotations.restore');

        foreach ($request->ids as $id) {
            $quotation = Quotation::onlyTrashed()->where('is_deffered', 1)->find($id);
            if (!$quotation) continue;

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Underwriting Quote Restored ' . $quotation->quotation_number;
            $audit->save();

            $quotation->restore();
        }

        return redirect()->route('underwriting_quotes.trash')->with('success', _lang('Restored Successfully'));
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function buildQuery(Request $request, array $relations = [], bool $onlyTrashed = false)
    {
        $query = ($onlyTrashed ? Quotation::onlyTrashed() : Quotation::query())->where('is_deffered', 1);

        if (!empty($relations)) {
            $query->with($relations);
        }

        $this->applyFilters($query, $request);
        return $query;
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->has('customer_id') && $request->customer_id !== '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('invoice_category') && $request->invoice_category !== '') {
            $query->where('invoice_category', $request->invoice_category);
        }

        if ($request->has('insurance_category_id') && $request->insurance_category_id !== '') {
            $query->where('insurance_category_id', $request->insurance_category_id);
        }

        $dateRange = $request->input('date_range');
        if (is_array($dateRange) && count($dateRange) >= 2 && $dateRange[0] && $dateRange[1]) {
            $query->whereDate('quotation_date', '>=', $dateRange[0])
                ->whereDate('quotation_date', '<=', $dateRange[1]);
        }

        if ($request->has('status') && $request->status !== '') {
            $this->applyStatusFilter($query, (string) $request->status);
        }
    }

    private function resolveSorting(Request $request): array
    {
        $sorting  = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);
        $allowed  = ['id', 'quotation_number', 'invoice_category', 'quotation_date', 'expired_date', 'grand_total', 'customer.name', 'insuranceCategory.name'];
        $column   = in_array($sorting['column'] ?? 'id', $allowed, true) ? ($sorting['column'] ?? 'id') : 'id';
        $direction = strtolower((string) ($sorting['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        return ['column' => $column, 'direction' => $direction];
    }

    private function applySorting($query, array $sorting): void
    {
        if ($sorting['column'] === 'customer.name') {
            $query->join('customers', 'quotations.customer_id', '=', 'customers.id')
                ->orderBy('customers.name', $sorting['direction'])
                ->select('quotations.*');
            return;
        }
        if ($sorting['column'] === 'insuranceCategory.name') {
            $query->leftJoin('insurance_categories', 'quotations.insurance_category_id', '=', 'insurance_categories.id')
                ->orderBy('insurance_categories.name', $sorting['direction'])
                ->select('quotations.*');
            return;
        }
        $query->orderBy('quotations.' . $sorting['column'], $sorting['direction']);
    }

    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            '0' => $query->where('status', 0)->whereDate('expired_date', '>=', Carbon::today()),
            '1' => $query->where('status', 0)->whereDate('expired_date', '<', Carbon::today()),
            '2' => $query->where('status', 1),
            '3' => $query->where('status', 2),
            '4' => $query->where('status', 3),
            default => null,
        };
    }

    private function hydrateQuotationItemMetadata(Quotation $quotation): void
    {
        foreach ($quotation->items as $item) {
            $metadata          = $item->metadata_json ?? [];
            $item->sum_insured = $item->basis_amount ?? ($metadata['basis_amount'] ?? ($metadata['sum_insured'] ?? 0));
        }
    }

    private function saveQuotationSections(Quotation $quotation, Request $request): void
    {
        foreach ($request->input('sections', []) as $sortOrder => $sectionData) {
            QuotationSection::create([
                'quotation_id'                   => $quotation->id,
                'insurance_category_id'          => $request->input('insurance_category_id'),
                'insurance_category_section_id'  => $sectionData['insurance_category_section_id'] ?? $sectionData['template_section_id'] ?? null,
                'title'                          => $sectionData['title'],
                'type'                           => $sectionData['type'],
                'sort_order'                     => $sectionData['sort_order'] ?? $sortOrder,
                'data_json'                      => [
                    'fields'  => $sectionData['fields'] ?? [],
                    'columns' => $sectionData['columns'] ?? [],
                    'rows'    => $sectionData['rows'] ?? [],
                ],
                'content'                        => $sectionData['content'] ?? null,
            ]);
        }
    }

    private function buildItemPayload(Request $request, int $index): array
    {
        $ratingRule      = $this->resolveRatingRule($request, $index);
        $calculationType = $this->resolveCalculationType($request, $index, $ratingRule);
        $rateType        = $this->resolveRateType($request, $index, $calculationType, $ratingRule);
        $quantity        = $this->normalizeDecimal($request->input("quantity.$index")) ?? 0;
        $basisQuantity   = $this->normalizeDecimal($request->input("basis_quantity.$index")) ?? ($quantity > 0 ? $quantity : 1);
        $basisAmount     = $this->normalizeDecimal($request->input("basis_amount.$index")) ?? 0;
        $rateValue       = $this->resolveRateValue($request, $index, $ratingRule);
        $lineTotal       = $this->calculateFinancialLineTotal($calculationType, $rateValue, $basisAmount, $basisQuantity);
        $unitCost        = $this->resolveLineUnitCost($calculationType, $rateValue, $lineTotal);

        $metadata = array_filter([
            'rating_rule_name' => $ratingRule?->name,
        ], fn($value) => $value !== null && $value !== '');

        if ($basisAmount > 0) {
            $metadata['basis_amount'] = $basisAmount;
        }

        return [
            'insurance_category_id' => $request->input('insurance_category_id'),
            'rating_rule_id'        => $ratingRule?->id,
            'product_id'            => $request->product_id[$index],
            'product_name'          => $request->product_name[$index],
            'description'           => $request->description[$index] ?? null,
            'calculation_type'      => $calculationType,
            'rate_type'             => $rateType,
            'rate_value'            => $rateValue,
            'basis_amount'          => $basisAmount,
            'basis_quantity'        => $basisQuantity,
            'quantity'              => $quantity,
            'unit_cost'             => $unitCost,
            'sub_total'             => $lineTotal,
            'metadata_json'         => $metadata ?: null,
        ];
    }

    private function resolveRatingRule(Request $request, int $index): ?RatingRule
    {
        $ruleId = $request->input("rating_rule_id.$index");
        if (!$ruleId) return null;

        return RatingRule::whereKey($ruleId)
            ->where('insurance_category_id', $request->input('insurance_category_id'))
            ->first();
    }

    private function resolveCalculationType(Request $request, int $index, ?RatingRule $ratingRule = null): string
    {
        return $request->input("calculation_type.$index")
            ?: $ratingRule?->calculation_type
            ?: 'manual_premium';
    }

    private function resolveRateType(Request $request, int $index, string $calculationType, ?RatingRule $ratingRule = null): string
    {
        return $request->input("rate_type.$index")
            ?: $ratingRule?->rate_type
            ?: $this->defaultRateTypeForCalculation($calculationType);
    }

    private function defaultRateTypeForCalculation(string $calculationType): string
    {
        return match ($calculationType) {
            'percentage_of_amount' => 'percentage',
            'tiered_rate'          => 'range',
            'fixed_per_quantity',
            'fixed_amount'         => 'fixed',
            default                => 'manual',
        };
    }

    private function resolveRateValue(Request $request, int $index, ?RatingRule $ratingRule = null): float
    {
        return $this->normalizeDecimal($request->input("rate_value.$index"))
            ?? $this->normalizeDecimal($request->input("unit_cost.$index"))
            ?? $this->normalizeDecimal($ratingRule?->default_rate)
            ?? 0.0;
    }

    private function normalizeDecimal($value): ?float
    {
        if ($value === null || $value === '') return null;
        return is_numeric($value) ? (float) $value : null;
    }

    private function calculateFinancialLineTotal(string $calculationType, float $rateValue, float $basisAmount, float $basisQuantity): float
    {
        return match ($calculationType) {
            'percentage_of_amount' => ($basisAmount * $rateValue) / 100,
            'fixed_per_quantity'   => $rateValue * $basisQuantity,
            'fixed_amount',
            'manual_premium',
            'contribution_table',
            'tiered_rate'          => $rateValue,
            default                => $rateValue,
        };
    }

    private function resolveLineUnitCost(string $calculationType, float $rateValue, float $lineTotal): float
    {
        return $calculationType === 'fixed_per_quantity' ? $rateValue : $lineTotal;
    }

    private function calculateTotal(Request $request): array
    {
        $subTotal = $taxAmount = $discountAmount = 0;

        for ($i = 0; $i < count($request->product_id); $i++) {
            $ratingRule      = $this->resolveRatingRule($request, $i);
            $calculationType = $this->resolveCalculationType($request, $i, $ratingRule);
            $rateValue       = $this->resolveRateValue($request, $i, $ratingRule);
            $quantity        = $this->normalizeDecimal($request->input("quantity.$i")) ?? 0;
            $basisAmount     = $this->normalizeDecimal($request->input("basis_amount.$i")) ?? 0;
            $basisQuantity   = $this->normalizeDecimal($request->input("basis_quantity.$i")) ?? ($quantity > 0 ? $quantity : 1);
            $lineTotal = $this->calculateFinancialLineTotal($calculationType, $rateValue, $basisAmount, $basisQuantity);
            $subTotal += $lineTotal;

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax       = Tax::find($taxId);
                    $taxAmount += ($lineTotal / 100) * $tax->rate;
                }
            }
        }

        if ($request->discount_type == '0') {
            $discountAmount = ($subTotal / 100) * ($request->discount_value ?? 0);
        } elseif ($request->discount_type == '1') {
            $discountAmount = $request->discount_value ?? 0;
        }

        $grandTotal = ($subTotal + $taxAmount) - $discountAmount;
        $rate       = Currency::where('name', $request->currency)->value('exchange_rate') ?? 1;

        return [
            'subTotal'       => $subTotal / $rate,
            'taxAmount'      => $taxAmount / $rate,
            'discountAmount' => $discountAmount / $rate,
            'grandTotal'     => $grandTotal / $rate,
        ];
    }

    private function ensureDefaultAccounts(Request $request): void
    {
        $accounts = [
            'Accounts Receivable'   => ['1100', 'Other Current Asset', 'dr'],
            'Sales Tax Payable'     => ['2200', 'Current Liability', 'cr'],
            'Sales Discount Allowed'=> ['4009', 'Other Income', 'dr'],
            'Inventory'             => ['1000', 'Other Current Asset', 'dr'],
            'Unearned Revenue'      => ['2300', 'Current Liability', 'cr'],
        ];

        foreach ($accounts as $name => [$code, $type, $drCr]) {
            if (Account::where('account_name', $name)->where('business_id', $request->activeBusiness->id)->exists()) continue;

            $account               = new Account();
            $account->account_code = $code;
            $account->account_type = $type;
            $account->dr_cr        = $drCr;
            $account->account_name = $name;
            $account->business_id  = $request->activeBusiness->id;
            $account->user_id      = $request->activeBusiness->user->id;
            $account->opening_date = now()->format('Y-m-d');
            $account->save();
        }
    }

    private function buildDeferredEarningsSchedule(Quotation $quotation): array
    {
        $decimalPlace   = (int) get_business_option('decimal_place', 2);
        $startDate      = Carbon::parse($quotation->getRawOriginal('quotation_date'))->startOfDay();
        $endDate        = Carbon::parse($quotation->getRawOriginal('expired_date'))->startOfDay();

        if ($endDate->lt($startDate)) $endDate = $startDate->copy();

        $totalDays      = max($startDate->diffInDays($endDate) + 1, 1);
        $factor         = (int) pow(10, $decimalPlace);
        $subTotalUnits  = (int) round(((float) $quotation->getRawOriginal('sub_total')) * $factor);
        $unitsPerDay    = intdiv($subTotalUnits, $totalDays);

        $scheduleUnits  = [];
        $cursor         = $startDate->copy();
        $usedUnits      = 0;

        while ($cursor->lte($endDate)) {
            $sliceStart = $cursor->copy();
            $sliceEnd   = $cursor->copy()->endOfMonth();
            if ($sliceEnd->gt($endDate)) $sliceEnd = $endDate->copy();

            $days          = $sliceStart->diffInDays($sliceEnd) + 1;
            $sliceUnits    = $unitsPerDay * $days;
            $scheduleUnits[] = ['start_date' => $sliceStart->format('Y-m-d'), 'end_date' => $sliceEnd->format('Y-m-d'), 'number_of_days' => $days, 'slice_units' => $sliceUnits];
            $usedUnits    += $sliceUnits;
            $cursor        = $sliceEnd->copy()->addDay()->startOfDay();
        }

        $remainder = $subTotalUnits - $usedUnits;
        if (!empty($scheduleUnits) && $remainder !== 0) {
            $scheduleUnits[array_key_last($scheduleUnits)]['slice_units'] += $remainder;
        }

        $schedule    = array_map(fn($e) => [
            'start_date'         => $e['start_date'],
            'end_date'           => $e['end_date'],
            'number_of_days'     => $e['number_of_days'],
            'currency'           => $quotation->currency,
            'exchange_rate'      => $quotation->exchange_rate,
            'transaction_amount' => round($e['slice_units'] / $factor, $decimalPlace),
        ], $scheduleUnits);

        $costPerDay  = $totalDays > 0 ? round(($subTotalUnits / $factor) / $totalDays, $decimalPlace) : 0;

        return ['schedule' => $schedule, 'total_days' => $totalDays, 'cost_per_day' => $costPerDay];
    }

    private function createDeferredInvoice(Request $request, Quotation $quotation): Invoice
    {
        $this->ensureDefaultAccounts($request);
        $earnings = $this->buildDeferredEarningsSchedule($quotation);

        $invoice                   = new Invoice();
        $invoice->customer_id      = $quotation->customer_id;
        $invoice->title            = $quotation->title ?: get_business_option('invoice_title', 'Invoice');
        $invoice->invoice_number   = get_business_option('invoice_number', '100001');
        $invoice->order_number     = $quotation->po_so_number;
        $invoice->invoice_date     = Carbon::today()->format('Y-m-d');
        $invoice->due_date         = Carbon::today()->format('Y-m-d');
        $invoice->sub_total        = $quotation->getRawOriginal('sub_total');
        $invoice->grand_total      = $quotation->getRawOriginal('grand_total');
        $invoice->currency         = $quotation->currency;
        $invoice->converted_total  = $quotation->converted_total;
        $invoice->exchange_rate    = $quotation->exchange_rate;
        $invoice->paid             = 0;
        $invoice->discount         = $quotation->getRawOriginal('discount');
        $invoice->discount_type    = $quotation->discount_type;
        $invoice->discount_value   = $quotation->discount_value;
        $invoice->template_type    = $quotation->template_type;
        $invoice->template         = $quotation->template;
        $invoice->note             = $quotation->note;
        $invoice->footer           = $quotation->footer;
        $invoice->short_code       = rand(100000, 9999999) . uniqid();
        $invoice->is_deffered      = 1;
        $invoice->invoice_category = $quotation->invoice_category;
        $invoice->deffered_start   = $quotation->getRawOriginal('quotation_date');
        $invoice->deffered_end     = $quotation->getRawOriginal('expired_date');
        $invoice->active_days      = $earnings['total_days'];
        $invoice->cost_per_day     = $earnings['cost_per_day'];
        $invoice->save();

        foreach ($earnings['schedule'] as $earning) {
            $de                       = new DefferedEarning();
            $de->invoice_id           = $invoice->id;
            $de->start_date           = $earning['start_date'];
            $de->end_date             = $earning['end_date'];
            $de->days                 = $earning['number_of_days'];
            $de->currency             = $earning['currency'];
            $de->exchange_rate        = $earning['exchange_rate'];
            $de->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, $earning['transaction_amount']);
            $de->transaction_amount   = $earning['transaction_amount'];
            $de->save();
        }

        $currentTime     = Carbon::now();
        $transactionDate = Carbon::parse($invoice->getRawOriginal('invoice_date'))
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');

        foreach ($quotation->items as $item) {
            $metadata = $item->metadata_json ?? [];
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product_name,
                'description'  => $item->description,
                'sum_insured'  => $item->basis_amount ?? ($metadata['basis_amount'] ?? ($metadata['sum_insured'] ?? 0)),
                'benefits'     => $metadata['benefits'] ?? null,
                'family_size'  => $metadata['family_size'] ?? null,
                'quantity'     => $item->quantity,
                'unit_cost'    => $item->getRawOriginal('unit_cost'),
                'sub_total'    => $item->getRawOriginal('sub_total'),
            ]));

            $product = Product::find($item->product_id);
            if (!$product) throw new \RuntimeException(_lang('Selected product is not available!'));

            if ($product->stock_management == 1) {
                $this->recordTransaction($transactionDate, get_account('Inventory')->id, 'cr', $invoice, $product->purchase_cost * $invoiceItem->quantity, 'd invoice', $invoiceItem->product_name . ' Sales #' . $invoiceItem->quantity, $request);
            }

            if ($product->allow_for_purchasing == 1) {
                $this->recordTransaction($transactionDate, $product->expense_account_id, 'dr', $invoice, $product->purchase_cost * $invoiceItem->quantity, 'd invoice', 'Deffered Invoice #' . $invoice->invoice_number, $request);
            }

            foreach ($item->taxes as $quotationItemTax) {
                $tax = Tax::find($quotationItemTax->tax_id);
                if (!$tax) continue;

                $taxAmount = $quotationItemTax->getRawOriginal('amount');
                $invoiceItem->taxes()->save(new InvoiceItemTax([
                    'invoice_id' => $invoice->id,
                    'tax_id'     => $tax->id,
                    'name'       => $tax->name . ' ' . $tax->rate . ' %',
                    'amount'     => $taxAmount,
                ]));
                $this->recordTransaction($transactionDate, $tax->account_id, 'cr', $invoice, $taxAmount, 'd invoice tax', _lang('Deffered Invoice Tax') . ' #' . $invoice->invoice_number, $request, $tax->id);
            }

            if ($product->type == 'product' && $product->stock_management == 1) {
                if ($product->stock < $item->quantity) throw new \RuntimeException($product->name . ' ' . _lang('Stock is not available!'));
                $product->stock -= $item->quantity;
                $product->save();
            }
        }

        BusinessSetting::where('name', 'invoice_number')->increment('value');

        $this->recordTransaction($transactionDate, get_account('Unearned Revenue')->id, 'cr', $invoice, $invoice->getRawOriginal('sub_total'), 'd invoice', _lang('Deffered Invoice Liability') . ' #' . $invoice->invoice_number, $request);
        $this->recordTransaction($transactionDate, get_account('Accounts Receivable')->id, 'dr', $invoice, $invoice->getRawOriginal('grand_total'), 'd invoice', 'Deffered Invoice #' . $invoice->invoice_number, $request);

        if ((float) ($quotation->discount_value ?? 0) > 0) {
            $this->recordTransaction($transactionDate, get_account('Sales Discount Allowed')->id, 'dr', $invoice, $invoice->getRawOriginal('discount'), 'd invoice', _lang('Deffered Invoice Discount') . ' #' . $invoice->invoice_number, $request);
        }

        return $invoice;
    }

    private function getInsuranceCategoriesWithSections(): \Illuminate\Support\Collection
    {
        return InsuranceCategory::with('quotationSections')->orderBy('name')->get()
            ->map(fn($c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'sections' => $c->quotationSections->map(fn($s) => [
                    'id'         => $s->id,
                    'title'      => $s->title,
                    'type'       => $s->type,
                    'sort_order' => $s->sort_order,
                    'fields'     => $s->fields_json  ?? [['label' => '', 'default_value' => '']],
                    'columns'    => $s->columns_json ?? [''],
                    'rows'       => $s->rows_json    ?? [['']],
                    'content'    => $s->content      ?? '',
                ])->values(),
            ]);
    }

    private function getActiveRatingRules(): \Illuminate\Support\Collection
    {
        $today = Carbon::today()->toDateString();

        return RatingRule::where('is_active', 1)
            ->where(function ($query) use ($today) {
                $query->whereNull('active_from')->orWhereDate('active_from', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('active_to')->orWhereDate('active_to', '>=', $today);
            })
            ->orderBy('name')
            ->get()
            ->map(fn($rule) => [
                'id'                    => $rule->id,
                'insurance_category_id' => $rule->insurance_category_id,
                'product_id'            => $rule->product_id,
                'name'                  => $rule->name,
                'calculation_type'      => $rule->calculation_type,
                'rate_type'             => $rule->rate_type,
                'default_rate'          => $rule->default_rate,
                'min_rate'              => $rule->min_rate,
                'max_rate'              => $rule->max_rate,
                'tax_rate'              => $rule->tax_rate,
                'currency'              => $rule->currency,
                'metadata_json'         => $rule->metadata_json,
            ]);
    }

    private function recordTransaction(string $date, int $accountId, string $drCr, Invoice $invoice, float $amount, string $refType, string $description, Request $request, ?int $taxId = null): void
    {
        $t                        = new Transaction();
        $t->trans_date            = $date;
        $t->account_id            = $accountId;
        $t->dr_cr                 = $drCr;
        $t->transaction_currency  = $invoice->currency;
        $t->currency_rate         = $invoice->exchange_rate;
        $t->transaction_amount    = convert_currency($request->activeBusiness->currency, $invoice->currency, $amount);
        $t->base_currency_amount  = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $amount));
        $t->description           = $description;
        $t->ref_id                = $invoice->id;
        $t->ref_type              = $refType;
        $t->customer_id           = $invoice->customer_id;
        if ($taxId !== null) $t->tax_id = $taxId;
        $t->save();
    }
}
