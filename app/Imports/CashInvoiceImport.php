<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\ReceiptItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CashInvoiceImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
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

            $customer = Customer::where('name', $row['customer_name'])->first();

            $receipt_date = Date::excelToDateTimeObject($row['invoice_date'])->format('Y-m-d');

            $month = Carbon::parse($receipt_date)->format('F');
            $year = Carbon::parse($receipt_date)->format('Y');
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

            $receipt = new Receipt();
            $receipt->customer_id     = $customer->id ?? null;
            $receipt->title           = $row['title'];
            $receipt->receipt_number  = BusinessSetting::where('name', 'receipt_number')->first()->value;
            $receipt->order_number    = $row['order_number'];
            $receipt->receipt_date    = $receipt_date;
            $receipt->sub_total       = $summary['subTotal'];
            $receipt->grand_total     = $summary['grandTotal'];
            $receipt->currency        = $row['transaction_currency'];
            $receipt->converted_total = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['grandTotal']);
            $receipt->exchange_rate   = $row['exchange_rate'];
            $receipt->discount        = $summary['discountAmount'];
            $receipt->discount_type   = $row['discount_type'] ?? 0;
            $receipt->discount_value  = $row['discount_value'];
            $receipt->template_type   = 0;
            $receipt->template        = 'default';
            $receipt->note            = $row['note'];
            $receipt->footer          = get_business_option('receipt_footer');
            $receipt->short_code      = rand(100000, 9999999) . uniqid();
            $receipt->user_id = auth()->user()->id;
            $receipt->business_id = request()->activeBusiness->id;
            $receipt->save();

            BusinessSetting::where('name', 'receipt_number')->increment('value');


            $product = Product::where('name', 'like', '%' . $row['product_name'] . '%')->first();

            $receiptItem = $receipt->items()->save(new ReceiptItem([
                'receipt_id'   => $receipt->id,
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'description'  => $product->descriptions,
                'quantity'     => $row['quantity'],
                'unit_cost'    => $row['unit_cost'],
                'sub_total'    => ($row['unit_cost'] * $row['quantity']),
                'user_id' => auth()->user()->id,
                'business_id' => request()->activeBusiness->id,
            ]));

            $currentTime = Carbon::now();

            if ($product->allow_for_selling == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($receipt_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->income_account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $row['exchange_rate'];
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $receiptItem->sub_total / $row['exchange_rate']));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $receiptItem->sub_total / $row['exchange_rate']);
                $transaction->description = _lang('Cash Invoice Income') . ' #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->save();

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($receipt_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account($row['debit_account'])->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $row['exchange_rate'];
                $transaction->transaction_method = $row['payment_method'];
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $receiptItem->sub_total / $row['exchange_rate']));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $receiptItem->sub_total / $row['exchange_rate']);
                $transaction->description = _lang('Cash Invoice') . ' #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->save();
            }

            if ($product->stock_management == 1 && $product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($receipt_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate           = $row['exchange_rate'];
                $transaction->base_currency_amount    = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $receiptItem->quantity));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $receiptItem->quantity);
                $transaction->description = $receiptItem->product_name . ' Sales #' . $receiptItem->quantity;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->save();

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($receipt_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $receiptItem->quantity);
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $row['exchange_rate'];
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $product->purchase_cost * $receiptItem->quantity));
                $transaction->ref_type    = 'receipt';
                $transaction->ref_id      = $receipt->id;
                $transaction->description = 'Cash Invoice #' . $receipt->receipt_number;
                $transaction->save();
            }

            if (isset($row['tax'])) {
                $tax         = Tax::where('name', 'like', '%' . $row['tax'] . '%')->first();
                $receiptItem->taxes()->save(new ReceiptItemTax([
                    'receipt_id' => $receipt->id,
                    'tax_id'     => $tax->id,
                    'name'       => $tax->name . ' ' . $tax->rate . ' %',
                    'amount'     => ($receiptItem->sub_total / 100) * $tax->rate,
                    'user_id' => auth()->user()->id,
                    'business_id' => request()->activeBusiness->id,
                ]));

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($receipt_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $tax->account_id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $row['exchange_rate'];
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], (($receiptItem->sub_total / $row['exchange_rate']) / 100) * $tax->rate));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], (($receiptItem->sub_total / $row['exchange_rate']) / 100) * $tax->rate);
                $transaction->description = _lang('Cash Invoice Tax') . ' #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
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

            if ($row['discount_value'] > 0) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($receipt_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Sales Discount Allowed')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['discountAmount']);
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate           = $row['exchange_rate'];
                $transaction->base_currency_amount    = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['discountAmount']));
                $transaction->description = _lang('Cash Invoice Discount') . ' #' . $receipt->receipt_number;
                $transaction->ref_id      = $receipt->id;
                $transaction->ref_type    = 'receipt';
                $transaction->save();
            }

            $rowNumber++;
        }
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
            'subTotal'       => $subTotal / $row['exchange_rate'],
            'taxAmount'      => $taxAmount / $row['exchange_rate'],
            'discountAmount' => $discountAmount / $row['exchange_rate'],
            'grandTotal'     => $grandTotal / $row['exchange_rate'],
        );
    }

    public function rules(): array
    {
        return [
            '*.customer_name' => 'nullable|exists:customers,name',
            '*.product_name' => 'required|exists:products,name',
            '*.quantity' => 'required|numeric',
            '*.unit_cost' => 'required|numeric',
            '*.transaction_currency' => 'required|exists:currency,name',
            '*.invoice_date' => 'required',
            '*.discount_type' => 'nullable|in:0,1',
            '*.discount_value' => 'nullable|numeric',
            '*.payment_method' => 'nullable|exists:transaction_methods,name',
            '*.debit_account' => 'required|exists:accounts,account_name',
            '*.tax' => 'nullable|exists:taxes,name',
            '*.title' => 'nullable',
            '*.order_number' => 'nullable',
            '*.note' => 'nullable',
        ];
    }
}
