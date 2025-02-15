<?php

namespace App\Imports;

use App\Models\UncategorizedTransaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankStatementImport implements ToModel, WithHeadingRow
{
    protected $account_id;

    public function __construct($account_id)
    {
        $this->account_id = $account_id;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new UncategorizedTransaction([
            'trans_date' => Carbon::createFromFormat('d/m/Y', $row["date"])->setTime(Carbon::now()->hour, Carbon::now()->minute, Carbon::now()->second)->format('Y-m-d H:i:s'),
            'account_id' => $this->account_id,
            'deposit' => $row["deposit"],
            'withdrawal' => $row["withdrawal"],
            'reference' => $row["reference"],
            'description' => $row["description"],
            'user_id' => auth()->user()->id,
            'business_id' => request()->activeBusiness->id,
        ]);
    }
}
