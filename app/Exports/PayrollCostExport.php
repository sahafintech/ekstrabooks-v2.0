<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollCostExport implements FromView, WithTitle
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        $report_data = array();
        $month = session('month');
        $year = session('year');

        $report_data['payroll'] = Payroll::with('staff')
            ->select('payslips.*')
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $report_data['total_netsalary'] = $report_data['payroll']->sum('net_salary');
        $report_data['total_basicsalary'] = $report_data['payroll']->sum('current_salary');
        $report_data['total_allowance'] = $report_data['payroll']->sum('total_allowance');
        $report_data['total_deduction'] = $report_data['payroll']->sum('total_deduction');
        $report_data['total_tax'] = $report_data['payroll']->sum('tax_amount');
        $report_data['total_advance'] = $report_data['payroll']->sum('advance');

        $report_data['month'] = $month;
        $report_data['year'] = $year;

        return view('backend.user.reports.exports.payroll_cost', [
            'report_data' => $report_data
        ]);
    }

    public function title(): string
    {
        return 'Payroll Cost';
    }
}
