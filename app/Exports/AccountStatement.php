<?php

namespace App\Exports;

use App\Models\Account;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AccountStatement implements FromView, WithTitle, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $date1;
    protected $date2;
    protected $id;

    public function __construct($date1, $date2, $id)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->id = $id;
    }

    public function view(): View
    {
        $date1 = Carbon::parse($this->date1)->format('Y-m-d');
        $date2 = Carbon::parse($this->date2)->format('Y-m-d');

        $account = Account::where('id', $this->id)->with('transactions')->first();

        $transactions = $account->transactions()
            ->whereDate('trans_date', '>=', $date1)
            ->whereDate('trans_date', '<=', $date2)
            ->orderBy('trans_date', 'asc')
            ->get();

        return view('backend.user.reports.exports.account_statement', compact('account', 'transactions'));
    }

    public function title(): string
    {
        return 'Account Statement';
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
