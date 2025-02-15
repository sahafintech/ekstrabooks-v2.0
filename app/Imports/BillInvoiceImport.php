<?php

namespace App\Imports;

use App\Http\Middleware\Business;
use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BillInvoiceImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $default_accounts = ['Accounts Payable', 'Purchase Tax Payable', 'Purchase Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', request()->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Payable') {
                    $account_obj->account_code = '2100';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->account_code = '2201';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->account_code = '6003';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->account_type = 'Cost Of Sale';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = request()->activeBusiness->id;
                $account_obj->user_id     = request()->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $rowNumber = 1;

        foreach ($rows as $row) {

            $summary = $this->calculateTotal($row);

            $vendor = Vendor::where('name', 'like', '%' . $row['supplier_name'] . '%')->first();

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
            $product = Product::where('name', 'like', '%' . $row['product_name'] . '%')->first();

            if ($product->stock_management != 1) {
                continue;
            }

            $purchase = new Purchase();
            $purchase->vendor_id     = $vendor->id;
            $purchase->title           = $row['title'];
            $purchase->bill_no  =     BusinessSetting::where('name', 'purchase_number')->first()->value;
            $purchase->po_so_number    = $row['order_number'];
            $purchase->purchase_date    = $invoice_date;
            $purchase->due_date        = $due_date;
            $purchase->sub_total       = $summary['subTotal'];
            $purchase->grand_total     = $summary['grandTotal'];
            $purchase->currency        = $row['transaction_currency'];
            $purchase->converted_total = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['grandTotal']);
            $purchase->exchange_rate   = Currency::where('name', 'like', '%' . $row['transaction_currency'] . '%')->first()->exchange_rate;
            $purchase->paid            = 0;
            $purchase->discount        = $summary['discountAmount'];
            $purchase->discount_type   = $row['discount_type'] ?? 0;
            $purchase->discount_value  = $row['discount_value'];
            $purchase->template_type   = 0;
            $purchase->template        = 'default';
            $purchase->note            = $row['note'];
            $purchase->footer          = get_business_option('purchase_footer');
            $purchase->short_code      = rand(100000, 9999999) . uniqid();
            $purchase->save();

            BusinessSetting::where('name', 'purchase_number')->increment('value');


            $purchaseItem = $purchase->items()->save(new PurchaseItem([
                'purchase_id'   => $purchase->id,
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'description'  => $product->descriptions,
                'quantity'     => $row['quantity'],
                'unit_cost'    => $row['unit_cost'],
                'sub_total'    => ($row['unit_cost'] * $row['quantity']),
            ]));

            $currentTime = Carbon::now();

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $purchaseItem->sub_total);
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $purchase->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $purchaseItem->sub_total));
                $transaction->ref_type    = 'bill';
                $transaction->ref_id      = $purchase->id;
                $transaction->description = 'Bill Invoice #' . $purchase->bill_no;
                $transaction->save();
            }

            if (isset($row['tax'])) {
                $tax         = Tax::where('name', 'like', '%' . $row['tax'] . '%')->first();
                $purchaseItem->taxes()->save(new PurchaseItemTax([
                    'purchase_id' => $purchase->id,
                    'tax_id'     => $tax->id,
                    'name'       => $tax->name . ' ' . $tax->rate . ' %',
                    'amount'     => ($purchaseItem->sub_total / 100) * $tax->rate,
                ]));

                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $tax->account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate = $purchase->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], ($purchaseItem->sub_total / 100) * $tax->rate));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], ($purchaseItem->sub_total / 100) * $tax->rate);
                $transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
                $transaction->ref_id      = $purchase->id;
                $transaction->ref_type    = 'bill';
                $transaction->save();
            }

            //Update Stock
            $product = $purchaseItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $row['quantity'];
                $product->save();
            }

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Payable')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['grandTotal']);
            $transaction->transaction_currency    = $row['transaction_currency'];
            $transaction->currency_rate = $purchase->exchange_rate;
            $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['grandTotal']));
            $transaction->ref_type    = 'bill';
            $transaction->ref_id      = $purchase->id;
            $transaction->description = 'Bill Invoice #' . $purchase->bill_no;
            $transaction->save();

            if ($row['discount_value'] > 0) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['discountAmount']);
                $transaction->transaction_currency    = $row['transaction_currency'];
                $transaction->currency_rate           = $purchase->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], $summary['discountAmount']));
                $transaction->description = _lang('Bill Invoice Discount') . ' #' . $purchase->bill_no;
                $transaction->ref_id      = $purchase->id;
                $transaction->ref_type    = 'bill';
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

    public function rules(): array
    {
        return [
            'invoice_date' => 'required',
            'due_date'     => 'required',
            'supplier_name' => 'required|exists:vendors,name',
            'product_name' => 'required|exists:products,name',
            'quantity'     => 'required|numeric',
            'unit_cost'    => 'required|numeric',
            'transaction_currency' => 'required|exists:currency,name',
            'discount_type' => 'nullable|in:0,1',
            'discount_value' => 'nullable|numeric',
            'tax' => 'nullable|exists:taxes,name',
            'order_number' => 'nullable',
            'note' => 'nullable',
            'title' => 'nullable',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'invoice_date.required' => _lang('Invoice Date is required'),
            'due_date.required'     => _lang('Due Date is required'),
            'supplier_name.required' => _lang('Supplier Name is required'),
            'product_name.required' => _lang('Product Name is required'),
            'quantity.required'     => _lang('Quantity is required'),
            'quantity.numeric'      => _lang('Quantity must be a number'),
            'unit_cost.required'    => _lang('Unit Cost is required'),
            'unit_cost.numeric'     => _lang('Unit Cost must be a number'),
            'transaction_currency.required' => _lang('Transaction Currency is required'),
        ];
    }
}
