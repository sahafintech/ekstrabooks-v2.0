<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class IncomeByCustomerExport implements FromView
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
        $date1 = $this->date1;
        $date2 = $this->date2;
        $customer_id = $this->customer_id;

        // Get credit invoices data
        $credit_invoices_query = Invoice::with('customer')
            ->selectRaw('customer_id, SUM(grand_total) as total_income, sum(paid) as total_paid')
            ->when($customer_id, function ($query, $customer_id) {
                return $query->where('customer_id', $customer_id);
            })
            ->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
            ->where('is_recurring', 0)
            ->where('status', '!=', 0)
            ->groupBy('customer_id');

        // Get cash invoices data
        $cash_invoices_query = Receipt::with('customer')
            ->selectRaw('customer_id, SUM(grand_total) as total_income, SUM(grand_total) as total_paid')
            ->when($customer_id, function ($query, $customer_id) {
                return $query->where('customer_id', $customer_id);
            })
            ->whereRaw("date(receipts.receipt_date) >= '$date1' AND date(receipts.receipt_date) <= '$date2'")
            ->groupBy('customer_id');

        // Get the data from both queries
        $credit_invoices = $credit_invoices_query->get();
        $cash_invoices = $cash_invoices_query->get();

        // Process data for the report
        $report_data = [];

        // Process credit invoices
        foreach ($credit_invoices as $invoice) {
            $report_data[$invoice->customer_id] = [
                'id' => $invoice->customer_id,
                'customer_id' => $invoice->customer_id,
                'customer_name' => $invoice->customer->name,
                'total_income' => $invoice->total_income,
                'total_paid' => $invoice->total_paid,
                'total_due' => $invoice->total_income - $invoice->total_paid,
            ];
        }

        // Process cash invoices
        foreach ($cash_invoices as $invoice) {
            if (isset($report_data[$invoice->customer_id])) {
                $report_data[$invoice->customer_id]['total_income'] += $invoice->total_income;
                $report_data[$invoice->customer_id]['total_paid'] += $invoice->total_paid;
                $report_data[$invoice->customer_id]['total_due'] = $report_data[$invoice->customer_id]['total_income'] - $report_data[$invoice->customer_id]['total_paid'];
            } else {
                $report_data[$invoice->customer_id] = [
                    'id' => $invoice->customer_id,
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer->name,
                    'total_income' => $invoice->total_income,
                    'total_paid' => $invoice->total_paid,
                    'total_due' => $invoice->total_income - $invoice->total_paid,
                ];
            }
        }

        // Convert to array and sort by customer name
        $data_array = array_values($report_data);
        usort($data_array, function ($a, $b) {
            return strcmp($a['customer_name'], $b['customer_name']);
        });

        // Calculate grand totals
        $grand_total_income = 0;
        $grand_total_paid = 0;
        $grand_total_due = 0;

        foreach ($data_array as $item) {
            $grand_total_income += $item['total_income'];
            $grand_total_paid += $item['total_paid'];
            $grand_total_due += $item['total_due'];
        }

        return view('backend.user.reports.exports.income_by_customer', [
            'report_data' => $data_array,
            'date1' => $date1,
            'date2' => $date2,
            'grand_total_income' => $grand_total_income,
            'grand_total_paid' => $grand_total_paid,
            'grand_total_due' => $grand_total_due
        ]);
    }
}