<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TransactionCategory;
use Exception;
use Illuminate\Http\Request;
use Validator;

class TransactionCategoryController extends Controller {

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
        $assets           = ['datatable'];
        $categories = TransactionCategory::all()->sortBy("name");
        return view('backend.user.transaction_category.list', compact('categories', 'assets'));
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
            return view('backend.user.transaction_category.modal.create');
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
            'name'  => 'required',
            'type'  => 'required',
            'color' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('transaction_categories.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $category              = new TransactionCategory();
        $category->name        = $request->input('name');
        $category->type        = $request->input('type');
        $category->color       = $request->input('color');
        $category->description = $request->input('description');

        $category->save();

        $category->color = '<div class="rounded-circle color-circle" style="background:' . $category->color . '"></div>';

        if (!$request->ajax()) {
            return redirect()->route('transaction_categories.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $category, 'table' => '#expense_categories_table']);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $category = TransactionCategory::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.transaction_category.modal.edit', compact('category', 'id'));
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
            'name'  => 'required',
            'color' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('transaction_categories.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $category              = TransactionCategory::find($id);
        $category->name        = $request->input('name');
        $category->type        = $request->input('type');
        $category->color       = $request->input('color');
        $category->description = $request->input('description');

        $category->save();

        $category->color = '<div class="rounded-circle color-circle" style="background:' . $category->color . '"></div>';

        if (!$request->ajax()) {
            return redirect()->route('transaction_categories.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $category, 'table' => '#expense_categories_table']);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $category = TransactionCategory::find($id);
        try{
            $category->delete();
            return redirect()->route('transaction_categories.index')->with('success', _lang('Deleted Successfully'));
        }catch(\Exception $e){
            return redirect()->route('transaction_categories.index')->with('error', _lang('This items is already exists in other entity'));
        }    
    }
}