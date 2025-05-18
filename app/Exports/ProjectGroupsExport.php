<?php

namespace App\Exports;

use App\Models\ProjectGroup;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectGroupsExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.user.reports.exports.project_groups', [
            'project_groups' => ProjectGroup::all()
        ]);
    }

    public function title(): string
    {
        return 'Project Groups';
    }
}
