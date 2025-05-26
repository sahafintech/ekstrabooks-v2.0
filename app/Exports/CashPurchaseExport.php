<?php

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CashPurchaseExport implements FromView
{
    protected $purchases;

    public function __construct($purchases = null)
    {
        $this->purchases = $purchases;
    }

    /**
     * @return \Illuminate\Support\View
     */
    public function view(): View
    {
        if ($this->purchases === null) {
            $this->purchases = Purchase::where('cash', 1)
                ->when(session()->has('cash_purchases_approval_status'), function ($query) {
                    return $query->where('approval_status', session('cash_purchases_approval_status'));
                })
                ->when(session()->has('cash_purchases_vendor_id'), function ($query) {
                    return $query->where('vendor_id', session('cash_purchases_vendor_id'));
                })
                ->when(session()->has('cash_purchases_from') && session()->has('cash_purchases_to'), function ($query) {
                    return $query->whereDate('purchase_date', '>=', session('cash_purchases_from'))
                        ->whereDate('purchase_date', '<=', session('cash_purchases_to'));
                })
                ->get();
        }

        return view('backend.user.reports.exports.cash_purchase', [
            'purchases' => $this->purchases
        ]);
    }
}
