<?php

namespace App\Exports;

use App\Models\InventoryAdjustment;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventoryAdjustmentExport implements FromView, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        $adjustments = InventoryAdjustment::with('product', 'account')->get();

        return view('backend.user.reports.exports.adjustments', [
            'adjustments' => $adjustments
        ]);
    }

    public function title(): string
    {
        return 'Inventory Adjustments';
    }
}
