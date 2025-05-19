<?php

namespace App\Imports;

use App\Models\ProjectTask;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

class ProjectTaskImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */

    private $project_id;

    public function __construct($project_id)
    {
        $this->project_id = $project_id;
    }

    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $project_task = new ProjectTask();
            $project_task->task_code = $row['task_code'];
            $project_task->description = $row['description'];
            $project_task->status = $row['status'];
            $project_task->completed_percent = $row['completed_percent'];
            $project_task->project_id = $this->project_id;
            $project_task->created_by = Auth::id();
            $project_task->save();
        }
    }

    public function rules(): array
    {
        return [
            '*.task_code' => 'required',
            '*.description' => 'nullable',
            '*.status' => 'required',
            '*.completed_percent' => 'required',
        ];
    }
}
