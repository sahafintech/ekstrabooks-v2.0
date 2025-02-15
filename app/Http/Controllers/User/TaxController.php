<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tax;
use Illuminate\Http\Request;
use Validator;

class TaxController extends Controller {

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
        $taxs   = Tax::all()->sortByDesc("id");
        return view('backend.user.tax.list', compact('taxs'));
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
            return view('backend.user.tax.modal.create');
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
            'name' => 'required|max:50',
            'rate' => 'required|numeric',
            'account_id' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('taxes.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $tax             = new Tax();
        $tax->name       = $request->input('name');
        $tax->rate       = $request->input('rate');
        $tax->tax_number = $request->input('tax_number');
        $tax->account_id = $request->input('account_id');

        $tax->save();
        $tax->rate = $tax->rate.' %';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Tax '.$tax->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('taxes.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $tax, 'table' => '#taxes_table']);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $tax = Tax::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.tax.modal.edit', compact('tax', 'id'));
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
            'name' => 'required|max:50',
            'rate' => 'required|numeric',
            'account_id' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('taxes.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $tax             = Tax::find($id);
        $tax->name       = $request->input('name');
        $tax->rate       = $request->input('rate');
        $tax->tax_number = $request->input('tax_number');
        $tax->account_id = $request->input('account_id');

        $tax->save();
        $tax->rate = $tax->rate.' %';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Tax '.$tax->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('taxes.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $tax, 'table' => '#taxes_table']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $tax = Tax::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Tax '.$tax->name;
        $audit->save();
        
        $tax->delete();
        return redirect()->route('taxes.index')->with('success', _lang('Deleted Successfully'));
    }
}