<?php

namespace App\Exports;

use App\Models\ProjectBudget;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectBudgetsExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.user.reports.exports.project_budgets', [
            'project_budgets' => ProjectBudget::all()
        ]);
    }

    public function title(): string
    {
        return 'Project Budgets';
    }
}
