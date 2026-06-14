<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCategory;
use App\Models\InsuranceCategorySection;
use App\Models\Product;
use App\Models\RatingRule;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class UnderwritingConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::whereIn('name', [
            'cert_number_prefix',
            'cert_number_increment',
            'policy_number_prefix',
            'policy_number_increment',
            'invoice_primary_color',
            'invoice_text_color',
        ])->pluck('value', 'name');

        // ── Insurance Categories ──────────────────────────────────────
        $certSearch  = $request->get('cert_search', '');
        $certPerPage = $request->get('cert_per_page', 10);
        $certSorting = $request->get('cert_sorting', []);
        $certSortCol = $certSorting['column']    ?? 'name';
        $certSortDir = $certSorting['direction'] ?? 'asc';

        $catQuery = InsuranceCategory::query();
        if (!empty($certSearch)) {
            $catQuery->where(function ($q) use ($certSearch) {
                $q->where('name', 'like', "%{$certSearch}%")
                  ->orWhere('slug', 'like', "%{$certSearch}%");
            });
        }
        $catQuery->orderBy($certSortCol, $certSortDir);
        $insuranceCategories = $catQuery->paginate($certPerPage)->withQueryString();

        // ── Rating Rules ──────────────────────────────────────────────
        $ruleSearch  = $request->get('rule_search', '');
        $rulePerPage = $request->get('rule_per_page', 10);
        $ruleSorting = $request->get('rule_sorting', []);
        $ruleSortCol = $ruleSorting['column']    ?? 'name';
        $ruleSortDir = $ruleSorting['direction'] ?? 'asc';

        $ruleQuery = RatingRule::with('insuranceCategory:id,name', 'product:id,name');
        if (!empty($ruleSearch)) {
            $ruleQuery->where(function ($q) use ($ruleSearch) {
                $q->where('name', 'like', "%{$ruleSearch}%");
            });
        }
        $ruleQuery->orderBy($ruleSortCol, $ruleSortDir);
        $ratingRules = $ruleQuery->paginate($rulePerPage)->withQueryString();

        return Inertia::render('Backend/User/UnderwritingConfiguration/List', [
            'settings'              => $settings,
            'insuranceCategories'   => $insuranceCategories->items(),
            'certMeta'              => [
                'current_page' => $insuranceCategories->currentPage(),
                'last_page'    => $insuranceCategories->lastPage(),
                'per_page'     => $insuranceCategories->perPage(),
                'total'        => $insuranceCategories->total(),
            ],
            'certFilters'           => ['search' => $certSearch, 'sorting' => $certSorting],
            'certTrashedCount'      => InsuranceCategory::onlyTrashed()->count(),
            'ratingRules'           => $ratingRules->items(),
            'ratingRulesMeta'       => [
                'current_page' => $ratingRules->currentPage(),
                'last_page'    => $ratingRules->lastPage(),
                'per_page'     => $ratingRules->perPage(),
                'total'        => $ratingRules->total(),
            ],
            'ratingRulesFilters'    => ['search' => $ruleSearch, 'sorting' => $ruleSorting],
            'allInsuranceCategories' => InsuranceCategory::select('id', 'name', 'type')->orderBy('name')->get(),
            'allProducts'           => Product::select('id', 'name')->orderBy('name')->get(),
            'defaultTab'            => $request->get('tab', 'policy_certificate'),
        ]);
    }

    public function insuranceCategoriesTrash(Request $request)
    {
        $search  = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $sorting = $request->get('sorting', []);
        $sortCol = $sorting['column']    ?? 'name';
        $sortDir = $sorting['direction'] ?? 'asc';

        $query = InsuranceCategory::onlyTrashed();
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        $query->orderBy($sortCol, $sortDir);
        $items = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Backend/User/UnderwritingConfiguration/InsuranceCategoriesTrash', [
            'insuranceCategories' => $items->items(),
            'meta'                => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
            'filters' => ['search' => $search, 'sorting' => $sorting],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cert_number_prefix'      => 'nullable|string|max:50',
            'cert_number_increment'   => 'required|integer|min:1',
            'policy_number_prefix'    => 'nullable|string|max:50',
            'policy_number_increment' => 'required|integer|min:1',
            'invoice_primary_color'   => 'nullable|string|max:7',
            'invoice_text_color'      => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        foreach (['cert_number_prefix', 'cert_number_increment', 'policy_number_prefix', 'policy_number_increment', 'invoice_primary_color', 'invoice_text_color'] as $key) {
            Setting::updateOrCreate(
                ['name' => $key],
                ['value' => $request->input($key) ?? '']
            );
        }

        return back()->with('success', _lang('Settings saved successfully'));
    }

    // ── Insurance Category Layout ─────────────────────────────────────

    public function insuranceCategoryLayout($id)
    {
        $insuranceCategory = InsuranceCategory::findOrFail($id);

        $mapSection = fn($s) => [
            'id'         => $s->id,
            'title'      => $s->title,
            'type'       => $s->type,
            'sort_order' => $s->sort_order,
            'fields'     => $s->fields_json  ?? [['label' => '', 'default_value' => '']],
            'columns'    => $s->columns_json ?? [''],
            'rows'       => $s->rows_json    ?? [['']],
            'content'    => $s->content      ?? '',
        ];

        $certSections = InsuranceCategorySection::where('insurance_category_id', $id)
            ->where('purpose', 'certificate')
            ->orderBy('sort_order')
            ->get()
            ->map($mapSection)
            ->values();

        $quotSections = InsuranceCategorySection::where('insurance_category_id', $id)
            ->where('purpose', 'quotation')
            ->orderBy('sort_order')
            ->get()
            ->map($mapSection)
            ->values();

        return Inertia::render('Backend/User/UnderwritingConfiguration/InsuranceCategoryLayout', [
            'insuranceCategory' => ['id' => $insuranceCategory->id, 'name' => $insuranceCategory->name, 'type' => $insuranceCategory->type],
            'certSections'      => $certSections,
            'quotSections'      => $quotSections,
        ]);
    }

    public function saveInsuranceCategoryLayout(Request $request, $id)
    {
        $insuranceCategory = InsuranceCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sections'         => 'nullable|array',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.type'  => 'required|string|in:fields,table,text,note,terms,exclusions,signature',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        InsuranceCategorySection::where('insurance_category_id', $id)
            ->where('purpose', 'certificate')
            ->delete();

        foreach ($request->input('sections', []) as $order => $s) {
            InsuranceCategorySection::create([
                'insurance_category_id' => $insuranceCategory->id,
                'purpose'               => 'certificate',
                'title'                 => $s['title'],
                'type'                  => $s['type'],
                'sort_order'            => $order,
                'fields_json'           => $s['fields']  ?? null,
                'columns_json'          => $s['columns'] ?? null,
                'rows_json'             => $s['rows']    ?? null,
                'content'               => $s['content'] ?? null,
            ]);
        }

        return back()->with('success', _lang('Layout saved successfully'));
    }

    public function saveQuotationTemplateSections(Request $request, $id)
    {
        $insuranceCategory = InsuranceCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sections'         => 'nullable|array',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.type'  => 'required|string|in:fields,table,benefits,remarks,rich_text,calculation,premium_summary',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        InsuranceCategorySection::where('insurance_category_id', $id)
            ->where('purpose', 'quotation')
            ->delete();

        foreach ($request->input('sections', []) as $order => $s) {
            InsuranceCategorySection::create([
                'insurance_category_id' => $insuranceCategory->id,
                'purpose'               => 'quotation',
                'title'                 => $s['title'],
                'type'                  => $s['type'],
                'sort_order'            => $order,
                'fields_json'           => $s['fields']  ?? null,
                'columns_json'          => $s['columns'] ?? null,
                'rows_json'             => $s['rows']    ?? null,
                'content'               => $s['content'] ?? null,
            ]);
        }

        return back()->with('success', _lang('Quotation sections saved successfully'));
    }

    // ── Insurance Category CRUD ───────────────────────────────────────

    public function storeInsuranceCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|size:3|unique:insurance_categories,slug|regex:/^[A-Z]{3}$/',
            'type' => 'required|in:medical,gpa,other',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        InsuranceCategory::create([
            'name' => $request->name,
            'slug' => strtoupper($request->slug),
            'type' => $request->type,
        ]);

        return back()->with('success', _lang('Insurance category created successfully'));
    }

    public function updateInsuranceCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|size:3|unique:insurance_categories,slug,' . $id . '|regex:/^[A-Z]{3}$/',
            'type' => 'required|in:medical,gpa,other',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        InsuranceCategory::findOrFail($id)->update([
            'name' => $request->name,
            'slug' => strtoupper($request->slug),
            'type' => $request->type,
        ]);

        return back()->with('success', _lang('Insurance category updated successfully'));
    }

    public function destroyInsuranceCategory($id)
    {
        InsuranceCategory::findOrFail($id)->delete();
        return back()->with('success', _lang('Insurance category deleted successfully'));
    }

    public function bulkDestroyInsuranceCategory(Request $request)
    {
        InsuranceCategory::whereIn('id', $request->ids)->delete();
        return back()->with('success', _lang('Insurance categories deleted successfully'));
    }

    public function restoreInsuranceCategory($id)
    {
        InsuranceCategory::onlyTrashed()->findOrFail($id)->restore();
        return back()->with('success', _lang('Insurance category restored successfully'));
    }

    public function bulkRestoreInsuranceCategory(Request $request)
    {
        InsuranceCategory::onlyTrashed()->whereIn('id', $request->ids)->restore();
        return back()->with('success', _lang('Insurance categories restored successfully'));
    }

    public function permanentDestroyInsuranceCategory($id)
    {
        InsuranceCategory::onlyTrashed()->findOrFail($id)->forceDelete();
        return back()->with('success', _lang('Permanently deleted successfully'));
    }

    public function bulkPermanentDestroyInsuranceCategory(Request $request)
    {
        InsuranceCategory::onlyTrashed()->whereIn('id', $request->ids)->forceDelete();
        return back()->with('success', _lang('Permanently deleted successfully'));
    }

    // ── Rating Rules CRUD ─────────────────────────────────────────────

    public function storeRatingRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'insurance_category_id' => 'required|exists:insurance_categories,id',
            'product_id'            => 'nullable|exists:products,id',
            'name'                  => 'required|string|max:191',
            'calculation_type'      => 'required|in:percentage_of_amount,fixed_per_quantity,fixed_amount,manual_premium,tiered_rate,contribution_table',
            'rate_type'             => 'required|in:percentage,fixed,manual,range',
            'min_rate'              => 'nullable|numeric|min:0',
            'max_rate'              => 'nullable|numeric|min:0',
            'default_rate'          => 'nullable|numeric|min:0',
            'minimum_premium'       => 'nullable|numeric|min:0',
            'tax_rate'              => 'nullable|numeric|min:0',
            'currency'              => 'nullable|string|max:55',
            'active_from'           => 'nullable|date',
            'active_to'             => 'nullable|date|after_or_equal:active_from',
            'is_active'             => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        RatingRule::create($request->only([
            'insurance_category_id', 'product_id', 'name',
            'calculation_type', 'rate_type',
            'min_rate', 'max_rate', 'default_rate',
            'minimum_premium', 'tax_rate', 'currency',
            'active_from', 'active_to', 'is_active',
        ]));

        return back()->with('success', _lang('Rating rule created successfully'));
    }

    public function updateRatingRule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'insurance_category_id' => 'required|exists:insurance_categories,id',
            'product_id'            => 'nullable|exists:products,id',
            'name'                  => 'required|string|max:191',
            'calculation_type'      => 'required|in:percentage_of_amount,fixed_per_quantity,fixed_amount,manual_premium,tiered_rate,contribution_table',
            'rate_type'             => 'required|in:percentage,fixed,manual,range',
            'min_rate'              => 'nullable|numeric|min:0',
            'max_rate'              => 'nullable|numeric|min:0',
            'default_rate'          => 'nullable|numeric|min:0',
            'minimum_premium'       => 'nullable|numeric|min:0',
            'tax_rate'              => 'nullable|numeric|min:0',
            'currency'              => 'nullable|string|max:55',
            'active_from'           => 'nullable|date',
            'active_to'             => 'nullable|date|after_or_equal:active_from',
            'is_active'             => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        RatingRule::findOrFail($id)->update($request->only([
            'insurance_category_id', 'product_id', 'name',
            'calculation_type', 'rate_type',
            'min_rate', 'max_rate', 'default_rate',
            'minimum_premium', 'tax_rate', 'currency',
            'active_from', 'active_to', 'is_active',
        ]));

        return back()->with('success', _lang('Rating rule updated successfully'));
    }

    public function destroyRatingRule($id)
    {
        RatingRule::findOrFail($id)->delete();
        return back()->with('success', _lang('Rating rule deleted successfully'));
    }

    public function bulkDestroyRatingRule(Request $request)
    {
        RatingRule::whereIn('id', $request->ids)->delete();
        return back()->with('success', _lang('Rating rules deleted successfully'));
    }
}
