<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TransactionMethod;
use Illuminate\Http\Request;
use Validator;

class TransactionMethodController extends Controller {

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
        $assets             = ['datatable'];
        $transactionmethods = TransactionMethod::all()->sortByDesc("id");
        return view('backend.user.transaction_method.list', compact('transactionmethods', 'assets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        if (!$request->ajax()) {
            return view('backend.user.transaction_method.create');
        } else {
            return view('backend.user.transaction_method.modal.create');
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
            'name'        => 'required|max:50',
            'status'      => 'required',
            'user_id'     => '',
            'business_id' => '',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('transaction_methods.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $transactionmethod         = new TransactionMethod();
        $transactionmethod->name   = $request->input('name');
        $transactionmethod->status = $request->input('status');

        $transactionmethod->save();
        $transactionmethod->status = status($request->status);

        if (!$request->ajax()) {
            return redirect()->route('transaction_methods.index')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $transactionmethod, 'table' => '#transaction_methods_table']);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $transactionmethod = TransactionMethod::find($id);
        if (!$request->ajax()) {
            return view('backend.user.transaction_method.edit', compact('transactionmethod', 'id'));
        } else {
            return view('backend.user.transaction_method.modal.edit', compact('transactionmethod', 'id'));
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
            'name'        => 'required|max:50',
            'status'      => 'required',
            'user_id'     => '',
            'business_id' => '',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('transaction_methods.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $transactionmethod         = TransactionMethod::find($id);
        $transactionmethod->name   = $request->input('name');
        $transactionmethod->status = $request->input('status');

        $transactionmethod->save();
        $transactionmethod->status = status($request->status);

        if (!$request->ajax()) {
            return redirect()->route('transaction_methods.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $transactionmethod, 'table' => '#transaction_methods_table']);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $transactionmethod = TransactionMethod::find($id);
        $transactionmethod->delete();
        return redirect()->route('transaction_methods.index')->with('success', _lang('Deleted Successfully'));
    }
}