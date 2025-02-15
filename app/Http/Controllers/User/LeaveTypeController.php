<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Validator;

class LeaveTypeController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(function ($request, $next) {

            if (package()->payroll_module != 1) {
                if (!$request->ajax()) {
                    return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
                } else {
                    return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
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
    public function index() {
        $assets     = ['datatable'];
        $leavetypes = LeaveType::all()->sortByDesc("id");
        return view('backend.user.leave_types.list', compact('leavetypes', 'assets'));
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
            return view('backend.user.leave_types.modal.create');
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
            'title' => 'required|max:50',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('leave_types.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $leavetype        = new LeaveType();
        $leavetype->title = $request->input('title');

        $leavetype->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Leave Type ' . $leavetype->title;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('leave_types.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $leavetype, 'table' => '#leave_types_table']);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $leavetype = LeaveType::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.leave_types.modal.edit', compact('leavetype', 'id'));
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
            'title' => 'required|max:50',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('leave_types.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $leavetype        = LeaveType::find($id);
        $leavetype->title = $request->input('title');

        $leavetype->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Leave Type ' . $leavetype->title;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('leave_types.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $leavetype, 'table' => '#leave_types_table']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $leavetype = LeaveType::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Leave Type ' . $leavetype->title;
        $audit->save();

        $leavetype->delete();
        return redirect()->route('leave_types.index')->with('success', _lang('Deleted Successfully'));
    }
}