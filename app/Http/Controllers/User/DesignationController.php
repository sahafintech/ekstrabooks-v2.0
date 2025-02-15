<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Designation;
use Illuminate\Http\Request;
use Validator;

class DesignationController extends Controller {

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
        $assets       = ['datatable'];
        $designations = Designation::all()->sortByDesc("id");
        return view('backend.user.designation.list', compact('designations', 'assets'));
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
            return view('backend.user.designation.modal.create');
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
            'name'          => 'required',
            'department_id' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('designations.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $designation                = new Designation();
        $designation->name          = $request->input('name');
        $designation->descriptions  = $request->input('descriptions');
        $designation->department_id = $request->input('department_id');

        $designation->save();
        $designation->department_id = $designation->department->name;

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Designation ' . $designation->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('designations.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $designation, 'table' => '#designations_table']);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $designation = Designation::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.designation.modal.view', compact('designation', 'id'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $designation = Designation::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.designation.modal.edit', compact('designation', 'id'));
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
        $request->validate([
            'name'          => 'required',
            'department_id' => 'required',
        ]);

        $designation                = Designation::find($id);
        $designation->name          = $request->input('name');
        $designation->descriptions  = $request->input('descriptions');
        $designation->department_id = $request->input('department_id');
        $designation->save();
        $designation->department_id = $designation->department->name;

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Designation ' . $designation->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('designations.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $designation, 'table' => '#designations_table']);
        }

    }

    public function get_designations(Request $request, $department_id) {
        $designations = Designation::where('department_id', $department_id)->get();
        return response()->json($designations);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $designation = Designation::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Designation ' . $designation->name;
        $audit->save();

        try {
            $designation->delete();
            return redirect()->route('designations.index')->with('success', _lang('Deleted Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('designations.index')->with('error', _lang('This items is already exists in other entity'));
        }
    }
}