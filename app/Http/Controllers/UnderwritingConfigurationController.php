<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCategory;
use App\Models\InsuranceCategorySection;
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

        $search    = $request->get('cert_search', '');
        $perPage   = $request->get('cert_per_page', 10);
        $sorting   = $request->get('cert_sorting', []);
        $sortCol   = $sorting['column']    ?? 'name';
        $sortDir   = $sorting['direction'] ?? 'asc';

        $query = InsuranceCategory::query();
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        $query->orderBy($sortCol, $sortDir);
        $insuranceCategories = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Backend/User/UnderwritingConfiguration/List', [
            'settings'                   => $settings,
            'insuranceCategories'        => $insuranceCategories->items(),
            'certMeta'                   => [
                'current_page' => $insuranceCategories->currentPage(),
                'last_page'    => $insuranceCategories->lastPage(),
                'per_page'     => $insuranceCategories->perPage(),
                'total'        => $insuranceCategories->total(),
            ],
            'certFilters'                => [
                'search'  => $search,
                'sorting' => $sorting,
            ],
            'certTrashedCount'           => InsuranceCategory::onlyTrashed()->count(),
            'defaultTab'                 => $request->get('tab', 'policy_certificate'),
        ]);
    }

    public function insuranceCategoriesTrash(Request $request)
    {
        $search    = $request->get('search', '');
        $perPage   = $request->get('per_page', 10);
        $sorting   = $request->get('sorting', []);
        $sortCol   = $sorting['column']    ?? 'name';
        $sortDir   = $sorting['direction'] ?? 'asc';

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
            'filters'             => ['search' => $search, 'sorting' => $sorting],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cert_number_prefix'    => 'nullable|string|max:50',
            'cert_number_increment' => 'required|integer|min:1',
            'policy_number_prefix'    => 'nullable|string|max:50',
            'policy_number_increment' => 'required|integer|min:1',
            'invoice_primary_color' => 'nullable|string|max:7',
            'invoice_text_color'    => 'nullable|string|max:7',
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

    public function insuranceCategoryLayout($id)
    {
        $insuranceCategory = InsuranceCategory::with('templateSections')->findOrFail($id);

        $sections = $insuranceCategory->templateSections->map(function ($s) {
            return [
                'id'         => $s->id,
                'title'      => $s->title,
                'type'       => $s->type,
                'sort_order' => $s->sort_order,
                'fields'     => $s->fields_json  ?? [['label' => '', 'default_value' => '']],
                'columns'    => $s->columns_json ?? [''],
                'rows'       => $s->rows_json    ?? [['']],
                'content'    => $s->content      ?? '',
            ];
        })->values();

        return Inertia::render('Backend/User/UnderwritingConfiguration/InsuranceCategoryLayout', [
            'insuranceCategory' => ['id' => $insuranceCategory->id, 'name' => $insuranceCategory->name],
            'sections'          => $sections,
        ]);
    }

    public function saveInsuranceCategoryLayout(Request $request, $id)
    {
        $insuranceCategory = InsuranceCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sections'          => 'nullable|array',
            'sections.*.title'  => 'required|string|max:255',
            'sections.*.type'   => 'required|string|in:fields,table,text,note,terms,exclusions,signature',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        InsuranceCategorySection::where('insurance_category_id', $id)->delete();

        foreach ($request->input('sections', []) as $order => $s) {
            InsuranceCategorySection::create([
                'insurance_category_id' => $insuranceCategory->id,
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

    public function storeInsuranceCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|size:3|unique:insurance_categories,slug|regex:/^[A-Z]{3}$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        InsuranceCategory::create([
            'name' => $request->name,
            'slug' => strtoupper($request->slug),
        ]);

        return back()->with('success', _lang('Insurance category created successfully'));
    }

    public function updateInsuranceCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|size:3|unique:insurance_categories,slug,' . $id . '|regex:/^[A-Z]{3}$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        InsuranceCategory::findOrFail($id)->update([
            'name' => $request->name,
            'slug' => strtoupper($request->slug),
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
}
