<?php

namespace App\Exports;

use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SupplierExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.user.reports.exports.suppliers', [
            'vendors' => Vendor::all()
        ]);
    }
}
