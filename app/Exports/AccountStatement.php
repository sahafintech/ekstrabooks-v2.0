<?php

namespace App\Exports;

use App\Models\Account;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AccountStatement implements FromView, WithTitle, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $date1;
    protected $date2;
    protected $id;

    public function __construct($date1, $date2, $id)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->id = $id;
    }

    public function view(): View
    {
        $date1 = Carbon::parse($this->date1)->format('Y-m-d');
        $date2 = Carbon::parse($this->date2)->format('Y-m-d');

        $account = Account::where('id', $this->id)->with([
            'transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }
        ])->first();

        $normal_transactions = $account->transactions()
            ->whereNotIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'payslip', 'd invoice income', 'd invoice tax'])
            ->whereDate('trans_date', '>=', $date1)
            ->whereDate('trans_date', '<=', $date2)
            ->get();

        $get_payment_income_transactions = $account->transactions()
            ->whereIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'd invoice income', 'd invoice tax'])
            ->whereDate('trans_date', '>=', $date1)
            ->whereDate('trans_date', '<=', $date2)
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

        $payslip_transactions = $account->transactions()
            ->where('ref_type', 'payslip')
            ->whereDate('trans_date', '>=', $date1)
            ->whereDate('trans_date', '<=', $date2)
            ->get()
            ->groupBy('trans_date');

        $combined_transactions = $this->combine($payment_income_transactions, $payslip_transactions, $normal_transactions);

        return view('backend.user.reports.exports.account_statement', compact('account', 'combined_transactions'));
    }

    function combine($payment_income_transactions, $payslip_transactions, $normal_transactions)
    {
        // Step 1: Aggregate payment transactions
        $aggregated_payment_income_transactions = $payment_income_transactions->map(function ($group, $key) {
            $totalAmount = $group->sum('base_currency_amount');
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
                'dr_cr' => $dr_cr,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'group_key' => $key, // Optional: Keep the group key for reference if needed
            ];
        })->values(); // Flatten the results into a list

        // Step 5: Aggregate payslip transactions
        $aggregated_payslip_transactions = $payslip_transactions->map(function ($group, $key) {
            $totalAmount = $group->sum('base_currency_amount');
            $trans_date  = $group[0]->trans_date;
            $dr_cr  = $group[0]->dr_cr;
            $ref_type = $group[0]->ref_type;
            $ref_id = $group[0]->ref_id;
            $count = $group->count();
            return (object) [
                'trans_date' => $trans_date,
                'description' => "{$count}, Staffs Salary",
                'base_currency_amount' => $totalAmount,
                'dr_cr' => $dr_cr,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'group_key' => $key, // Optional: Keep the group key for reference if needed
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
                'ref_type' => $transaction->ref_type,
                'ref_id' => $transaction->ref_id,
                'currency' => $transaction->transaction_currency,
                'dr_cr' => $transaction->dr_cr,
            ];
        });

        // Step 8: Combine everything into a single collection
        $final_collection = $combined_transactions->merge($normal_transactions_summary);

        // Output or use the $final_collection
        return $final_collection;
    }

    public function title(): string
    {
        return 'Account Statement';
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
