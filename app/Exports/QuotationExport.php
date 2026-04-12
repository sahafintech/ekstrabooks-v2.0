<?php

namespace App\Exports;

use App\Models\Quotation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class QuotationExport implements FromView
{
    protected ?Collection $quotations;

    public function __construct(?Collection $quotations = null)
    {
        $this->quotations = $quotations;
    }

    public function view(): View
    {
        if ($this->quotations === null) {
            $this->quotations = Quotation::with([
                'customer',
                'taxes',
                'items.product',
                'items.taxes',
            ])
                ->orderBy('quotation_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        return view('backend.user.reports.exports.quotation', [
            'quotations' => $this->quotations,
        ]);
    }
}
