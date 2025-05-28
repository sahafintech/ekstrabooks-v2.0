<?php

namespace App\Http\Controllers;

use App\Exports\ProjectBudgetsExport;
use App\Http\Controllers\Controller;
use App\Imports\ProjectBudgetsImport;
use App\Models\AuditLog;
use App\Models\ProjectBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProjectBudgetController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (package()->construction_module != 1) {
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_task_id' => 'required|exists:project_tasks,id',
            'cost_code_id' => 'required|exists:cost_codes,id',
            'description' => 'nullable|string',
            'uom' => 'nullable|string',
            'unit_rate' => 'nullable|numeric',
            'original_budgeted_quantity' => 'nullable|numeric',
            'original_budgeted_amount' => 'nullable|numeric',
            'committed_budget_quantity' => 'nullable|numeric',
            'committed_budget_amount' => 'nullable|numeric',
            'received_budget_quantity' => 'nullable|numeric',
            'received_budget_amount' => 'nullable|numeric',
            'actual_budget_quantity' => 'nullable|numeric',
            'actual_budget_amount' => 'nullable|numeric',
        ]);


        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project_budget                  = new ProjectBudget();
        $project_budget->project_id = $request->input('project_id');
        $project_budget->project_task_id           = $request->input('project_task_id');
        $project_budget->cost_code_id    = $request->input('cost_code_id');
        $project_budget->description = $request->input('description');
        $project_budget->uom = $request->input('uom');
        $project_budget->original_budgeted_quantity = $request->input('original_budgeted_quantity') ?? 0;
        $project_budget->original_budgeted_amount = $request->input('original_budgeted_amount') ?? 0;
        $project_budget->committed_budget_quantity = $request->input('committed_budget_quantity') ?? 0;
        $project_budget->committed_budget_amount = $request->input('committed_budget_amount') ?? 0;
        $project_budget->received_budget_quantity = $request->input('received_budget_quantity') ?? 0;
        $project_budget->received_budget_amount = $request->input('received_budget_amount') ?? 0;
        $project_budget->actual_budget_quantity = $request->input('actual_budget_quantity') ?? 0;
        $project_budget->actual_budget_amount = $request->input('actual_budget_amount') ?? 0;
        $project_budget->created_by     = Auth::id();
        $project_budget->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created New Project Budget';
        $audit->save();

        return redirect()->back()->with('success', _lang('Budget Saved Successfully'));
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
            'project_task_id' => 'required|exists:project_tasks,id',
            'cost_code_id' => 'required|exists:cost_codes,id',
            'description' => 'required|string',
            'uom' => 'required|string',
            'original_budgeted_quantity' => 'required|numeric',
            'unit_rate' => 'required|numeric',
            'original_budgeted_amount' => 'required|numeric',
            'committed_budget_quantity' => 'required|numeric',
            'committed_budget_amount' => 'required|numeric',
            'received_budget_quantity' => 'required|numeric',
            'received_budget_amount' => 'required|numeric',
            'actual_budget_quantity' => 'required|numeric',
            'actual_budget_amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project_budget = ProjectBudget::findOrFail($id);

        $project_budget->project_task_id          = $request->input('project_task_id');
        $project_budget->cost_code_id    = $request->input('cost_code_id');
        $project_budget->description = $request->input('description');
        $project_budget->uom = $request->input('uom');
        $project_budget->original_budgeted_quantity = $request->input('original_budgeted_quantity') ?? 0;
        $project_budget->unit_rate = $request->input('unit_rate') ?? 0;
        $project_budget->original_budgeted_amount = $request->input('original_budgeted_amount') ?? 0;
        $project_budget->committed_budget_quantity = $request->input('committed_budget_quantity') ?? 0;
        $project_budget->committed_budget_amount = $request->input('committed_budget_amount') ?? 0;
        $project_budget->received_budget_quantity = $request->input('received_budget_quantity') ?? 0;
        $project_budget->received_budget_amount = $request->input('received_budget_amount') ?? 0;
        $project_budget->actual_budget_quantity = $request->input('actual_budget_quantity') ?? 0;
        $project_budget->actual_budget_amount = $request->input('actual_budget_amount') ?? 0;
        $project_budget->updated_by    = Auth::id();
        $project_budget->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Project Budget';
        $audit->save();

        return redirect()->back()->with('success', _lang('Budget Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $project_budget = ProjectBudget::findOrFail($id);
        $project_budget->deleted_by = Auth::id();
        $project_budget->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Project Budget';
        $audit->save();

        $project_budget->delete();

        return redirect()->back()->with('success', _lang('Budget Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $project_budget = ProjectBudget::findOrFail($id);
            $project_budget->deleted_by = Auth::id();
            $project_budget->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project Budget';
            $audit->save();

            $project_budget->delete();
        }

        return redirect()->back()->with('success', _lang('Budgets Deleted Successfully'));
    }

    /**
     * Bulk delete selected staff members
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $project_budgets = ProjectBudget::whereIn('id', $request->ids)
            ->get();

        foreach ($project_budgets as $project_budget) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project Budget';
            $audit->save();

            $project_budget->delete();
        }

        return redirect()->back()->with('success', _lang('Budgets Deleted Successfully'));
    }

    public function import_project_budgets(Request $request)
    {
        $request->validate([
            'project_budgets_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new ProjectBudgetsImport($request->project_id), $request->file('project_budgets_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Imported Project Budgets';
        $audit->save();

        return redirect()->back()->with('success', _lang('Project Budgets Imported'));
    }

    public function export_project_budgets()
    {
        return Excel::download(new ProjectBudgetsExport, 'project_budgets ' . now()->format('d m Y') . '.xlsx');
    }
}
