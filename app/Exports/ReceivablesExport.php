<?php

namespace App\Exports;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReceivablesExport implements FromView
{
    protected $date1;
    protected $date2;
    protected $customer_id;

    public function __construct($date1, $date2, $customer_id)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->customer_id = $customer_id;
    }

    public function view(): View
    {
        $data = array();
        $date1 = $this->date1;
        $date2 = $this->date2;

        $receivables = Invoice::with('customer')
            ->when($this->customer_id, function ($query, $customer_id) {
                return $query->where('customer_id', $customer_id);
            })
            ->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
            ->where('is_recurring', 0)
            ->get();

        $date1 = Carbon::parse($date1);
        $date2 = Carbon::parse($date2);

        return view('backend.user.reports.exports.receivables', compact('receivables', 'date1', 'date2'));
    }
}
