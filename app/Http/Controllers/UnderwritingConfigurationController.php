<?php

namespace App\Http\Controllers;

use App\Models\CertificateType;
use App\Models\CertificateTypeSection;
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

        $certSearch    = $request->get('cert_search', '');
        $certPerPage   = $request->get('cert_per_page', 10);
        $certSorting   = $request->get('cert_sorting', []);
        $certSortCol   = $certSorting['column']    ?? 'name';
        $certSortDir   = $certSorting['direction'] ?? 'asc';

        $certQuery = CertificateType::query();
        if (!empty($certSearch)) {
            $certQuery->where(function ($q) use ($certSearch) {
                $q->where('name', 'like', "%{$certSearch}%")
                    ->orWhere('slug', 'like', "%{$certSearch}%");
            });
        }
        $certQuery->orderBy($certSortCol, $certSortDir);
        $certificateTypes = $certQuery->paginate($certPerPage)->withQueryString();

        return Inertia::render('Backend/User/UnderwritingConfiguration/List', [
            'settings'         => $settings,
            'certificateTypes' => $certificateTypes->items(),
            'certMeta'         => [
                'current_page' => $certificateTypes->currentPage(),
                'last_page'    => $certificateTypes->lastPage(),
                'per_page'     => $certificateTypes->perPage(),
                'total'        => $certificateTypes->total(),
            ],
            'certFilters'      => [
                'search'  => $certSearch,
                'sorting' => $certSorting,
            ],
            'certTrashedCount' => CertificateType::onlyTrashed()->count(),
            'defaultTab'       => $request->get('tab', 'policy_certificate'),
        ]);
    }

    public function certTypesTrash(Request $request)
    {
        $search    = $request->get('search', '');
        $perPage   = $request->get('per_page', 10);
        $sorting   = $request->get('sorting', []);
        $sortCol   = $sorting['column']    ?? 'name';
        $sortDir   = $sorting['direction'] ?? 'asc';

        $query = CertificateType::onlyTrashed();
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        $query->orderBy($sortCol, $sortDir);
        $types = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Backend/User/UnderwritingConfiguration/CertTypesTrash', [
            'certificateTypes' => $types->items(),
            'meta'             => [
                'current_page' => $types->currentPage(),
                'last_page'    => $types->lastPage(),
                'per_page'     => $types->perPage(),
                'total'        => $types->total(),
            ],
            'filters'          => ['search' => $search, 'sorting' => $sorting],
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

    public function typeLayout($id)
    {
        $type = CertificateType::with('templateSections')->findOrFail($id);

        $sections = $type->templateSections->map(function ($s) {
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

        return Inertia::render('Backend/User/UnderwritingConfiguration/CertificateTypeLayout', [
            'certificateType' => ['id' => $type->id, 'name' => $type->name],
            'sections'        => $sections,
        ]);
    }

    public function saveTypeLayout(Request $request, $id)
    {
        $type = CertificateType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sections'          => 'nullable|array',
            'sections.*.title'  => 'required|string|max:255',
            'sections.*.type'   => 'required|string|in:fields,table,text,note,terms,exclusions,signature',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        CertificateTypeSection::where('certificate_type_id', $id)->delete();

        foreach ($request->input('sections', []) as $order => $s) {
            CertificateTypeSection::create([
                'certificate_type_id' => $type->id,
                'title'               => $s['title'],
                'type'                => $s['type'],
                'sort_order'          => $order,
                'fields_json'         => $s['fields']  ?? null,
                'columns_json'        => $s['columns'] ?? null,
                'rows_json'           => $s['rows']    ?? null,
                'content'             => $s['content'] ?? null,
            ]);
        }

        return back()->with('success', _lang('Layout saved successfully'));
    }

    public function storeCertificateType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|size:3|unique:certificate_types,slug|regex:/^[A-Z]{3}$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        CertificateType::create([
            'name' => $request->name,
            'slug' => strtoupper($request->slug),
        ]);

        return back()->with('success', _lang('Certificate type created successfully'));
    }

    public function updateCertificateType(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|size:3|unique:certificate_types,slug,' . $id . '|regex:/^[A-Z]{3}$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        CertificateType::findOrFail($id)->update([
            'name' => $request->name,
            'slug' => strtoupper($request->slug),
        ]);

        return back()->with('success', _lang('Certificate type updated successfully'));
    }

    public function destroyCertificateType($id)
    {
        CertificateType::findOrFail($id)->delete();
        return back()->with('success', _lang('Certificate type deleted successfully'));
    }

    public function bulkDestroyCertificateType(Request $request)
    {
        CertificateType::whereIn('id', $request->ids)->delete();
        return back()->with('success', _lang('Certificate types deleted successfully'));
    }

    public function restoreCertificateType($id)
    {
        CertificateType::onlyTrashed()->findOrFail($id)->restore();
        return back()->with('success', _lang('Certificate type restored successfully'));
    }

    public function bulkRestoreCertificateType(Request $request)
    {
        CertificateType::onlyTrashed()->whereIn('id', $request->ids)->restore();
        return back()->with('success', _lang('Certificate types restored successfully'));
    }

    public function permanentDestroyCertificateType($id)
    {
        CertificateType::onlyTrashed()->findOrFail($id)->forceDelete();
        return back()->with('success', _lang('Permanently deleted successfully'));
    }

    public function bulkPermanentDestroyCertificateType(Request $request)
    {
        CertificateType::onlyTrashed()->whereIn('id', $request->ids)->forceDelete();
        return back()->with('success', _lang('Permanently deleted successfully'));
    }
}
