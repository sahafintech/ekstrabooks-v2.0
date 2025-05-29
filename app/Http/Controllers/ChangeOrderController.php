<?php

namespace App\Http\Controllers;

use App\Models\ChangeOrder;
use App\Models\ProjectBudget;
use Illuminate\Http\Request;

class ChangeOrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required',
            'project_task_id' => 'required',
            'cost_code_id' => 'required',
            'change_order_date' => 'required',
            'description' => 'required',
            'quantity' => 'required',
            'unit_cost' => 'required',
            'total_amount' => 'required',
        ]);

        $changeOrder = new ChangeOrder();
        $changeOrder->project_id = $request->project_id;
        $changeOrder->project_task_id = $request->project_task_id;
        $changeOrder->cost_code_id = $request->cost_code_id;
        $changeOrder->change_order_date = $request->change_order_date;
        $changeOrder->description = $request->description;
        $changeOrder->quantity = $request->quantity;
        $changeOrder->unit_cost = $request->unit_cost;
        $changeOrder->total_amount = $request->total_amount;
        $changeOrder->status = 0;
        $changeOrder->created_by = auth()->user()->id;
        $changeOrder->save();

        $projectBudget = ProjectBudget::where('project_id', $request->project_id)->where('cost_code_id', $request->cost_code_id)->where('project_task_id', $request->project_task_id)->first();
        if ($projectBudget) {
            $projectBudget->unit_rate = $request->unit_cost;
            $projectBudget->original_budgeted_quantity = $request->quantity;
            $projectBudget->original_budgeted_amount = $request->total_amount;
            $projectBudget->save();
        }

        return redirect()->back()->with('success', 'Change order created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'project_id' => 'required',
            'project_task_id' => 'required',
            'cost_code_id' => 'required',
            'change_order_date' => 'required',
            'description' => 'required',
            'quantity' => 'required',
            'unit_cost' => 'required',
            'total_amount' => 'required',
        ]);

        $changeOrder = ChangeOrder::find($id);
        $changeOrder->project_id = $request->project_id;
        $changeOrder->project_task_id = $request->project_task_id;
        $changeOrder->cost_code_id = $request->cost_code_id;
        $changeOrder->change_order_date = $request->change_order_date;
        $changeOrder->description = $request->description;
        $changeOrder->quantity = $request->quantity;
        $changeOrder->unit_cost = $request->unit_cost;
        $changeOrder->total_amount = $request->total_amount;
        $changeOrder->status = 0;
        $changeOrder->updated_by = auth()->user()->id;
        $changeOrder->save();

        $projectBudget = ProjectBudget::where('project_id', $request->project_id)->where('cost_code_id', $request->cost_code_id)->where('project_task_id', $request->project_task_id)->first();
        if ($projectBudget) {
            $projectBudget->unit_rate = $request->unit_cost;
            $projectBudget->original_budgeted_quantity = $request->quantity;
            $projectBudget->original_budgeted_amount = $request->total_amount;
            $projectBudget->save();
        }

        return redirect()->back()->with('success', 'Change order updated successfully');
    }

    public function destroy($id)
    {
        $changeOrder = ChangeOrder::find($id);
        $changeOrder->deleted_by = auth()->user()->id;
        $changeOrder->delete();

        return redirect()->back()->with('success', 'Change order deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required',
        ]);

        $changeOrders = ChangeOrder::whereIn('id', $request->ids)->get();
        foreach ($changeOrders as $changeOrder) {
            $changeOrder->deleted_by = auth()->user()->id;
            $changeOrder->delete();
        }

        return redirect()->back()->with('success', 'Change orders deleted successfully');
    }
}