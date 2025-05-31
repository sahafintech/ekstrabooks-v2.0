<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayslipsExport implements FromView, WithTitle, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function view(): View
    {
        return view('backend.user.reports.exports.payslips', [
            'payslips' => Payroll::where('month', session('month'))->where('year', session('year'))->get(),
            'month' => session('month'),
            'year' => session('year')
        ]);
    }

    public function title(): string
    {
        return 'Payroll';
    }

    public function columnFormats(): array
    {
        return [
            'i' => '0.00',
            'j' => '0.00',
            'k' => '0.00',
            'l' => '0.00',
            'm' => '0.00',
            'n' => '0.00',
        ];
    }
}
