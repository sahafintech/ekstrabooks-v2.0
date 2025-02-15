<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessType;
use Illuminate\Http\Request;
use Validator;

class BusinessTypeController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $assets        = ['datatable'];
        $businesstypes = BusinessType::all()->sortByDesc("id");
        return view('backend.admin.business_type.list', compact('businesstypes', 'assets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        if (!$request->ajax()) {
            return view('backend.admin.business_type.create');
        } else {
            return view('backend.admin.business_type.modal.create');
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
            'name'   => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('business_types.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $businesstype         = new BusinessType();
        $businesstype->name   = $request->input('name');
        $businesstype->status = $request->input('status');

        $businesstype->save();

        //Prefix Output
        $businesstype->status = status($businesstype->status);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Business Type Created: ' . $businesstype->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('business_types.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $businesstype, 'table' => '#business_types_table']);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $businesstype = BusinessType::find($id);
        if (!$request->ajax()) {
            return view('backend.admin.business_type.edit', compact('businesstype', 'id'));
        } else {
            return view('backend.admin.business_type.modal.edit', compact('businesstype', 'id'));
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
            'name'   => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('business_types.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $businesstype         = BusinessType::find($id);
        $businesstype->name   = $request->input('name');
        $businesstype->status = $request->input('status');

        $businesstype->save();

        //Prefix Output
        $businesstype->status = status($businesstype->status);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Business Type Updated: ' . $businesstype->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('business_types.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $businesstype, 'table' => '#business_types_table']);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $businesstype = BusinessType::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Business Type Deleted: ' . $businesstype->name;
        $audit->save();

        $businesstype->delete();
        return redirect()->route('business_types.index')->with('success', _lang('Deleted Successfully'));
    }
}