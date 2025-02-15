<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MedicalRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

    public function index() {
        $records = MedicalRecord::all();
        return view('backend.user.medical_record.list', compact('records'));
    }

    public function create() {
        return view('backend.user.medical_record.create');
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
        return view('backend.user.medical_record.view', compact('record'));
    }

    public function edit($id) {
        $record = MedicalRecord::find($id);
        return view('backend.user.medical_record.edit', compact('record', 'id'));
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
}
