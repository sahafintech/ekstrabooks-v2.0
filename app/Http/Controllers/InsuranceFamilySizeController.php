<?php

namespace App\Http\Controllers;

use App\Models\InsuranceFamilySize;
use Illuminate\Http\Request;

class InsuranceFamilySizeController extends Controller
{
    public function index()
    {
        $sizes = InsuranceFamilySize::all();
        return view('backend.user.invoice.deffered.familysize.list', compact('sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'size' => 'required',
        ]);

        $size = new InsuranceFamilySize();
        $size->size = $request->size;
        $size->save();

        return response()->json(['success' => 'Size added successfully', 'id' => $size->id]);
    }

    public function destroy($id)
    {
        $size = InsuranceFamilySize::find($id);
        $size->delete();
        return response()->json(['success' => 'Size deleted successfully']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'size' => 'required',
        ]);
    }
}
