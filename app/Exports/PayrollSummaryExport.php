<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollSummaryExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        $month = session('month');
        $year = session('year');

        $current_salary = Payroll::where('month', $month)
            ->where('year', $year)
            ->sum('current_salary');

        $total_allowance = Payroll::where('month', $month)
            ->where('year', $year)
            ->sum('total_allowance');

        $total_deduction = Payroll::where('month', $month)
            ->where('year', $year)
            ->sum('total_deduction');

        $net_salary = Payroll::where('month', $month)
            ->where('year', $year)
            ->sum('net_salary');

        $total_tax = Payroll::where('month', $month)
            ->where('year', $year)
            ->sum('tax_amount');

        $report_data = [
            'current_salary' => $current_salary,
            'total_allowance' => $total_allowance,
            'total_deduction' => $total_deduction,
            'net_salary' => $net_salary,
            'total_tax' => $total_tax,
            'month' => $month,
            'year' => $year,
            'currency' => request()->activeBusiness->currency
        ];

        return view('backend.user.reports.exports.payroll_summary', [
            'report_data' => $report_data
        ]);
    }

    public function title(): string
    {
        return 'Payroll Summary';
    }
}
