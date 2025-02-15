<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Leave;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Validator;

class LeaveController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $leaves = Leave::select('leaves.*')
            ->with('staff')
            ->get();

        return view('backend.user.leave.list', compact('leaves'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.leave.modal.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'employee_id'    => 'required',
            'leave_type'     => 'required',
            'leave_duration' => 'required',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'status'         => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('leaves.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $leave                 = new Leave();
        $leave->employee_id    = $request->input('employee_id');
        $leave->leave_type     = $request->input('leave_type');
        $leave->leave_duration = $request->input('leave_duration');
        $leave->start_date     = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $leave->end_date       = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $leave->total_days     = $request->input('total_days');
        $leave->description    = $request->input('description');
        $leave->status         = $request->input('status');

        $leave->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Leave ' . $leave->leave_type;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('leaves.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $leave, 'table' => '#leaves_table']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $leave = Leave::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.leave.modal.view', compact('leave', 'id'));
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $leave = Leave::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.leave.modal.edit', compact('leave', 'id'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'employee_id'    => 'required',
            'leave_type'     => 'required',
            'leave_duration' => 'required',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'status'         => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('leaves.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $leave                 = Leave::find($id);
        $leave->employee_id    = $request->input('employee_id');
        $leave->leave_type     = $request->input('leave_type');
        $leave->leave_duration = $request->input('leave_duration');
        $leave->start_date     = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $leave->end_date       = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $leave->total_days     = $request->input('total_days');
        $leave->description    = $request->input('description');
        $leave->status         = $request->input('status');
        $leave->user_id        = $request->input('user_id');
        $leave->business_id    = $request->input('business_id');

        $leave->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Leave ' . $leave->leave_type;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('leaves.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $leave, 'table' => '#leaves_table']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $leave = Leave::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Leave ' . $leave->leave_type;
        $audit->save();

        $leave->delete();
        return redirect()->route('leaves.index')->with('success', _lang('Deleted Successfully'));
    }
}