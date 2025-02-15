<?php

namespace App\Http\Controllers;

use App\Models\InsuranceBenefit;
use Illuminate\Http\Request;

class InsuranceBenefitController extends Controller
{
    public function index()
    {
        $benefits = InsuranceBenefit::all();
        return view('backend.user.invoice.deffered.benefits.list', compact('benefits'));
    }

    public function create()
    {
        return view('backend.user.invoice.deffered.benefits.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_category' => 'required',
            'name' => 'required',
        ]);

        $benefit = new InsuranceBenefit();
        $benefit->invoice_category = ucwords($request->invoice_category);
        $benefit->name = ucwords($request->name);
        $benefit->save();

        return response()->json(['success' => 'Benefit added successfully', 'id' => $benefit->id]);
    }

    public function destroy($id)
    {
        $benefit = InsuranceBenefit::find($id);
        $benefit->delete();
        return response()->json(['success' => 'Benefit deleted successfully']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'invoice_category' => 'required',
            'name' => 'required',
        ]);

        $benefit = InsuranceBenefit::find($id);
        $benefit->invoice_category = ucwords($request->invoice_category);
        $benefit->name = ucwords($request->name);
        $benefit->save();

        return response()->json(['success' => 'Benefit updated successfully']);
    }
}
