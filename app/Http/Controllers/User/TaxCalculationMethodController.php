<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TaxCalculationMethod;
use App\Models\TaxBracket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaxCalculationMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $taxCalculationMethods = TaxCalculationMethod::all();
        return view('backend.user.tax_calculation_methods.index', compact('taxCalculationMethods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.user.tax_calculation_methods.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'method_type' => 'required|string|in:fixed,progressive',
            'description' => 'nullable|string',
            'brackets' => 'required|array|min:1',
            'brackets.*.income_from' => 'required|numeric|min:0',
            'brackets.*.income_to' => 'nullable|numeric|min:0',
            'brackets.*.rate' => 'required|numeric|min:0',
            'brackets.*.fixed_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $taxCalculationMethod = TaxCalculationMethod::create([
                'name' => $request->name,
                'method_type' => $request->method_type,
                'description' => $request->description,
                'business_id' => request()->activeBusiness->id,
            ]);

            foreach ($request->brackets as $bracket) {
                TaxBracket::create([
                    'tax_calculation_method_id' => $taxCalculationMethod->id,
                    'income_from' => $bracket['income_from'],
                    'income_to' => $bracket['income_to'] ?? null,
                    'rate' => $bracket['rate'],
                    'fixed_amount' => $bracket['fixed_amount'] ?? 0,
                ]);
            }

            DB::commit();
            
            if (!$request->ajax()) {
                return redirect()->route('tax-calculation-methods.index')->with('success', _lang('Created Successfully'));
            } else {
                return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Created Successfully'), 'data' => $taxCalculationMethod]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            
            if (!$request->ajax()) {
                return back()->with('error', _lang('Something went wrong! Please try again.'));
            } else {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TaxCalculationMethod $taxCalculationMethod)
    {
        $taxCalculationMethod->load('brackets');
        return view('backend.user.tax_calculation_methods.show', compact('taxCalculationMethod'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TaxCalculationMethod $taxCalculationMethod)
    {
        $taxCalculationMethod->load('brackets');
        return view('backend.user.tax_calculation_methods.edit', compact('taxCalculationMethod'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaxCalculationMethod $taxCalculationMethod)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'method_type' => 'required|string|in:fixed,progressive',
            'description' => 'nullable|string',
            'brackets' => 'required|array|min:1',
            'brackets.*.income_from' => 'required|numeric|min:0',
            'brackets.*.income_to' => 'nullable|numeric|min:0',
            'brackets.*.rate' => 'required|numeric|min:0',
            'brackets.*.fixed_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $taxCalculationMethod->update([
                'name' => $request->name,
                'method_type' => $request->method_type,
                'description' => $request->description,
            ]);

            // Delete existing brackets
            $taxCalculationMethod->brackets()->delete();

            // Create new brackets
            foreach ($request->brackets as $bracket) {
                TaxBracket::create([
                    'tax_calculation_method_id' => $taxCalculationMethod->id,
                    'income_from' => $bracket['income_from'],
                    'income_to' => $bracket['income_to'] ?? null,
                    'rate' => $bracket['rate'],
                    'fixed_amount' => $bracket['fixed_amount'] ?? 0,
                ]);
            }

            DB::commit();
            
            if (!$request->ajax()) {
                return redirect()->route('tax-calculation-methods.index')->with('success', _lang('Updated Successfully'));
            } else {
                return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $taxCalculationMethod]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            
            if (!$request->ajax()) {
                return back()->with('error', _lang('Something went wrong! Please try again.'));
            } else {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TaxCalculationMethod $taxCalculationMethod)
    {
        DB::beginTransaction();
        try {
            // Check if this tax calculation method is being used in any payslips
            $payslipsCount = DB::table('payslips')
                ->where('tax_calculation_method_id', $taxCalculationMethod->id)
                ->count();
                
            if ($payslipsCount > 0) {
                return response()->json(['result' => 'error', 'message' => _lang('This tax calculation method is in use and cannot be deleted.')]);
            }
            
            // Delete brackets
            $taxCalculationMethod->brackets()->delete();
            
            // Delete the method
            $taxCalculationMethod->delete();
            
            DB::commit();
            
            return response()->json(['result' => 'success', 'action' => 'delete', 'message' => _lang('Deleted Successfully'), 'id' => $taxCalculationMethod->id]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
