<?php

namespace App\Exports;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class IncomeStatementExport implements FromView, WithTitle, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */


    protected $date1;
    protected $date2;

    public function __construct($date1, $date2)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
    }

    public function view(): View
    {
        $data = array();
        $date1 = $this->date1;
        $date2 = $this->date2;

        $data['report_data']['sales_and_income'] = Account::where(function ($query) {
            $query->where('account_type', 'Other Income')
                ->orWhere('account_type', 'Sales');
        })
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['date1'] = Carbon::parse($date1);
        $data['date2'] = Carbon::parse($date2);


        return view('backend.user.reports.exports.income_statement', $data);
    }

    public function title(): string
    {
        return 'Income Statement';
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
