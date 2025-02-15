<?php

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BillInvoiceExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view() : View
    {
        return view('backend.user.reports.exports.bill', [
            'purchases' => Purchase::all()
        ]);
    }
}
