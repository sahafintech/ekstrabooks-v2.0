<?php

namespace App\Imports;

use App\Models\CostCode;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProjectImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $start_date = $row['start_date'] ? Date::excelToDateTimeObject($row['start_date'])->format('Y-m-d') : null;
            $end_date = $row['end_date'] ? Date::excelToDateTimeObject($row['end_date'])->format('Y-m-d') : null;
            $completion_date = $row['completion_date'] ? Date::excelToDateTimeObject($row['completion_date'])->format('Y-m-d') : null;

            $project = new Project();
            $project->project_code = $row['project_code'];
            $project->project_name = $row['project_name'];
            $project->project_group_id = $row['project_group'] ? ProjectGroup::where('group_name', $row['project_group'])->first()->id : null;
            $project->customer_id = $row['customer'] ? Customer::where('name', $row['customer'])->first()->id : null;
            $project->project_manager_id = $row['project_manager'] ? Employee::where('name', $row['project_manager'])->first()->id : null;
            $project->start_date = $start_date;
            $project->end_date = $end_date;
            $project->status = $row['status'];
            $project->priority = $row['priority'];
            $project->completion_date = $completion_date;
            $project->project_currency = $row['project_currency'];
            $project->description = $row['description'];
            $project->created_by = Auth::id();
            $project->save();
        }
    }

    public function rules(): array
    {
        return [
            '*.project_code' => 'required|unique:projects,project_code',
            '*.project_name' => 'required',
            '*.project_group' => 'nullable|exists:project_groups,group_name',
            '*.customer' => 'nullable|exists:customers,name',
            '*.project_manager' => 'nullable|exists:employees,name',
            '*.start_date' => 'nullable',
            '*.end_date' => 'nullable',
            '*.status' => 'nullable|in:Planning,In Progress,Completed,On Hold,Cancelled,Archived',
            '*.priority' => 'nullable|in:Low,Medium,High',
            '*.completion_date' => 'nullable',
            '*.project_currency' => 'required',
            '*.description' => 'nullable',
        ];
    }
}
