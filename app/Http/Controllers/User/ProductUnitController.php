<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Validator;

class ProductUnitController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $assets = ['datatable'];
        $productunits = ProductUnit::all()->sortByDesc("id");
        return view('backend.user.product_unit.list', compact('productunits', 'assets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.product_unit.modal.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit' => 'required|max:30',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('product_units.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $productunit       = new ProductUnit();
        $productunit->unit = $request->input('unit');
        $productunit->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Created' . ' ' . $productunit->unit;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('product_units.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $productunit, 'table' => '#product_units_table']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $productunit = ProductUnit::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.product_unit.modal.edit', compact('productunit', 'id'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'unit' => 'required|max:30',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('product_units.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $productunit       = ProductUnit::find($id);
        $productunit->unit = $request->input('unit');

        $productunit->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Updated' . ' ' . $productunit->unit;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('product_units.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $productunit, 'table' => '#product_units_table']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $productunit = ProductUnit::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Deleted' . ' ' . $productunit->unit;
        $audit->save();

        $productunit->delete();
        return redirect()->route('product_units.index')->with('success', _lang('Deleted Successfully'));
    }
}
