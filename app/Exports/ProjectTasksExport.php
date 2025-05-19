<?php

namespace App\Exports;

use App\Models\ProjectTask;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectTasksExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.user.reports.exports.project_tasks', [
            'project_tasks' => ProjectTask::all()
        ]);
    }

    public function title(): string
    {
        return 'Project Tasks';
    }
}
