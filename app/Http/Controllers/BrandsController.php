<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Brands;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
    public function index()
    {
        $brands = Brands::all();
        return view('backend.user.brand.list', compact('brands'));
    }

    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.brand.create');
        }
    }

    public function store()
    {
        $data = request()->validate([
            'name' => 'required',
        ]);

        $brand = new Brands();
        $brand->name = $data['name'];
        $brand->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Created: ' . $brand->name;
        $audit->save();

        return redirect()->route('brands.index')->with('success', _lang('Brand Created'));
    }

    public function destroy($id)
    {
        $brand = Brands::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Deleted: ' . $brand->name;
        $audit->save();

        $brand->delete();

        return redirect()->route('brands.index')->with('success', _lang('Brand Deleted'));
    }

    public function edit(Request $request, $id)
    {
        $brand = Brands::find($id);
        return view('backend.user.brand.edit', compact('brand', 'id'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $brand = Brands::find($id);
        $brand->name = $request->name;
        $brand->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Updated: ' . $brand->name;
        $audit->save();

        return redirect()->route('brands.index')->with('success', _lang('Brand Updated'));
    }
}
