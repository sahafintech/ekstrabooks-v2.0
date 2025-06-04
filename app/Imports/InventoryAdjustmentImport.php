<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Currency;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InventoryAdjustmentImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{

    public function collection(Collection $rows)
    {

        foreach ($rows as $row) {

            $product = Product::where('name', $row['product_name'])->firstOrFail();
            $account = Account::where('account_name', $row['account_name'])->firstOrFail();

            $adjustment = new InventoryAdjustment();
            $adjustment->adjustment_date = Date::excelToDateTimeObject($row['adjustment_date'])->format('Y-m-d');
            $adjustment->account_id = $account->id;
            $adjustment->product_id = $product->id;
            $adjustment->quantity_on_hand = $row['quantity_on_hand'];
            $adjustment->adjusted_quantity = $row['adjusted_quantity'];
            $adjustment->new_quantity_on_hand = floatval($row['quantity_on_hand']) + floatval($row['adjusted_quantity']);
            $adjustment->description = $row['description'] ?? null;
            $adjustment->adjustment_type = $row['adjusted_quantity'] > 0 ? 'adds' : 'deducts';
            $adjustment->save();

            // Update product stock
            $product->stock = floatval($row['quantity_on_hand']) + floatval($row['adjusted_quantity']);
            $product->save();

            $currentTime = Carbon::now();

            if ($adjustment->adjustment_type == 'adds') {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse(Date::excelToDateTimeObject($row['adjustment_date'])->format('Y-m-d'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $account->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = request()->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->transaction_amount      = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->description = Product::find($product->id)->name . ' Inventory Adjustment #' . $row['adjusted_quantity'];
                $transaction->ref_id      =  $product->id;
                $transaction->ref_type    = 'product adjustment';
                $transaction->save();

                // invetory account transaction
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse(Date::excelToDateTimeObject($row['adjustment_date'])->format('Y-m-d'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = request()->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->transaction_amount      = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->description = Product::find($product->id)->name . ' Inventory Adjustment #' . $row['adjusted_quantity'];
                $transaction->ref_id      =  $product->id;
                $transaction->ref_type    = 'product adjustment';
                $transaction->save();
            } else {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse(Date::excelToDateTimeObject($row['adjustment_date'])->format('Y-m-d'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = $account->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = request()->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->transaction_amount      = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->description = Product::find($product->id)->name . ' Inventory Adjustment #' . $row['adjusted_quantity'];
                $transaction->ref_id      =  $product->id;
                $transaction->ref_type    = 'product adjustment';
                $transaction->save();

                // invetory account transaction
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse(Date::excelToDateTimeObject($row['adjustment_date'])->format('Y-m-d'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = request()->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->transaction_amount      = abs($row['adjusted_quantity']) * Product::find($product->id)->purchase_cost;
                $transaction->description = Product::find($product->id)->name . ' Inventory Adjustment #' . $row['adjusted_quantity'];
                $transaction->ref_id      =  $product->id;
                $transaction->ref_type    = 'product adjustment';
                $transaction->save();
            }
        }

        DB::commit();
    }

    public function rules(): array
    {
        return [
            'adjustment_date' => 'required',
            'product_name' => 'required|exists:products,name',
            'account_name' => 'required|exists:accounts,account_name',
            'quantity_on_hand' => 'required',
            'adjusted_quantity' => 'required',
            'description' => 'nullable',
        ];
    }
}
