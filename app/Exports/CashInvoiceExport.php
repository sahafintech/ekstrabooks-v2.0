<?php

namespace App\Exports;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CashInvoiceExport implements FromView
{
    /**
    * @return \Illuminate\Support\View
    */
    public function view() : View
    {
        return view('backend.user.reports.exports.receipt', [
            'invoices' => Receipt::all()
        ]);
    }
}
