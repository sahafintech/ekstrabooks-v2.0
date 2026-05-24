<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CertificateType;
use App\Models\Customer;
use App\Models\PolicyCertificate;
use App\Models\PolicyCertificateField;
use App\Models\PolicyCertificateSection;
use App\Models\PolicyCertificateSectionRow;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PolicyCertificateController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('policy_certificates.view');

        $per_page = $request->get('per_page', 50);
        $search   = $request->get('search', '');
        $sorting  = $request->get('sorting', []);

        $query = PolicyCertificate::with(['customer', 'certificateType']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhere('policy_number', 'like', "%{$search}%")
                    ->orWhereHas('certificateType', fn($r) => $r->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }

        $query->orderBy($sorting['column'] ?? 'id', $sorting['direction'] ?? 'desc');

        $certificates = $query->paginate($per_page)->withQueryString();

        return Inertia::render('Backend/User/PolicyCertificate/List', [
            'certificates' => $certificates->items(),
            'meta' => [
                'current_page' => $certificates->currentPage(),
                'from'         => $certificates->firstItem(),
                'last_page'    => $certificates->lastPage(),
                'links'        => $certificates->linkCollection(),
                'path'         => $certificates->path(),
                'per_page'     => $certificates->perPage(),
                'to'           => $certificates->lastItem(),
                'total'        => $certificates->total(),
            ],
            'filters' => [
                'search'  => $search,
                'sorting' => $sorting,
            ],
            'trashed_count' => PolicyCertificate::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('policy_certificates.view');

        $per_page = $request->get('per_page', 50);
        $search   = $request->get('search', '');
        $sorting  = $request->get('sorting', []);

        $query = PolicyCertificate::onlyTrashed()->with('certificateType');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhere('policy_number', 'like', "%{$search}%")
                    ->orWhereHas('certificateType', fn($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }

        $query->orderBy($sorting['column'] ?? 'id', $sorting['direction'] ?? 'desc');

        $certificates = $query->paginate($per_page)->withQueryString();

        return Inertia::render('Backend/User/PolicyCertificate/Trash', [
            'certificates' => $certificates->items(),
            'meta' => [
                'current_page' => $certificates->currentPage(),
                'from'         => $certificates->firstItem(),
                'last_page'    => $certificates->lastPage(),
                'links'        => $certificates->linkCollection(),
                'path'         => $certificates->path(),
                'per_page'     => $certificates->perPage(),
                'to'           => $certificates->lastItem(),
                'total'        => $certificates->total(),
            ],
            'filters' => [
                'search'  => $search,
                'sorting' => $sorting,
            ],
        ]);
    }

    private function buildNumber(string $prefixKey, string $incrementKey, string $slug): string
    {
        $prefix    = Setting::where('name', $prefixKey)->value('value') ?? '';
        $increment = Setting::where('name', $incrementKey)->value('value') ?? 1;
        $year      = now()->format('Y');

        $parts = array_filter([$prefix, $slug, $year, $increment], fn($p) => $p !== '');

        return implode('/', $parts);
    }

    public function create()
    {
        Gate::authorize('policy_certificates.create');

        return Inertia::render('Backend/User/PolicyCertificate/Create', [
            'cert_prefix'      => Setting::where('name', 'cert_number_prefix')->value('value') ?? '',
            'cert_increment'   => Setting::where('name', 'cert_number_increment')->value('value') ?? 1,
            'policy_prefix'    => Setting::where('name', 'policy_number_prefix')->value('value') ?? '',
            'policy_increment' => Setting::where('name', 'policy_number_increment')->value('value') ?? 1,
            'certificateTypes' => CertificateType::orderBy('name')->get(['id', 'name', 'slug']),
            'customers'        => Customer::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('policy_certificates.create');

        $validator = Validator::make($request->all(), [
            'customer_id'       => 'required|exists:customers,id',
            'certificate_type'  => 'required|exists:certificate_types,id',
            'policy_start_date' => 'required|date',
            'policy_end_date'   => 'required|date|after_or_equal:policy_start_date',
            'sections'          => 'nullable|array',
            'sections.*.title'  => 'required|string|max:255',
            'sections.*.type'   => 'required|string|in:fields,table,text,note,terms,exclusions,signature',
        ]);

        if ($validator->fails()) {
            return redirect()->route('policy_certificates.create')
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request) {
            $certType          = CertificateType::findOrFail($request->certificate_type);
            $certificateNumber = $this->buildNumber('cert_number_prefix', 'cert_number_increment', $certType->slug);
            $policyNumber      = $this->buildNumber('policy_number_prefix', 'policy_number_increment', $certType->slug);

            $certificate = PolicyCertificate::create([
                'customer_id'         => $request->customer_id,
                'certificate_type_id' => $certType->id,
                'certificate_number'  => $certificateNumber,
                'policy_number'       => $policyNumber,
                'policy_start_date'   => $request->policy_start_date,
                'policy_end_date'     => $request->policy_end_date,
                'short_code'          => rand(100000, 9999999) . uniqid(),
            ]);

            // Increment the counters in settings
            Setting::where('name', 'cert_number_increment')->increment('value');
            Setting::where('name', 'policy_number_increment')->increment('value');

            foreach ($request->input('sections', []) as $sortOrder => $sectionData) {
                $section = PolicyCertificateSection::create([
                    'policy_certificate_id' => $certificate->id,
                    'title'                 => $sectionData['title'],
                    'type'                  => $sectionData['type'],
                    'sort_order'            => $sortOrder,
                ]);

                $type = $sectionData['type'];

                if ($type === 'fields') {
                    foreach ($sectionData['fields'] ?? [] as $fieldOrder => $field) {
                        if (empty($field['label'])) continue;
                        PolicyCertificateField::create([
                            'policy_certificate_id' => $certificate->id,
                            'section'               => (string) $section->id,
                            'label'                 => $field['label'],
                            'value'                 => $field['value'] ?? null,
                            'sort_order'            => $fieldOrder,
                        ]);
                    }
                } elseif ($type === 'table') {
                    $columns = $sectionData['columns'] ?? [];
                    // Store column headers as a field row
                    PolicyCertificateField::create([
                        'policy_certificate_id' => $certificate->id,
                        'section'               => (string) $section->id,
                        'label'                 => 'columns',
                        'value'                 => json_encode($columns),
                        'sort_order'            => 0,
                    ]);
                    // Store each data row
                    foreach ($sectionData['rows'] ?? [] as $rowOrder => $row) {
                        PolicyCertificateSectionRow::create([
                            'policy_certificate_section_id' => $section->id,
                            'data'       => array_combine($columns, array_pad($row, count($columns), '')),
                            'sort_order' => $rowOrder,
                        ]);
                    }
                } else {
                    // text, note, terms, exclusions, signature
                    PolicyCertificateField::create([
                        'policy_certificate_id' => $certificate->id,
                        'section'               => (string) $section->id,
                        'label'                 => $type,
                        'value'                 => $sectionData['content'] ?? null,
                        'sort_order'            => 0,
                    ]);
                }
            }

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Created Policy Certificate ' . $certificate->certificate_number;
            $audit->save();
        });

        return redirect()->route('policy_certificates.index')->with('success', _lang('Saved Successfully'));
    }

    public function show($id)
    {
        Gate::authorize('policy_certificates.view');

        $certificate = PolicyCertificate::with([
            'customer',
            'certificateType',
            'sections',
            'sections.rows',
            'fields',
        ])->findOrFail($id);

        $sections = $certificate->sections->map(function ($section) use ($certificate) {
            $sectionFields = $certificate->fields->where('section', (string) $section->id);

            if ($section->type === 'fields') {
                return [
                    'title'  => $section->title,
                    'type'   => $section->type,
                    'fields' => $sectionFields->map(fn($f) => ['label' => $f->label, 'value' => $f->value ?? ''])->values(),
                ];
            } elseif ($section->type === 'table') {
                $columnsField = $sectionFields->firstWhere('label', 'columns');
                $columns      = $columnsField ? (json_decode($columnsField->value, true) ?? []) : [];
                $rows         = $section->rows->map(function ($r) {
                    $data = is_array($r->data) ? $r->data : (json_decode($r->data, true) ?? []);
                    return array_values($data);
                });
                return [
                    'title'   => $section->title,
                    'type'    => $section->type,
                    'columns' => $columns,
                    'rows'    => $rows,
                ];
            } else {
                $contentField = $sectionFields->firstWhere('label', $section->type);
                return [
                    'title'   => $section->title,
                    'type'    => $section->type,
                    'content' => $contentField ? ($contentField->value ?? '') : '',
                ];
            }
        })->values();

        $business = auth()->user()->business()->withoutGlobalScopes()->with('systemSettings')->wherePivot('is_active', 1)->first();

        return Inertia::render('Backend/User/PolicyCertificate/View', [
            'certificate' => [
                'id'                 => $certificate->id,
                'short_code'         => $certificate->short_code,
                'customer_name'      => $certificate->customer?->name ?? '',
                'certificate_number' => $certificate->certificate_number,
                'policy_number'      => $certificate->policy_number,
                'certificate_type'   => $certificate->certificateType?->name ?? '',
                'policy_start_date'  => $certificate->getRawOriginal('policy_start_date'),
                'policy_end_date'    => $certificate->getRawOriginal('policy_end_date'),
            ],
            'sections' => $sections,
            'business' => [
                'name'            => $business?->business_name ?? '',
                'logo'            => $business?->logo ?? null,
                'email'           => $business?->business_email ?? '',
                'phone'           => $business?->phone ?? $business?->mobile ?? '',
                'website'         => $business?->website ?? '',
                'system_settings' => $business?->systemSettings ?? [],
            ],
        ]);
    }

    public function edit($id)
    {
        Gate::authorize('policy_certificates.update');

        $certificate = PolicyCertificate::with([
            'customer',
            'certificateType',
            'sections',
            'sections.rows',
            'fields',
        ])->findOrFail($id);

        $initialSections = $certificate->sections->map(function ($section) use ($certificate) {
            $sectionFields = $certificate->fields->where('section', (string) $section->id);

            if ($section->type === 'fields') {
                $fields = $sectionFields->map(fn($f) => ['label' => $f->label, 'value' => $f->value ?? ''])->values()->toArray();
                return [
                    'title'      => $section->title,
                    'type'       => $section->type,
                    'sort_order' => $section->sort_order,
                    'fields'     => empty($fields) ? [['label' => '', 'value' => '']] : $fields,
                    'columns'    => [''],
                    'rows'       => [['']],
                    'content'    => '',
                ];
            } elseif ($section->type === 'table') {
                $columnsField = $sectionFields->firstWhere('label', 'columns');
                $columns      = $columnsField ? (json_decode($columnsField->value, true) ?? ['']) : [''];
                $rows         = $section->rows->map(function ($r) {
                    $data = is_array($r->data) ? $r->data : json_decode($r->data, true) ?? [];
                    return array_values($data);
                })->toArray();
                return [
                    'title'      => $section->title,
                    'type'       => $section->type,
                    'sort_order' => $section->sort_order,
                    'fields'     => [['label' => '', 'value' => '']],
                    'columns'    => $columns ?: [''],
                    'rows'       => empty($rows) ? [array_fill(0, count($columns), '')] : $rows,
                    'content'    => '',
                ];
            } else {
                $contentField = $sectionFields->firstWhere('label', $section->type);
                return [
                    'title'      => $section->title,
                    'type'       => $section->type,
                    'sort_order' => $section->sort_order,
                    'fields'     => [['label' => '', 'value' => '']],
                    'columns'    => [''],
                    'rows'       => [['']],
                    'content'    => $contentField ? ($contentField->value ?? '') : '',
                ];
            }
        })->values()->toArray();

        return Inertia::render('Backend/User/PolicyCertificate/Edit', [
            'certificate' => [
                'id'                  => $certificate->id,
                'customer_id'         => $certificate->customer_id,
                'certificate_type_id' => $certificate->certificate_type_id,
                'certificate_number'  => $certificate->certificate_number,
                'policy_number'       => $certificate->policy_number,
                'policy_start_date'   => $certificate->getRawOriginal('policy_start_date'),
                'policy_end_date'     => $certificate->getRawOriginal('policy_end_date'),
            ],
            'certificateTypes' => CertificateType::orderBy('name')->get(['id', 'name', 'slug']),
            'customers'        => Customer::select('id', 'name')->orderBy('name')->get(),
            'initialSections'  => $initialSections,
        ]);
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('policy_certificates.update');

        $validator = Validator::make($request->all(), [
            'customer_id'         => 'required|exists:customers,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'policy_start_date'   => 'required|date',
            'policy_end_date'     => 'required|date|after_or_equal:policy_start_date',
            'sections'            => 'nullable|array',
            'sections.*.title'    => 'required|string|max:255',
            'sections.*.type'     => 'required|string|in:fields,table,text,note,terms,exclusions,signature',
        ]);

        if ($validator->fails()) {
            return redirect()->route('policy_certificates.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request, $id) {
            $certificate = PolicyCertificate::findOrFail($id);

            $certificate->update([
                'customer_id'         => $request->customer_id,
                'certificate_type_id' => $request->certificate_type_id,
                'policy_start_date'   => $request->policy_start_date,
                'policy_end_date'     => $request->policy_end_date,
            ]);

            // Remove old child records then recreate
            $oldSectionIds = PolicyCertificateSection::where('policy_certificate_id', $id)->pluck('id');
            PolicyCertificateSectionRow::whereIn('policy_certificate_section_id', $oldSectionIds)->forceDelete();
            PolicyCertificateSection::where('policy_certificate_id', $id)->forceDelete();
            PolicyCertificateField::where('policy_certificate_id', $id)->forceDelete();

            foreach ($request->input('sections', []) as $sortOrder => $sectionData) {
                $section = PolicyCertificateSection::create([
                    'policy_certificate_id' => $certificate->id,
                    'title'                 => $sectionData['title'],
                    'type'                  => $sectionData['type'],
                    'sort_order'            => $sortOrder,
                ]);

                $type = $sectionData['type'];

                if ($type === 'fields') {
                    foreach ($sectionData['fields'] ?? [] as $fieldOrder => $field) {
                        if (empty($field['label'])) continue;
                        PolicyCertificateField::create([
                            'policy_certificate_id' => $certificate->id,
                            'section'               => (string) $section->id,
                            'label'                 => $field['label'],
                            'value'                 => $field['value'] ?? null,
                            'sort_order'            => $fieldOrder,
                        ]);
                    }
                } elseif ($type === 'table') {
                    $columns = $sectionData['columns'] ?? [];
                    PolicyCertificateField::create([
                        'policy_certificate_id' => $certificate->id,
                        'section'               => (string) $section->id,
                        'label'                 => 'columns',
                        'value'                 => json_encode($columns),
                        'sort_order'            => 0,
                    ]);
                    foreach ($sectionData['rows'] ?? [] as $rowOrder => $row) {
                        PolicyCertificateSectionRow::create([
                            'policy_certificate_section_id' => $section->id,
                            'data'       => array_combine($columns, array_pad($row, count($columns), '')),
                            'sort_order' => $rowOrder,
                        ]);
                    }
                } else {
                    PolicyCertificateField::create([
                        'policy_certificate_id' => $certificate->id,
                        'section'               => (string) $section->id,
                        'label'                 => $type,
                        'value'                 => $sectionData['content'] ?? null,
                        'sort_order'            => 0,
                    ]);
                }
            }

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Updated Policy Certificate ' . $certificate->certificate_number;
            $audit->save();
        });

        return redirect()->route('policy_certificates.index')->with('success', _lang('Updated Successfully'));
    }

    public function destroy($id)
    {
        Gate::authorize('policy_certificates.delete');

        $certificate = PolicyCertificate::findOrFail($id);

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Deleted Policy Certificate ' . $certificate->certificate_number;
        $audit->save();

        $certificate->delete();

        return redirect()->route('policy_certificates.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        Gate::authorize('policy_certificates.delete');

        foreach ($request->ids as $id) {
            $certificate = PolicyCertificate::find($id);
            if (!$certificate) continue;

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Deleted Policy Certificate ' . $certificate->certificate_number;
            $audit->save();

            $certificate->delete();
        }

        return redirect()->route('policy_certificates.index')->with('success', _lang('Deleted Successfully'));
    }

    public function permanent_destroy($id)
    {
        Gate::authorize('policy_certificates.delete');

        $certificate = PolicyCertificate::onlyTrashed()->findOrFail($id);

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Permanently Deleted Policy Certificate ' . $certificate->certificate_number;
        $audit->save();

        $certificate->forceDelete();

        return redirect()->route('policies.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('policy_certificates.delete');

        foreach ($request->ids as $id) {
            $certificate = PolicyCertificate::onlyTrashed()->find($id);
            if (!$certificate) continue;

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Permanently Deleted Policy Certificate ' . $certificate->certificate_number;
            $audit->save();

            $certificate->forceDelete();
        }

        return redirect()->route('policies.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function restore($id)
    {
        Gate::authorize('policy_certificates.restore');

        $certificate = PolicyCertificate::onlyTrashed()->findOrFail($id);

        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by   = auth()->user()->id;
        $audit->event        = 'Restored Policy Certificate ' . $certificate->certificate_number;
        $audit->save();

        $certificate->restore();

        return redirect()->route('policies.trash')->with('success', _lang('Restored Successfully'));
    }

    public function show_public(string $short_code)
    {
        $certificate = PolicyCertificate::withoutGlobalScopes()->with([
            'customer',
            'certificateType',
            'sections',
            'sections.rows',
            'fields',
        ])->where('short_code', $short_code)->firstOrFail();

        $sections = $certificate->sections->map(function ($section) use ($certificate) {
            $sectionFields = $certificate->fields->where('section', (string) $section->id);

            if ($section->type === 'fields') {
                return [
                    'title'  => $section->title,
                    'type'   => $section->type,
                    'fields' => $sectionFields->map(fn($f) => ['label' => $f->label, 'value' => $f->value ?? ''])->values(),
                ];
            } elseif ($section->type === 'table') {
                $columnsField = $sectionFields->firstWhere('label', 'columns');
                $columns      = $columnsField ? (json_decode($columnsField->value, true) ?? []) : [];
                $rows         = $section->rows->map(function ($r) {
                    $data = is_array($r->data) ? $r->data : (json_decode($r->data, true) ?? []);
                    return array_values($data);
                });
                return [
                    'title'   => $section->title,
                    'type'    => $section->type,
                    'columns' => $columns,
                    'rows'    => $rows,
                ];
            } else {
                $contentField = $sectionFields->firstWhere('label', $section->type);
                return [
                    'title'   => $section->title,
                    'type'    => $section->type,
                    'content' => $contentField ? ($contentField->value ?? '') : '',
                ];
            }
        })->values();

        $user     = \App\Models\User::find($certificate->user_id);
        $business = $user?->business()->withoutGlobalScopes()->with('systemSettings')->wherePivot('is_active', 1)->first();

        return Inertia::render('Backend/User/PolicyCertificate/PublicView', [
            'certificate' => [
                'id'                 => $certificate->id,
                'customer_name'      => $certificate->customer?->name ?? '',
                'certificate_number' => $certificate->certificate_number,
                'policy_number'      => $certificate->policy_number,
                'certificate_type'   => $certificate->certificateType?->name ?? '',
                'policy_start_date'  => $certificate->getRawOriginal('policy_start_date'),
                'policy_end_date'    => $certificate->getRawOriginal('policy_end_date'),
            ],
            'sections' => $sections,
            'business' => [
                'name'            => $business?->business_name ?? '',
                'logo'            => $business?->logo ?? null,
                'email'           => $business?->business_email ?? '',
                'phone'           => $business?->phone ?? $business?->mobile ?? '',
                'website'         => $business?->website ?? '',
                'system_settings' => $business?->systemSettings ?? [],
            ],
        ]);
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('policy_certificates.restore');

        foreach ($request->ids as $id) {
            $certificate = PolicyCertificate::onlyTrashed()->find($id);
            if (!$certificate) continue;

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by   = auth()->user()->id;
            $audit->event        = 'Restored Policy Certificate ' . $certificate->certificate_number;
            $audit->save();

            $certificate->restore();
        }

        return redirect()->route('policies.trash')->with('success', _lang('Restored Successfully'));
    }
}
