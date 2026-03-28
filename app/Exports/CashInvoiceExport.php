<?php

namespace App\Exports;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CashInvoiceExport implements FromView
{
    protected $receipts;

    public function __construct($receipts = null)
    {
        $this->receipts = $receipts;
    }

    /**
    * @return \Illuminate\Support\View
    */
    public function view() : View
    {
        if ($this->receipts === null) {
            $this->receipts = Receipt::with([
                'customer',
                'project',
                'taxes',
                'items.product',
                'items.taxes',
                'transactions.account',
            ])
                ->orderBy('receipt_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        return view('backend.user.reports.exports.receipt', [
            'invoices' => $this->receipts,
        ]);
    }
}
