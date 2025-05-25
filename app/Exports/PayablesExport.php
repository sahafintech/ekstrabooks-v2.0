<?php

namespace App\Exports;

use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PayablesExport implements FromView
{
    protected $date1;
    protected $date2;
    protected $vendor_id;
    protected $search;

    public function __construct($date1, $date2, $vendor_id = '', $search = '')
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->vendor_id = $vendor_id;
        $this->search = $search;
    }

    public function view(): View
    {
        // Get payables data
        $purchases = Purchase::with('vendor')
            ->when($this->vendor_id, function ($query) {
                return $query->where('vendor_id', $this->vendor_id);
            })
            ->whereRaw("date(purchases.purchase_date) >= ? AND date(purchases.purchase_date) <= ?", [$this->date1, $this->date2])
            ->get();

        // Prepare data array for report
        $data_array = [];

        foreach ($purchases as $purchase) {
            $due_amount = $purchase->grand_total - $purchase->paid;

            // Skip purchases that are fully paid
            if ($due_amount <= 0) {
                continue;
            }

            $data_array[] = [
                'id' => $purchase->id,
                'purchase_number' => $purchase->bill_no,
                'vendor_id' => $purchase->vendor_id,
                'vendor_name' => $purchase->vendor->name,
                'purchase_date' => $purchase->purchase_date,
                'due_date' => $purchase->due_date,
                'grand_total' => $purchase->grand_total,
                'paid_amount' => $purchase->paid,
                'due_amount' => $due_amount,
                'status' => $purchase->status,
            ];
        }

        // Apply search filter if provided
        if (!empty($this->search)) {
            $data_array = array_filter($data_array, function ($item) {
                return stripos($item['vendor_name'], $this->search) !== false ||
                    stripos($item['purchase_number'], $this->search) !== false;
            });
            // Re-index array after filtering
            $data_array = array_values($data_array);
        }

        // Calculate totals
        $total_grand = 0;
        $total_paid = 0;
        $total_due = 0;

        foreach ($data_array as $item) {
            $total_grand += $item['grand_total'];
            $total_paid += $item['paid_amount'];
            $total_due += $item['due_amount'];
        }

        return view('backend.user.reports.exports.payables', [
            'payables' => collect($data_array),
            'date1' => Carbon::parse($this->date1),
            'date2' => Carbon::parse($this->date2),
            'grand_total' => $total_grand,
            'paid_amount' => $total_paid,
            'due_amount' => $total_due,
            'currency' => request()->activeBusiness->currency,
            'business_name' => request()->activeBusiness->name
        ]);
    }
}