<?php

namespace App\Imports;

use App\Models\CostCode;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectTask;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

class ProjectBudgetsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $project_budget = new ProjectBudget();
            $project_budget->project_id = Project::where('project_code', $row['project_code'])->first()->id;
            $project_budget->project_task_id = ProjectTask::where('task_code', $row['task_code'])->first()->id;
            $project_budget->cost_code_id = CostCode::where('code', $row['cost_code'])->first()->id;
            $project_budget->description = $row['description'];
            $project_budget->uom = $row['uom'] ?? null;
            $project_budget->unit_rate = $row['unit_rate'] ?? 0;
            $project_budget->original_budgeted_quantity = $row['original_budgeted_quantity'] ?? 0;
            $project_budget->original_budgeted_amount = $row['original_budgeted_amount'] ?? 0;
            $project_budget->revised_budgeted_quantity = $row['revised_budgeted_quantity'] ?? 0;
            $project_budget->revised_budgeted_amount = $row['revised_budgeted_amount'] ?? 0;
            $project_budget->revised_committed_quantity = $row['revised_committed_quantity'] ?? 0;
            $project_budget->revised_committed_amount = $row['revised_committed_amount'] ?? 0;
            $project_budget->committed_open_quantity = $row['committed_open_quantity'] ?? 0;
            $project_budget->committed_open_amount = $row['committed_open_amount'] ?? 0;
            $project_budget->committed_received_quantity = $row['committed_received_quantity'] ?? 0;
            $project_budget->committed_invoiced_quantity = $row['committed_invoiced_quantity'] ?? 0;
            $project_budget->committed_invoiced_amount = $row['committed_invoiced_amount'] ?? 0;
            $project_budget->actual_quantity = $row['actual_quantity'] ?? 0;
            $project_budget->actual_amount = $row['actual_amount'] ?? 0;
            $project_budget->created_by = Auth::id();
            $project_budget->save();
        }
    }

    public function rules(): array
    {
        return [
            '*.project_code' => 'required|exists:projects,project_code',
            '*.task_code' => 'required|exists:project_tasks,task_code',
            '*.cost_code' => 'required|exists:cost_codes,code',
            '*.description' => 'nullable',
            '*.uom' => 'nullable',
            '*.unit_rate' => 'nullable',
            '*.original_budgeted_quantity' => 'nullable',
            '*.original_budgeted_amount' => 'nullable',
            '*.revised_budgeted_quantity' => 'nullable',
            '*.revised_budgeted_amount' => 'nullable',
            '*.revised_committed_quantity' => 'nullable',
            '*.revised_committed_amount' => 'nullable',
            '*.committed_open_quantity' => 'nullable',
            '*.committed_open_amount' => 'nullable',
            '*.committed_received_quantity' => 'nullable',
            '*.committed_invoiced_quantity' => 'nullable',
            '*.committed_invoiced_amount' => 'nullable',
            '*.actual_quantity' => 'nullable',
            '*.actual_amount' => 'nullable',
        ];
    }
}
