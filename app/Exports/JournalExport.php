<?php

namespace App\Exports;

use App\Models\Journal;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class JournalExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $id;

    // constructor
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function view(): View
    {
        return view('backend.user.reports.exports.journal', [
            'journals' => Journal::find($this->id),
            'transactions' => Transaction::where('ref_id', $this->id)->where('ref_type', 'journal')->get(),
        ]);
    }
}
