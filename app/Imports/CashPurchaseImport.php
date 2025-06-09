<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProjectBudget;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemTax;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\ReceiptItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CashPurchaseImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $rowNumber = 1;

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
                    $account_obj->dr_cr   = 'dr';
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

        foreach ($rows as $row) {
            $summary = $this->calculateTotal($row);

            $vendor = Vendor::where('name', $row['vendor_name'])->first();

            $purchase_date = Date::excelToDateTimeObject($row['purchase_date'])->format('Y-m-d');

            $month = Carbon::parse($purchase_date)->format('F');
            $year = Carbon::parse($purchase_date)->format('Y');
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

            $purchase = new Purchase();
            $purchase->vendor_id = $vendor->id ?? null;
            $purchase->title = $row['title'];
            $purchase->bill_no = get_business_option('purchase_number');
            $purchase->po_so_number = $row['po_so_number'];
            $purchase->purchase_date = Carbon::parse($purchase_date)->format('Y-m-d');
            $purchase->due_date = Carbon::parse($purchase_date)->format('Y-m-d');
            $purchase->sub_total = $summary['subTotal'];
            $purchase->grand_total = $summary['grandTotal'];
            $purchase->converted_total = $row['converted_total'];
            $purchase->exchange_rate   = $row['exchange_rate'];
            $purchase->currency   = $row['transaction_currency'];
            $purchase->paid = $summary['grandTotal'];
            $purchase->discount = $summary['discountAmount'];
            $purchase->cash = 1;
            $purchase->discount_type = $row['discount_type'];
            $purchase->discount_value = $row['discount_value'] ?? 0;
            $purchase->template_type = 0;
            $purchase->template = 'default';
            $purchase->note = $row['note'];
            $purchase->withholding_tax = $row['withholding_tax'] ?? 0;
            $purchase->footer = get_business_option('purchase_footer');
            if (has_permission('cash_purchases.approve') || request()->isOwner) {
                $purchase->approval_status = 1;
            } else {
                $purchase->approval_status = 0;
            }
            $purchase->created_by = auth()->user()->id;
            if (has_permission('cash_purchases.approve') || request()->isOwner) {
                $purchase->approved_by = auth()->user()->id;
            } else {
                $purchase->approved_by = null;
            }
            $purchase->benificiary = $row['benificiary'];
            $purchase->short_code = rand(100000, 9999999) . uniqid();
            $purchase->paid = $summary['grandTotal'];
            $purchase->status = 2;

            $purchase->save();

            BusinessSetting::where('name', 'receipt_number')->increment('value');


            $product = Product::where('name', 'like', '%' . $row['product_name'] . '%')->first();

            $purchaseItem = $purchase->items()->save(new PurchaseItem([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'description' => $product->descriptions,
                'quantity' => $row['quantity'],
                'unit_cost' => $row['unit_cost'],
                'sub_total' => ($row['unit_cost'] * $row['quantity']),
                'account_id' => $row['account_id'],
                'project_id' => isset($row['project_id']) ? $row['project_id'] : null,
                'project_task_id' => isset($row['project_task_id']) ? $row['project_task_id'] : null,
                'cost_code_id' => isset($row['cost_code_id']) ? $row['cost_code_id'] : null,
            ]));

            if ($purchaseItem->project_id && $purchaseItem->project_task_id && $purchaseItem->cost_code_id) {
                $projectBudget = ProjectBudget::where('project_id', $purchaseItem->project_id)->where('project_task_id', $purchaseItem->project_task_id)->where('cost_code_id', $purchaseItem->cost_code_id)->first();
                if ($projectBudget) {
                    $projectBudget->actual_budget_quantity += $purchaseItem->quantity;
                    $projectBudget->actual_budget_amount += $purchaseItem->sub_total;
                    $projectBudget->save();
                }
            }

            $currentTime = Carbon::now();

            if (isset($row['taxes'])) {
                foreach ($row['taxes'] as $taxId) {
                    $tax = Tax::find($taxId);

                    $purchaseItem->taxes()->save(new PurchaseItemTax([
                        'purchase_id' => $purchase->id,
                        'tax_id' => $taxId,
                        'name' => $tax->name . ' ' . $tax->rate . ' %',
                        'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
                    ]));

                    if (has_permission('cash_purchases.approve') || request()->isOwner) {
                        if (isset($row['withholding_tax']) && $row['withholding_tax'] == 1) {
                            $transaction              = new Transaction();
                            $transaction->trans_date  = Carbon::parse($purchase_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                            $transaction->account_id  = $tax->account_id;
                            $transaction->dr_cr       = 'cr';
                            $transaction->transaction_currency    = $row['transaction_currency'];
                            $transaction->currency_rate = $purchase->exchange_rate;
                            $transaction->base_currency_amount = convert_currency($row['transaction_currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
                            $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['transaction_currency'], (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
                            $transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
                            $transaction->ref_id      = $purchase->id;
                            $transaction->ref_type    = 'cash purchase tax';
                            $transaction->tax_id      = $tax->id;
                            $transaction->project_id = $purchaseItem->project_id;
                            $transaction->project_task_id = $purchaseItem->project_task_id;
                            $transaction->cost_code_id = $purchaseItem->cost_code_id;
                            $transaction->save();
                        } else {
                            $transaction              = new Transaction();
                            $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                            $transaction->account_id  = $tax->account_id;
                            $transaction->dr_cr       = 'dr';
                            $transaction->transaction_currency    = $request->currency;
                            $transaction->currency_rate = $purchase->exchange_rate;
                            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
                            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
                            $transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
                            $transaction->ref_id      = $purchase->id;
                            $transaction->ref_type    = 'cash purchase tax';
                            $transaction->tax_id      = $tax->id;
                            $transaction->project_id = $purchaseItem->project_id;
                            $transaction->project_task_id = $purchaseItem->project_task_id;
                            $transaction->cost_code_id = $purchaseItem->cost_code_id;
                            $transaction->save();
                        }
                    } else {
                        if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                            $transaction              = new PendingTransaction();
                            $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                            $transaction->account_id  = $tax->account_id;
                            $transaction->dr_cr       = 'cr';
                            $transaction->transaction_currency    = $request->currency;
                            $transaction->currency_rate = $purchase->exchange_rate;
                            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
                            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
                            $transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
                            $transaction->ref_id      = $purchase->id;
                            $transaction->ref_type    = 'cash purchase tax';
                            $transaction->tax_id      = $tax->id;
                            $transaction->project_id = $purchaseItem->project_id;
                            $transaction->project_task_id = $purchaseItem->project_task_id;
                            $transaction->cost_code_id = $purchaseItem->cost_code_id;
                            $transaction->save();
                        } else {
                            $transaction              = new PendingTransaction();
                            $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                            $transaction->account_id  = $tax->account_id;
                            $transaction->dr_cr       = 'dr';
                            $transaction->transaction_currency    = $request->currency;
                            $transaction->currency_rate = $purchase->exchange_rate;
                            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
                            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
                            $transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
                            $transaction->ref_id      = $purchase->id;
                            $transaction->ref_type    = 'cash purchase tax';
                            $transaction->tax_id      = $tax->id;
                            $transaction->project_id = $purchaseItem->project_id;
                            $transaction->project_task_id = $purchaseItem->project_task_id;
                            $transaction->cost_code_id = $purchaseItem->cost_code_id;
                            $transaction->save();
                        }
                    }
                }
            }

            if (has_permission('cash_purchases.approve') || request()->isOwner) {

                if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('account_id')[$i];
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
                    $transaction->ref_type    = 'cash purchase';
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->project_id = $purchaseItem->project_id;
                    $transaction->project_task_id = $purchaseItem->project_task_id;
                    $transaction->cost_code_id = $purchaseItem->cost_code_id;
                    $transaction->save();
                } else {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('account_id')[$i];
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate));
                    $transaction->ref_type    = 'cash purchase';
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->project_id = $purchaseItem->project_id;
                    $transaction->project_task_id = $purchaseItem->project_task_id;
                    $transaction->cost_code_id = $purchaseItem->cost_code_id;
                    $transaction->save();
                }
            } else {
                if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('account_id')[$i];
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
                    $transaction->ref_type    = 'cash purchase';
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->project_id = $purchaseItem->project_id;
                    $transaction->project_task_id = $purchaseItem->project_task_id;
                    $transaction->cost_code_id = $purchaseItem->cost_code_id;
                    $transaction->save();
                } else {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('account_id')[$i];
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate));
                    $transaction->ref_type    = 'cash purchase';
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->project_id = $purchaseItem->project_id;
                    $transaction->project_task_id = $purchaseItem->project_task_id;
                    $transaction->cost_code_id = $purchaseItem->cost_code_id;
                    $transaction->save();
                }
            }

            // update stock
            if ($purchaseItem->product->type == 'product' && $purchaseItem->product->stock_management == 1) {
                $purchaseItem->product->stock = $purchaseItem->product->stock + $request->quantity[$i];
                $purchaseItem->product->save();
            }

            //Increment Bill Number
            BusinessSetting::where('name', 'purchase_number')->increment('value');

            DB::commit();

            if (has_permission('cash_purchases.approve') || request()->isOwner) {
                if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('credit_account_id');
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
                    $transaction->ref_type    = 'cash purchase payment';
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->project_id = $purchase->items->first()->project_id;
                    $transaction->save();
                } else {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('credit_account_id');
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                    $transaction->ref_type    = 'cash purchase payment';
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->project_id = $purchase->items->first()->project_id;
                    $transaction->save();
                }

                if ($request->input('discount_value') > 0) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate           = $purchase->exchange_rate;
                    $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                    $transaction->description = _lang('Bill Invoice Discount') . ' #' . $purchase->bill_no;
                    $transaction->ref_id      = $purchase->id;
                    $transaction->ref_type    = 'cash purchase';
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->project_id = $purchase->items->first()->project_id;
                    $transaction->save();
                }
            } else {
                if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('credit_account_id');
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
                    $transaction->ref_type    = 'cash purchase payment';
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->project_id = $purchase->items->first()->project_id;
                    $transaction->save();
                } else {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id  = $request->input('credit_account_id');
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchase->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                    $transaction->ref_type    = 'cash purchase payment';
                    $transaction->ref_id      = $purchase->id;
                    $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->project_id = $purchase->items->first()->project_id;
                    $transaction->save();
                }

                if ($request->input('discount_value') > 0) {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate           = $purchase->exchange_rate;
                    $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                    $transaction->description = _lang('Bill Invoice Discount') . ' #' . $purchase->bill_no;
                    $transaction->ref_id      = $purchase->id;
                    $transaction->ref_type    = 'cash purchase';
                    $transaction->vendor_id   = $purchase->vendor_id;
                    $transaction->project_id = $purchase->items->first()->project_id;
                    $transaction->save();
                }
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
