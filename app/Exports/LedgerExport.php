<?php

namespace App\Exports;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LedgerExport implements FromView, WithTitle, WithColumnFormatting
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
        $date1 = $this->date1;
        $date2 = $this->date2;

        // Get all accounts with transactions grouped by account
        $accounts = Account::with(['transactions' => function ($query) use ($date1, $date2) {
            $query->whereDate('trans_date', '>=', $date1)
                ->whereDate('trans_date', '<=', $date2)
                ->orderBy('trans_date', 'asc');
        }])
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
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

        // Calculate grand totals and prepare account data
        $grand_total_debit = 0;
        $grand_total_credit = 0;
        $report_data = [];

        foreach ($accounts as $account) {
            if ($account->transactions->isEmpty()) {
                continue;
            }

            $debit_amount = $account->dr_amount ?? 0;
            $credit_amount = $account->cr_amount ?? 0;
            $balance = $account->dr_cr == 'dr' 
                ? $debit_amount - $credit_amount 
                : $credit_amount - $debit_amount;

            $grand_total_debit += $debit_amount;
            $grand_total_credit += $credit_amount;

            $report_data[] = [
                'id' => $account->id,
                'account_name' => $account->account_name,
                'account_number' => $account->account_code,
                'debit_amount' => $debit_amount,
                'credit_amount' => $credit_amount,
                'balance' => $balance,
                'dr_cr' => $account->dr_cr,
                'transactions' => $account->transactions,
            ];
        }

        return view('backend.user.reports.exports.ledger', [
            'report_data' => $report_data,
            'date1' => Carbon::parse($date1),
            'date2' => Carbon::parse($date2),
            'currency' => request()->activeBusiness->currency,
            'business_name' => request()->activeBusiness->name,
            'grand_total_debit' => $grand_total_debit,
            'grand_total_credit' => $grand_total_credit,
            'grand_total_balance' => $grand_total_debit - $grand_total_credit,
        ]);
    }

    public function title(): string
    {
        return 'Ledger';
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
