<?php

namespace App\Imports;

use App\Http\Middleware\Business;
use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CreditInvoiceImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation, SkipsEmptyRows
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $rowNumber = 1;

        $default_accounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', request()->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_code = '1100';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_code = '2200';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_code = '4009';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_type = 'Other Current Asset';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_type = 'Other Income';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Receivable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = request()->activeBusiness->id;
                $account_obj->user_id     = request()->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        foreach ($rows as $row) {
            $summary = $this->calculateTotal($row);

            $customer = Customer::where('name', 'like', '%' . $row['customer_name'] . '%')->first();

            $invoice_date = Date::excelToDateTimeObject($row['invoice_date'])->format('Y-m-d');
            $due_date = Date::excelToDateTimeObject($row['due_date'])->format('Y-m-d');

            $month = Carbon::parse($invoice_date)->format('F');
            $year = Carbon::parse($invoice_date)->format('Y');
            $today = now()->format('d');

            // financial year
            $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
            $end_month = explode(',', $financial_year)[1];
            $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
            $end_day = $start_day + 5;

            // if login as this user dont check the financial year
            if (false) {
                if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                    return redirect()->back()->withInput()->with('error', _lang('The date on row ' . $rowNumber . ' . Period Closed'));
                }
            }

            $invoice = new Invoice();
            $invoice->customer_id     = $customer->id;
            $invoice->title           = $row['title'];
            $invoice->invoice_number  = BusinessSetting::where('name', 'invoice_number')->first()->value;
            $invoice->order_number    = $row['order_number'];
            $invoice->invoice_date    = $invoice_date;
            $invoice->due_date        = $due_date;
            $invoice->sub_total       = $summary['subTotal'];
            $invoice->grand_total     = $summary['grandTotal'];
            $invoice->currency        = $row['transaction_currency'];
            $invoice->converted_total = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['grandTotal']);
            $invoice->exchange_rate   = Currency::where('name', 'like', '%' . $row['transaction_currency'] . '%')->first()->exchange_rate;
            $invoice->paid            = 0;
            $invoice->discount        = $summary['discountAmount'];
            $invoice->discount_type   = $row['discount_type'] ?? 0;
            $invoice->discount_value  = $row['discount_value'];
            $invoice->template_type   = 0;
            $invoice->template        = 'default';
            $invoice->note            = $row['note'];
            $invoice->footer          = get_business_option('invoice_footer');
            $invoice->short_code      = rand(100000, 9999999) . uniqid();
            $invoice->save();

            BusinessSetting::where('name', 'invoice_number')->increment('value');

            $product = Product::where('name', 'like', '%' . $row['product_name'] . '%')->first();

            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'description'  => $product->descriptions,
                'quantity'     => $row['quantity'],
                'unit_cost'    => $row['unit_cost'],
                'sub_total'    => ($row['unit_cost'] * $row['quantity']),
            ]));

            $currentTime = Carbon::now();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $invoiceItem->sub_total));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $invoiceItem->sub_total);
                $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';

                $transaction->save();
            }

            if ($product->stock_management == 1 && $product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoiceItem->quantity;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->save();

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type    = 'invoice';
                $transaction->ref_id      = $invoice->id;
                $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
                $transaction->save();
            }

            if (isset($row['tax'])) {
                $tax         = Tax::where('name', 'like', '%' . $row['tax'] . '%')->first();
                $invoiceItem->taxes()->save(new InvoiceItemTax([
                    'invoice_id' => $invoice->id,
                    'tax_id'     => $tax->id,
                    'name'       => $tax->name . ' ' . $tax->rate . ' %',
                    'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                ]));

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $tax->account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], ($invoiceItem->sub_total / 100) * $tax->rate));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], ($invoiceItem->sub_total / 100) * $tax->rate);
                $transaction->description = _lang('Credit Invoice Tax') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->save();
            }

            //Update Stock
            if ($product->type == 'product' && $product->stock_management == 1) {
                //Check Available Stock Quantity
                if ($product->stock < $row['quantity']) {
                    DB::rollBack();
                    return back()->with('error', $product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $row['quantity'];
                $product->save();
            }

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Accounts Receivable')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['grandTotal']);
            $transaction->transaction_currency    = $row['transaction_currency'];
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['grandTotal']));
            $transaction->ref_id      = $invoice->id;
            $transaction->ref_type    = 'invoice';
            $transaction->description = 'Credit Invoice #' . $invoice->invoice_number;
            $transaction->save();

            if ($row['discount_value'] > 0) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Sales Discount Allowed')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['discountAmount']);
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['discountAmount']));
                $transaction->description = _lang('Credit Invoice Discount') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'invoice';
                $transaction->save();
            }
        }

        $rowNumber++;
    }

    private function calculateTotal($row)
    {
        $subTotal       = 0;
        $taxAmount      = 0;
        $discountAmount = 0;
        $grandTotal     = 0;

        //Calculate Sub Total
        $line_qnt       = $row['quantity'];
        $line_unit_cost = $row['unit_cost'];
        $line_total     = ($line_qnt * $line_unit_cost);

        //Show Sub Total
        $subTotal = ($subTotal + $line_total);

        //Calculate Taxes
        if (isset($row['tax'])) {
            $tax         = Tax::where('name', 'like', '%' . $row['tax'] . '%')->first();
            $taxAmount   = ($line_total / 100) * $tax->rate;
        }

        //Calculate Discount
        if ($row['discount_type'] == '0') {
            $discountAmount = ($subTotal / 100) * $row['discount_value'];
        } else if ($row['discount_type'] == '1') {
            $discountAmount = $row['discount_value'];
        }

        //Calculate Grand Total
        $grandTotal = ($subTotal + $taxAmount) - $discountAmount;

        return array(
            'subTotal'       => $subTotal,
            'taxAmount'      => $taxAmount,
            'discountAmount' => $discountAmount,
            'grandTotal'     => $grandTotal,
        );
    }

    public function chunkSize(): int
    {
        return 20;
    }

    public function rules(): array
    {
        return [
            '*.customer_name' => 'required|exists:customers,name',
            '*.invoice_date' => 'required',
            '*.due_date' => 'required',
            '*.title' => 'nullable',
            '*.order_number' => 'nullable',
            '*.product_name' => 'required|exists:products,name',
            '*.quantity' => 'required|numeric',
            '*.unit_cost' => 'required|numeric',
            '*.transaction_currency' => 'required',
            '*.tax' => 'nullable|exists:taxes,name',
            '*.discount_type' => 'nullable|in:0,1',
            '*.discount_value' => 'nullable|numeric',
            '*.note' => 'nullable',
        ];
    }
}
