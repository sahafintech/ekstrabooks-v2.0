<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomerExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.user.reports.exports.customers', [
            'customers' => Customer::all()
        ]);
    }

    public function title(): string
    {
        return 'Customers';
    }
}
