<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LedgerExport implements FromView, WithTitle, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $date1;
    protected $date2;

    public function __construct($date1, $date2)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
    }

    public function view(): View
    {
        $data = array();
        $date1 = $this->date1;
        $date2 = $this->date2;

        $data['report_data'] = Transaction::with('account')
            ->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
            ->orderBy('trans_date', 'desc')
            ->get();

        $data['date1'] = Carbon::parse($date1);
        $data['date2'] = Carbon::parse($date2);

        return view('backend.user.reports.exports.ledger', $data);
    }

    public function title(): string
    {
        return 'Ledger';
    }

    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
