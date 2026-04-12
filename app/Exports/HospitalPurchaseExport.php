<?php

namespace App\Exports;

use App\Models\HospitalPurchase as Purchase;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class HospitalPurchaseExport implements FromView
{
    protected ?Collection $purchases;

    public function __construct(?Collection $purchases = null)
    {
        $this->purchases = $purchases;
    }

    public function view(): View
    {
        if ($this->purchases === null) {
            $this->purchases = Purchase::with([
                'vendor',
                'business',
                'taxes',
                'items.product',
                'items.account',
                'items.taxes',
            ])
                ->where('cash', 0)
                ->orderBy('purchase_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        return view('backend.user.reports.exports.hospitalpurchase', [
            'purchases' => $this->purchases,
        ]);
    }
}
