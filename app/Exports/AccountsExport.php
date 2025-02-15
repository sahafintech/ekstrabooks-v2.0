<?php

namespace App\Exports;

use App\Models\Account;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class AccountsExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.user.reports.exports.accounts', [
            'accounts' => Account::where('business_id', request()->activeBusiness->id)
                ->orWhere('business_id', null)
                ->get()
        ]);
    }

    public function title(): string
    {
        return 'Accounts';
    }
}
