<?php

namespace App\Imports;

use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\PendingTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class JournalImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas, WithValidation, SkipsEmptyRows
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

    protected $trans_currency;
    protected $date;

    // constructor
    public function __construct($trans_currency, $date)
    {
        $this->trans_currency = $trans_currency;
        $this->date = $date;
    }

    public function collection(Collection $rows)
    {
        $month = Carbon::parse($this->date)->format('F');
        $year = Carbon::parse($this->date)->format('Y');
        $today = now()->format('d');

        // financial year
        $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
        $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
        $end_month = explode(',', $financial_year)[1];
        $end_day = $start_day + 5;

        // if login as this user dont check the financial year
        if (false) {
            if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
            }
        }

        // calculate total debit and credit
        $total_debit = number_format(array_sum(array_column($rows->toArray(), 'debit')), get_business_option('decimal_places'));
        $total_credit = number_format(array_sum(array_column($rows->toArray(), 'credit')), get_business_option('decimal_places'));

        if (floatval($total_debit) != floatval($total_credit)) {
            return redirect()->back()->withInput()->with('error', _lang('Debit and Credit must be equal'));
        }

        $journal = new Journal();
        $journal->date = Carbon::parse($this->date)->format('Y-m-d');
        $journal->journal_number = BusinessSetting::where('name', 'journal_number')->first()->value;
        $journal->transaction_currency = $this->trans_currency;
        $journal->currency_rate = Currency::where('name', $this->trans_currency)->first()->exchange_rate;
        $journal->transaction_amount      = array_sum(array_column($rows->toArray(), 'debit'));
        $journal->base_currency_amount    = convert_currency($this->trans_currency, request()->activeBusiness->currency, array_sum(array_column($rows->toArray(), 'debit')));
        $journal->user_id = auth()->user()->id;
        $journal->created_by = auth()->user()->id;
        $journal->business_id = request()->activeBusiness->id;
        if(request()->isOwner == true){
            $journal->status = 1;
        }else{
            $journal->status = 0;
        }
        $journal->save();

        BusinessSetting::where('name', 'journal_number')->increment('value');

        foreach ($rows as $row) {
            $currentTime = Carbon::now();
            $date = Date::excelToDateTimeObject($row['trans_date'])->format('Y-m-d');

            $month = Carbon::parse($date)->format('F');
            $today = now()->format('d');

            // financial year
            $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
            $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
            $end_month = explode(',', $financial_year)[1];
            $end_day = $start_day + 5;

            // if login as this user dont check the financial year
            if (false) {
                if ($month !== now()->format('F') || ($today > $end_day && $month == now()->subMonth()->format('F')) || ($today > 25 && $month !== $end_month)) {
                    return redirect()->back()->with('error', _lang('Period Closed'));
                }
            }

            if(has_permission('journals.approve') || request()->isOwner == true) {
                if ($row['debit'] > 0) {
                    Transaction::create([
                        'trans_date'                  => Carbon::parse($date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s'),
                        'account_id'  => get_account($row['account_name'])->id,
                        'transaction_currency'        => $journal->transaction_currency,
                        'currency_rate'               => Currency::where('name', $journal->transaction_currency)->first()->exchange_rate,
                        'dr_cr'       => 'dr',
                        'transaction_amount'      => $row['debit'],
                        'base_currency_amount'    => convert_currency($journal->transaction_currency, request()->activeBusiness->currency, $row['debit']),
                        'description' => $row['description'],
                        'ref_id'      => $journal->id,
                        'ref_type'    => 'journal',
                        'customer_id' => Customer::where('name', $row['customer_name'])->first()->id ?? NULL,
                        'vendor_id' => Customer::where('name', $row['supplier_name'])->first()->id ?? NULL,
                    ]);
                } else if ($row['credit'] > 0) {
                    Transaction::create([
                        'trans_date'                  => Carbon::parse($date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s'),
                        'account_id'  => get_account($row['account_name'])->id,
                        'transaction_currency'        => $journal->transaction_currency,
                        'currency_rate'               => Currency::where('name', $journal->transaction_currency)->first()->exchange_rate,
                        'dr_cr'       => 'cr',
                        'transaction_amount'      => $row['credit'],
                        'base_currency_amount' => convert_currency($journal->transaction_currency, request()->activeBusiness->currency, $row['credit']),
                        'description' => $row['description'],
                        'ref_id'      => $journal->id,
                        'ref_type'    => 'journal',
                        'customer_id' => Customer::where('name', $row['customer_name'])->first()->id ?? NULL,
                        'vendor_id' => Customer::where('name', $row['supplier_name'])->first()->id ?? NULL,
                    ]);
                }
            }else{
                if ($row['debit'] > 0) {
                    PendingTransaction::create([
                        'trans_date'                  => Carbon::parse($date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s'),
                        'account_id'  => get_account($row['account_name'])->id,
                        'transaction_currency'        => $journal->transaction_currency,
                        'currency_rate'               => Currency::where('name', $journal->transaction_currency)->first()->exchange_rate,
                        'dr_cr'       => 'dr',
                        'transaction_amount'      => $row['debit'],
                        'base_currency_amount'    => convert_currency($journal->transaction_currency, request()->activeBusiness->currency, $row['debit']),
                        'description' => $row['description'],
                        'ref_id'      => $journal->id,
                        'ref_type'    => 'journal',
                        'customer_id' => Customer::where('name', $row['customer_name'])->first()->id ?? NULL,
                        'vendor_id' => Customer::where('name', $row['supplier_name'])->first()->id ?? NULL,
                    ]);
                } else if ($row['credit'] > 0) {
                    PendingTransaction::create([
                        'trans_date'                  => Carbon::parse($date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s'),
                        'account_id'  => get_account($row['account_name'])->id,
                        'transaction_currency'        => $journal->transaction_currency,
                        'currency_rate'               => Currency::where('name', $journal->transaction_currency)->first()->exchange_rate,
                        'dr_cr'       => 'cr',
                        'transaction_amount'      => $row['credit'],
                        'base_currency_amount' => convert_currency($journal->transaction_currency, request()->activeBusiness->currency, $row['credit']),
                        'description' => $row['description'],
                        'ref_id'      => $journal->id,
                        'ref_type'    => 'journal',
                        'customer_id' => Customer::where('name', $row['customer_name'])->first()->id ?? NULL,
                        'vendor_id' => Customer::where('name', $row['supplier_name'])->first()->id ?? NULL,
                    ]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'trans_date' => 'required',
            'account_name' => 'required|exists:accounts,account_name',
            'debit' => 'required_if:credit,=,null',
            'credit' => 'required_if:debit,=,null',
            'description' => 'nullable',
            'customer_name' => 'nullable|exists:customers,name',
            'supplier_name' => 'nullable|exists:customers,name',
        ];
    }
}
