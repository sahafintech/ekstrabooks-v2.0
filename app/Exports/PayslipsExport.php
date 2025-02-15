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

    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function view(): View
    {
        return view('backend.user.reports.exports.payslips', [
            'payslips' => Payroll::where('month', $this->month)->where('year', $this->year)->get(),
            'month' => $this->month,
            'year' => $this->year
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
