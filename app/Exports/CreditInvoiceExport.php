<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CreditInvoiceExport implements FromView
{
    /**
    * @return \Illuminate\Support\View
    */
    public function view() : View
    {
        return view('backend.user.reports.exports.invoice', [
            'invoices' => Invoice::all()
        ]);
    }
}
