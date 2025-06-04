<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Brands;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SubCategory;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProductImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $rows)
    {

        if (!Account::where('account_name', 'Common Shares')->where('business_id', request()->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '3000';
            $account->account_name    = 'Common Shares';
            $account->account_type    = 'Equity';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = request()->activeBusiness->id;
            $account->user_id         = request()->activeBusiness->user->id;
            $account->dr_cr           = 'cr';
            $account->save();
        }

        if (!Account::where('account_name', 'Inventory')->where('business_id', request()->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '1000';
            $account->account_name    = 'Inventory';
            $account->account_type    = 'Other Current Asset';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = request()->activeBusiness->id;
            $account->user_id         = request()->activeBusiness->user->id;
            $account->dr_cr           = 'dr';
            $account->save();
        }

        foreach ($rows as $row) {
            if ($row['expiry_date'] == null) {
                $expiry_date = null;
            } else {
                $expiry_date = Date::excelToDateTimeObject($row['expiry_date']);
            }

            $product = Product::where('name', $row['name'])->first();

            if ($product == null) {
                $product = Product::create([
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'product_unit_id' => ProductUnit::where('unit', 'like', '%' . $row['unit'] . '%')->first()->id ?? null,
                    'purchase_cost' => $row['purchase_cost'] ?? 0,
                    'selling_price' => $row['selling_price'] ?? 0,
                    'descriptions' => $row['descriptions'],
                    'image' => $row['image'] ?? 'default.png',
                    'stock_management' => $row['stock_management'],
                    'intial_stock' => $row['intial_stock'] ?? 0,
                    'stock' => $row['intial_stock'] ?? 0,
                    'allow_for_selling' => $row['allow_for_selling'],
                    'income_account_id' => get_account($row['income_account_name'])->id,
                    'allow_for_purchasing' => $row['allow_for_purchasing'],
                    'expense_account_id' => get_account($row['expense_account_name'])->id,
                    'status' => $row['status'],
                    'expiry_date' => $expiry_date ?? null,
                    'code' => $row['code'] ?? null,
                    'reorder_point' => $row['reorder_point'] ?? null,
                    'brand_id' => Brands::where('name', $row['brand'])->first()->id ?? null,
                    'sub_category_id' => SubCategory::where('name', $row['sub_category'])->first()->id ?? null,
                ]);

                if ($row['intial_stock'] > 0) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Inventory')->id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = request()->activeBusiness->currency;
                    $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $product->purchase_cost * $row['intial_stock'];
                    $transaction->transaction_amount      = $product->purchase_cost * $row['intial_stock'];
                    $transaction->description = $product->name . ' Opening Stock #' . $row['intial_stock'];
                    $transaction->ref_id      = $product->id;
                    $transaction->ref_type    = 'product';
                    $transaction->save();

                    $transaction              = new Transaction();
                    $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Common Shares')->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = request()->activeBusiness->currency;
                    $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $product->purchase_cost * $row['intial_stock'];
                    $transaction->transaction_amount      = $product->purchase_cost * $row['intial_stock'];
                    $transaction->description = $product->name . ' Opening Stock #' . $row['intial_stock'];
                    $transaction->ref_id      = $product->id;
                    $transaction->ref_type    = 'product';
                    $transaction->save();
                }
            } else {
                $previous_initial_stock = $product->intial_stock ?? 0;
                $new_initial_stock = $row['intial_stock'] ?? 0;

                $stock_difference = $new_initial_stock - $previous_initial_stock;

                $product->update([
                    'type' => $row['type'],
                    'product_unit_id' => ProductUnit::where('unit', 'like', '%' . $row['unit'] . '%')->first()->id ?? null,
                    'selling_price' => $row['selling_price'] ?? 0,
                    'descriptions' => $row['descriptions'],
                    'stock_management' => $row['stock_management'],
                    'intial_stock' => $row['intial_stock'] ?? 0,
                    'stock' => $product->stock + max($stock_difference, 0),
                    'allow_for_selling' => $row['allow_for_selling'],
                    'income_account_id' => get_account($row['income_account_name'])->id,
                    'allow_for_purchasing' => $row['allow_for_purchasing'],
                    'expense_account_id' => get_account($row['expense_account_name'])->id,
                    'status' => $row['status'],
                    'expiry_date' => $expiry_date,
                    'code' => $row['code'] ?? null,
                    'reorder_point' => $row['reorder_point'] ?? null,
                    'brand_id' => Brands::where('name', $row['brand'])->first()->id ?? null,
                    'sub_category_id' => SubCategory::where('name', $row['sub_category'])->first()->id ?? null,
                ]);

                if ($stock_difference > 0) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Inventory')->id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = request()->activeBusiness->currency;
                    $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $product->purchase_cost * $stock_difference;
                    $transaction->transaction_amount      = $product->purchase_cost * $stock_difference;
                    $transaction->description = $product->name . ' Addition Stock #' . $stock_difference;
                    $transaction->ref_id      = $product->id;
                    $transaction->ref_type    = 'product';
                    $transaction->save();

                    $transaction              = new Transaction();
                    $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Common Shares')->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = request()->activeBusiness->currency;
                    $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $product->purchase_cost * $stock_difference;
                    $transaction->transaction_amount      = $product->purchase_cost * $stock_difference;
                    $transaction->description = $product->name . ' Addition Stock #' . $stock_difference;
                    $transaction->ref_id      = $product->id;
                    $transaction->ref_type    = 'product';
                    $transaction->save();
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'type' => 'required',
            'unit' => 'nullable',
            'purchase_cost' => 'nullable',
            'selling_price' => 'nullable',
            'descriptions' => 'nullable',
            'stock_management' => 'required|in:1,0',
            'allow_for_selling' => 'required|in:1,0',
            'income_account_name' => 'required|exists:accounts,account_name',
            'allow_for_purchasing' => 'required|in:1,0',
            'expense_account_name' => 'required|exists:accounts,account_name',
            'status' => 'required|in:1,0',
            'expiry_date' => 'nullable',
            'code' => 'nullable',
            'brand' => 'nullable|exists:brands,name',
            'sub_category' => 'nullable|exists:sub_categories,name',
            'image' => 'nullable',
        ];
    }
}
