<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\MedicalRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MedicalRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (package()->medical_record != 1) {
                if (!$request->ajax()) {
                    return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
                } else {
                    return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
                }
            }

            return $next($request);
        });
    }

    public function index(Request $request) {
        $query = MedicalRecord::select('medical_records.*')->with('customer');

        // handle search
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('age', 'like', "%{$search}%");
            });
        }

        // handle column filters
        if ($request->has('columnFilters')) {
            $columnFilters = $request->get('columnFilters');
            if (is_string($columnFilters)) {
                $columnFilters = json_decode($columnFilters, true);
            }
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        $query->whereHas('customer', function ($q) use ($column, $value) {
                            $q->where($column, $value);
                        });
                    }
                }
            }
        }

        // handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        if (in_array($sortColumn, ['customer.name', 'customer.age', 'customer.mobile'])) {
            $customerField = explode('.', $sortColumn)[1];
            $query->join('customers', 'medical_records.customer_id', '=', 'customers.id')
                  ->orderBy('customers.' . $customerField, $sortDirection)
                  ->select('medical_records.*');
        } else {
            $query->orderBy('medical_records.' . $sortColumn, $sortDirection);
        }

        // handle pagination
        $perPage = $request->get('per_page', 10);
        $records = $query->paginate($perPage);
        return Inertia::render('Backend/User/MedicalRecord/List', [
            'records' => $records->items(),
            'meta' => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
            'trashed_medical_records' => MedicalRecord::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request) {
        $query = MedicalRecord::onlyTrashed()->select('medical_records.*')->with('customer');

        // handle search
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('age', 'like', "%{$search}%");
            });
        }

        // handle column filters
        if ($request->has('columnFilters')) {
            $columnFilters = $request->get('columnFilters');
            if (is_string($columnFilters)) {
                $columnFilters = json_decode($columnFilters, true);
            }
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        $query->whereHas('customer', function ($q) use ($column, $value) {
                            $q->where($column, $value);
                        });
                    }
                }
            }
        }

        // handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        if (in_array($sortColumn, ['customer.name', 'customer.age', 'customer.mobile'])) {
            $customerField = explode('.', $sortColumn)[1];
            $query->join('customers', 'medical_records.customer_id', '=', 'customers.id')
                  ->orderBy('customers.' . $customerField, $sortDirection)
                  ->select('medical_records.*');
        } else {
            $query->orderBy('medical_records.' . $sortColumn, $sortDirection);
        }

        // handle pagination
        $perPage = $request->get('per_page', 10);
        $records = $query->paginate($perPage);
        return Inertia::render('Backend/User/MedicalRecord/Trash', [
            'records' => $records->items(),
            'meta' => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
        ]);
    }

    public function create() {
        $customers = Customer::all();
        return Inertia::render('Backend/User/MedicalRecord/Create', [
            'customers' => $customers
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'customer_id' => 'required',
            'patient_id' => 'nullable',
            'date'       =>  'required',    
            'ocular_history' => 'nullable',
            'occupation' => 'nullable',
            'va_unaided_re' => 'nullable',
            'va_unaided_le' => 'nullable',
            'va_aided_re' => 'nullable',
            'va_aided_le' => 'nullable',
            'va_pinhole_re' => 'nullable',
            'va_pinhole_le' => 'nullable',
            'rf_unaided_d_re' => 'nullable',
            'rf_unaided_d_le' => 'nullable',
            'rf_unaided_n_re' => 'nullable',
            'rf_unaided_n_le' => 'nullable',
            'rf_aided_d_re' => 'nullable',
            'rf_aided_d_le' => 'nullable',
            'rf_aided_n_re' => 'nullable',
            'rf_aided_n_le' => 'nullable',
            'rf_best_corrected_le' => 'nullable',
            'rf_best_corrected_re' => 'nullable',
            'rf_test_type_used_re' => 'nullable',
            'rf_test_type_used_le' => 'nullable',
            'rf_lensometer_le' => 'nullable',
            'rf_lensometer_re' => 'nullable',
            'rf_autorefraction_re' => 'nullable',
            'rf_autorefraction_le' => 'nullable',
            'rf_dry_retinoscopy_re' => 'nullable',
            'rf_dry_retinoscopy_le' => 'nullable',
            'rf_wet_retinoscopy_re' => 'nullable',
            'rf_wet_retinoscopy_le' => 'nullable',
            'rf_subjective_re' => 'nullable',
            'rf_subjective_le' => 'nullable',
            'rf_near_re' => 'nullable',
            'rf_near_le' => 'nullable',
            'rf_final_prescription_re' => 'nullable',
            'rf_final_prescription_le' => 'nullable',
            'eso' => 'nullable',
            'exo' => 'nullable',
            'hypo' => 'nullable',
            'hyper' => 'nullable',
            'eso_distance_5m_6m' => 'nullable',
            'eso_near_30cm_50cm' => 'nullable',
            'exo_distance_5m_6m' => 'nullable',
            'exo_near_30cm_50cm' => 'nullable',
            'hypo_distance_5m_6m' => 'nullable',
            'hypo_near_30cm_50cm' => 'nullable',
            'hyper_distance_5m_6m' => 'nullable',
            'hyper_near_30cm_50cm' => 'nullable',
            'tropia' => 'nullable',
            'phoria' => 'nullable',
        ]);

        $medical_record                      = new MedicalRecord();
        $medical_record->customer_id         = $data['customer_id'];
        $medical_record->patient_id          = $data['patient_id'];
        $medical_record->date                = Carbon::parse($data['date'])->format('Y-m-d');
        $medical_record->ocular_history      = $data['ocular_history'];
        $medical_record->occupation          = $data['occupation'];
        $medical_record->va_unaided_re       = $data['va_unaided_re'];
        $medical_record->va_unaided_le       = $data['va_unaided_le'];
        $medical_record->va_aided_re         = $data['va_aided_re'];
        $medical_record->va_aided_le         = $data['va_aided_le'];
        $medical_record->va_pinhole_re       = $data['va_pinhole_re'];
        $medical_record->va_pinhole_le       = $data['va_pinhole_le'];
        $medical_record->rf_unaided_d_re     = $data['rf_unaided_d_re'];
        $medical_record->rf_unaided_d_le     = $data['rf_unaided_d_le'];
        $medical_record->rf_unaided_n_re     = $data['rf_unaided_n_re'];
        $medical_record->rf_unaided_n_le     = $data['rf_unaided_n_le'];
        $medical_record->rf_aided_d_re       = $data['rf_aided_d_re'];
        $medical_record->rf_aided_d_le       = $data['rf_aided_d_le'];
        $medical_record->rf_aided_n_re       = $data['rf_aided_n_re'];
        $medical_record->rf_aided_n_le       = $data['rf_aided_n_le'];
        $medical_record->rf_best_corrected_le= $data['rf_best_corrected_le'];
        $medical_record->rf_best_corrected_re= $data['rf_best_corrected_re'];
        $medical_record->rf_test_type_used_re= $data['rf_test_type_used_re'];
        $medical_record->rf_test_type_used_le= $data['rf_test_type_used_le'];
        $medical_record->rf_lensometer_le    = $data['rf_lensometer_le'];
        $medical_record->rf_lensometer_re    = $data['rf_lensometer_re'];
        $medical_record->rf_autorefraction_re= $data['rf_autorefraction_re'];
        $medical_record->rf_autorefraction_le= $data['rf_autorefraction_le'];
        $medical_record->rf_dry_retinoscopy_re= $data['rf_dry_retinoscopy_re'];
        $medical_record->rf_dry_retinoscopy_le= $data['rf_dry_retinoscopy_le'];
        $medical_record->rf_wet_retinoscopy_re= $data['rf_wet_retinoscopy_re'];
        $medical_record->rf_wet_retinoscopy_le= $data['rf_wet_retinoscopy_le'];
        $medical_record->rf_subjective_re    = $data['rf_subjective_re'];
        $medical_record->rf_subjective_le    = $data['rf_subjective_le'];
        $medical_record->rf_near_re          = $data['rf_near_re'];
        $medical_record->rf_near_le          = $data['rf_near_le'];
        $medical_record->rf_final_prescription_re= $data['rf_final_prescription_re'];
        $medical_record->rf_final_prescription_le= $data['rf_final_prescription_le'];
        $medical_record->eso                 = $data['eso'] ?? 0;
        $medical_record->exo                 = $data['exo'] ?? 0;
        $medical_record->hypo                = $data['hypo'] ?? 0;
        $medical_record->hyper               = $data['hyper'] ?? 0;
        $medical_record->eso_distance_5m_6m  = $data['eso_distance_5m_6m'];
        $medical_record->eso_near_30cm_50cm  = $data['eso_near_30cm_50cm'];
        $medical_record->exo_distance_5m_6m  = $data['exo_distance_5m_6m'];
        $medical_record->exo_near_30cm_50cm  = $data['exo_near_30cm_50cm'];
        $medical_record->hypo_distance_5m_6m = $data['hypo_distance_5m_6m'];
        $medical_record->hypo_near_30cm_50cm = $data['hypo_near_30cm_50cm'];
        $medical_record->hyper_distance_5m_6m= $data['hyper_distance_5m_6m'];
        $medical_record->hyper_near_30cm_50cm= $data['hyper_near_30cm_50cm'];
        $medical_record->tropia              = $data['tropia'] ?? 0;
        $medical_record->phoria              = $data['phoria'] ?? 0;
        $medical_record->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Record Created';
        $audit->save();

        return redirect()->route('medical_records.index');
    }

    public function show($id) {
        $record = MedicalRecord::find($id);
        $customer = Customer::find($record->customer_id);
        
        return Inertia::render('Backend/User/MedicalRecord/View', [
            'record' => $record,
            'customer' => $customer,
        ]);
    }

    public function edit($id) {
        $medicalRecord = MedicalRecord::find($id);
        $customers = Customer::all();
        return Inertia::render('Backend/User/MedicalRecord/Edit', [
            'medicalRecord' => $medicalRecord,
            'id' => $id,
            'customers' => $customers,
        ]);
    }

    public function update(Request $request, $id) {
        $data = $request->validate([
            'customer_id' => 'required',
            'patient_id' => 'nullable',
            'date'      => 'required',
            'ocular_history' => 'nullable',
            'occupation' => 'nullable',
            'va_unaided_re' => 'nullable',
            'va_unaided_le' => 'nullable',
            'va_aided_re' => 'nullable',
            'va_aided_le' => 'nullable',
            'va_pinhole_re' => 'nullable',
            'va_pinhole_le' => 'nullable',
            'rf_unaided_d_re' => 'nullable',
            'rf_unaided_d_le' => 'nullable',
            'rf_unaided_n_re' => 'nullable',
            'rf_unaided_n_le' => 'nullable',
            'rf_aided_d_re' => 'nullable',
            'rf_aided_d_le' => 'nullable',
            'rf_aided_n_re' => 'nullable',
            'rf_aided_n_le' => 'nullable',
            'rf_best_corrected_le' => 'nullable',
            'rf_best_corrected_re' => 'nullable',
            'rf_test_type_used_re' => 'nullable',
            'rf_test_type_used_le' => 'nullable',
            'rf_lensometer_le' => 'nullable',
            'rf_lensometer_re' => 'nullable',
            'rf_autorefraction_re' => 'nullable',
            'rf_autorefraction_le' => 'nullable',
            'rf_dry_retinoscopy_re' => 'nullable',
            'rf_dry_retinoscopy_le' => 'nullable',
            'rf_wet_retinoscopy_re' => 'nullable',
            'rf_wet_retinoscopy_le' => 'nullable',
            'rf_subjective_re' => 'nullable',
            'rf_subjective_le' => 'nullable',
            'rf_near_re' => 'nullable',
            'rf_near_le' => 'nullable',
            'rf_final_prescription_re' => 'nullable',
            'rf_final_prescription_le' => 'nullable',
            'eso' => 'nullable',
            'exo' => 'nullable',
            'hypo' => 'nullable',
            'hyper' => 'nullable',
            'eso_distance_5m_6m' => 'nullable',
            'eso_near_30cm_50cm' => 'nullable',
            'exo_distance_5m_6m' => 'nullable',
            'exo_near_30cm_50cm' => 'nullable',
            'hypo_distance_5m_6m' => 'nullable',
            'hypo_near_30cm_50cm' => 'nullable',
            'hyper_distance_5m_6m' => 'nullable',
            'hyper_near_30cm_50cm' => 'nullable',
            'tropia' => 'nullable',
            'phoria' => 'nullable',
        ]);

        $medical_record                      = MedicalRecord::find($id);
        $medical_record->customer_id         = $data['customer_id'];
        $medical_record->patient_id          = $data['patient_id'];
        $medical_record->date                = Carbon::parse($data['date'])->format('Y-m-d');
        $medical_record->ocular_history      = $data['ocular_history'];
        $medical_record->occupation          = $data['occupation'];
        $medical_record->va_unaided_re       = $data['va_unaided_re'];
        $medical_record->va_unaided_le       = $data['va_unaided_le'];
        $medical_record->va_aided_re         = $data['va_aided_re'];
        $medical_record->va_aided_le         = $data['va_aided_le'];
        $medical_record->va_pinhole_re       = $data['va_pinhole_re'];
        $medical_record->va_pinhole_le       = $data['va_pinhole_le'];
        $medical_record->rf_unaided_d_re     = $data['rf_unaided_d_re'];
        $medical_record->rf_unaided_d_le     = $data['rf_unaided_d_le'];
        $medical_record->rf_unaided_n_re     = $data['rf_unaided_n_re'];
        $medical_record->rf_unaided_n_le     = $data['rf_unaided_n_le'];
        $medical_record->rf_aided_d_re       = $data['rf_aided_d_re'];
        $medical_record->rf_aided_d_le       = $data['rf_aided_d_le'];
        $medical_record->rf_aided_n_re       = $data['rf_aided_n_re'];
        $medical_record->rf_aided_n_le       = $data['rf_aided_n_le'];
        $medical_record->rf_best_corrected_le= $data['rf_best_corrected_le'];
        $medical_record->rf_best_corrected_re= $data['rf_best_corrected_re'];
        $medical_record->rf_test_type_used_re= $data['rf_test_type_used_re'];
        $medical_record->rf_test_type_used_le= $data['rf_test_type_used_le'];
        $medical_record->rf_lensometer_le    = $data['rf_lensometer_le'];
        $medical_record->rf_lensometer_re    = $data['rf_lensometer_re'];
        $medical_record->rf_autorefraction_re= $data['rf_autorefraction_re'];
        $medical_record->rf_autorefraction_le= $data['rf_autorefraction_le'];
        $medical_record->rf_dry_retinoscopy_re= $data['rf_dry_retinoscopy_re'];
        $medical_record->rf_dry_retinoscopy_le= $data['rf_dry_retinoscopy_le'];
        $medical_record->rf_wet_retinoscopy_re= $data['rf_wet_retinoscopy_re'];
        $medical_record->rf_wet_retinoscopy_le= $data['rf_wet_retinoscopy_le'];
        $medical_record->rf_subjective_re    = $data['rf_subjective_re'];
        $medical_record->rf_subjective_le    = $data['rf_subjective_le'];
        $medical_record->rf_near_re          = $data['rf_near_re'];
        $medical_record->rf_near_le          = $data['rf_near_le'];
        $medical_record->rf_final_prescription_re= $data['rf_final_prescription_re'];
        $medical_record->rf_final_prescription_le= $data['rf_final_prescription_le'];
        $medical_record->eso                 = $data['eso'] ?? 0;
        $medical_record->exo                 = $data['exo'] ?? 0;
        $medical_record->hypo                = $data['hypo'] ?? 0;
        $medical_record->hyper               = $data['hyper'] ?? 0;
        $medical_record->eso_distance_5m_6m  = $data['eso_distance_5m_6m'];
        $medical_record->eso_near_30cm_50cm  = $data['eso_near_30cm_50cm'];
        $medical_record->exo_distance_5m_6m  = $data['exo_distance_5m_6m'];
        $medical_record->exo_near_30cm_50cm  = $data['exo_near_30cm_50cm'];
        $medical_record->hypo_distance_5m_6m = $data['hypo_distance_5m_6m'];
        $medical_record->hypo_near_30cm_50cm = $data['hypo_near_30cm_50cm'];
        $medical_record->hyper_distance_5m_6m= $data['hyper_distance_5m_6m'];
        $medical_record->hyper_near_30cm_50cm= $data['hyper_near_30cm_50cm'];
        $medical_record->tropia              = $data['tropia'] ?? 0;
        $medical_record->phoria              = $data['phoria'] ?? 0;
        $medical_record->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Record Updated';
        $audit->save();

        return redirect()->route('medical_records.index');
    }

    public function destroy($id) {
        $record = MedicalRecord::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Record Deleted';
        $audit->save();

        $record->delete();
        return redirect()->route('medical_records.index');
    }

    public function permanent_destroy($id) {
        $record = MedicalRecord::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Record Permanently Deleted';
        $audit->save();

        $record->forceDelete();
        return redirect()->route('medical_records.trash');
    }

    public function restore($id) {
        $record = MedicalRecord::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Record Restored';
        $audit->save();

        $record->restore();
        return redirect()->route('medical_records.trash');
    }

    public function bulk_destroy(Request $request) {
        $records = MedicalRecord::whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Records Deleted - ' . count($records) . ' Medical Records';
        $audit->save();

        foreach ($records as $record) {
            $record->delete();
        }

        return redirect()->route('medical_records.index');
    }

    public function bulk_permanent_destroy(Request $request) {
        $records = MedicalRecord::onlyTrashed()->whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Records Permanently Deleted - ' . count($records) . ' Medical Records';
        $audit->save();

        foreach ($records as $record) {
            $record->forceDelete();
        }

        return redirect()->route('medical_records.index');
    }

    public function bulk_restore(Request $request) {
        $records = MedicalRecord::onlyTrashed()->whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Medical Records Restored - ' . count($records) . ' Medical Records';
        $audit->save();

        foreach ($records as $record) {
            $record->restore();
        }

        return redirect()->route('medical_records.trash');
    }
}
