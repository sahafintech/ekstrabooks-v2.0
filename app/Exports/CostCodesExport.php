<?php

namespace App\Exports;

use App\Models\CostCode;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class CostCodesExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.user.reports.exports.cost_codes', [
            'cost_codes' => CostCode::all()
        ]);
    }

    public function title(): string
    {
        return 'Cost Codes';
    }
}
