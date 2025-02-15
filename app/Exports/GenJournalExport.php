<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GenJournalExport implements FromView, WithTitle, WithColumnFormatting
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

        $normal_transactions = Transaction::with('account')
            ->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
            ->whereNotIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'payslip', 'd invoice income', 'd invoice tax'])
            ->get();

        $get_payment_income_transactions = Transaction::with('account')
            ->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
            ->whereIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'd invoice income', 'd invoice tax'])
            ->get(); // Fetch the transactions as a collection

        $payment_income_transactions = $get_payment_income_transactions->groupBy(function ($transaction) {
            // Check if the ref_id contains a comma
            if (strpos($transaction->ref_id, ',') !== false) {
                // Extract the second part of ref_id
                return explode(',', $transaction->ref_id)[1];
            }
            // Otherwise, return the ref_id as is
            return $transaction->ref_id;
        });

        $payslip_transactions = Transaction::with('account')
            ->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
            ->where('ref_type', 'payslip')
            ->get()
            ->groupBy('trans_date');

        $data['report_data'] = $this->combine($payment_income_transactions, $payslip_transactions, $normal_transactions);

        $data['date1'] = Carbon::parse($date1);
        $data['date2'] = Carbon::parse($date2);

        return view('backend.user.reports.exports.gen_journal', $data);
    }

    function combine($payment_income_transactions, $payslip_transactions, $normal_transactions)
    {
        // Step 1: Aggregate payment transactions
        $aggregated_payment_income_transactions = $payment_income_transactions->map(function ($group, $key) {
            $totalAmount = $group->sum('base_currency_amount');
            $totalTransAmount = $group->sum('transaction_amount');
            $trans_date  = $group[0]->trans_date;
            $dr_cr  = $group[0]->dr_cr;
            $ref_type = $group[0]->ref_type;
            $ref_id = $group[0]->ref_id;
            $count = $group->count();
            if ($ref_type == 'invoice payment' || $ref_type == 'd invoice payment') {
                $description = $count . ' Invoices Payment';
            } else if ($ref_type == 'bill payment') {
                $description = $count . ' Bills Payment';
            } else if ($ref_type == 'd invoice income') {
                $description = 'Deffered Earnings Income From Invoice #' . Invoice::find($ref_id)->invoice_number;
            } else if ($ref_type == 'd invoice tax') {
                $description = 'Deffered Tax From Invoice #' . Invoice::find($ref_id)?->invoice_number;
            }
            return (object) [
                'trans_date' => $trans_date,
                'description' => "{$description}",
                'base_currency_amount' => $totalAmount,
                'transaction_currency' => $group[0]->transaction_currency,
                'transaction_amount' => $totalTransAmount,
                'currency_rate' => $group[0]->currency_rate,
                'dr_cr' => $dr_cr,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'payee_name' => $group[0]->payee_name,
                'group_key' => $key, // Optional: Keep the group key for reference if needed
                'account' => (object) [
                    'id' => $group[0]->account->id,
                    'account_name' => $group[0]->account->account_name,
                    'account_number' => $group[0]->account->account_number,
                ],
            ];
        })->values(); // Flatten the results into a list

        // Step 5: Aggregate payslip transactions
        $aggregated_payslip_transactions = $payslip_transactions->map(function ($group, $key) {
            $totalAmount = $group->sum('base_currency_amount');
            $totalTransAmount = $group->sum('transaction_amount');
            $trans_date  = $group[0]->trans_date;
            $dr_cr  = $group[0]->dr_cr;
            $ref_type = $group[0]->ref_type;
            $ref_id = $group[0]->ref_id;
            $count = $group->count();
            return (object) [
                'trans_date' => $trans_date,
                'description' => "{$count}, Staffs Salary",
                'base_currency_amount' => $totalAmount,
                'transaction_currency' => $group[0]->transaction_currency,
                'transaction_amount' => $totalTransAmount,
                'currency_rate' => $group[0]->currency_rate,
                'dr_cr' => $dr_cr,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'payee_name' => $group[0]->payee_name,
                'group_key' => $key, // Optional: Keep the group key for reference if needed
                'account' => (object) [
                    'id' => $group[0]->account->id,
                    'account_name' => $group[0]->account->account_name,
                    'account_number' => $group[0]->account->account_number,
                ],
            ];
        })->values(); // Flatten the results into a list

        // Step 6: Combine all aggregated transactions
        $combined_transactions = collect()
            ->merge($aggregated_payment_income_transactions)
            ->merge($aggregated_payslip_transactions);

        // Step 7: Include normal transactions as is
        $normal_transactions_summary = $normal_transactions->map(function ($transaction) {
            return (object) [
                'trans_date' => $transaction->trans_date,
                'description' => $transaction->description,
                'base_currency_amount' => $transaction->base_currency_amount,
                'transaction_currency' => $transaction->transaction_currency,
                'transaction_amount' => $transaction->transaction_amount,
                'currency_rate' => $transaction->currency_rate,
                'ref_type' => $transaction->ref_type,
                'ref_id' => $transaction->ref_id,
                'payee_name' => $transaction->payee_name,
                'currency' => $transaction->transaction_currency,
                'dr_cr' => $transaction->dr_cr,
                'account' => (object) [
                    'id' => $transaction->account->id,
                    'account_name' => $transaction->account->account_name,
                    'account_number' => $transaction->account->account_number,
                ],
            ];
        });

        // Step 8: Combine everything into a single collection
        $final_collection = $combined_transactions->merge($normal_transactions_summary);

        // Output or use the $final_collection
        return $final_collection;
    }

    public function title(): string
    {
        return 'Ledger';
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
