<?php

namespace App\Imports;

use App\Models\Account;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AccountsImport implements ToCollection, WithHeadingRow, WithValidation, WithCalculatedFormulas, SkipsEmptyRows
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $account = new Account();
            $account->account_code = $row['account_code'];
            $account->account_name = $row['account_name'];
            $account->account_type = $row['account_type'];
            $account->description = $row['description'];
            if ($row['account_type'] == 'Bank' || $row['account_type'] == 'Other Current Asset' || $row['account_type'] == 'Cash' || $row['account_type'] == 'Fixed Asset') {
                $account->dr_cr = 'dr';
            } else if ($row['account_type'] == 'Current Liability' || $row['account_type'] == 'Long Term Liability' || $row['account_type'] == 'Equity' || $row['account_type'] == 'Other Income' || $row['account_type'] == 'Sales') {
                $account->dr_cr = 'cr';
            } else if ($row['account_type'] == 'Cost of Sale' || $row['account_type'] == 'Other Expenses' || $row['account_type'] == 'Direct Expenses') {
                $account->dr_cr = 'dr';
            }
            $account->currency = $row['currency'] ?? NULL;
            $account->opening_date = now();
            $account->user_id = request()->activeBusiness->user->id;
            $account->business_id = request()->activeBusiness->id;
            $account->save();
        }
    }

    public function rules(): array
    {
        return [
            'account_code' => 'required|unique:accounts,account_code,NULL,id,business_id,' . request()->activeBusiness->id,
            'account_name' => 'required',
            'account_type' => 'required|exists:account_types,type',
        ];
    }
}
