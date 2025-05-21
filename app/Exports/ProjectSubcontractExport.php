<?php

namespace App\Exports;

use App\Models\ProjectSubcontract;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProjectSubcontractExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view() : View
    {
        return view('backend.user.reports.exports.project_subcontracts', [
            'project_subcontracts' => ProjectSubcontract::all()
        ]);
    }
}
