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
    protected $fieldMappings;
    protected $duplicateHandling;

    public function __construct($fieldMappings = [], $duplicateHandling = 'skip')
    {
        $this->fieldMappings = $fieldMappings;
        $this->duplicateHandling = $duplicateHandling;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $rows)
    {
        // Ensure required accounts exist (only check once)
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
            // Map fields if mappings are provided
            $data = $this->mapRowData($row);

            // Skip if required field is missing
            if (empty($data['name'])) {
                continue;
            }

            // Handle duplicate checking
            $product = null;
            if ($this->duplicateHandling === 'skip') {
                $product = Product::where('name', $data['name'])->first();
                if ($product) {
                    continue; // Skip duplicates
                }
            } else {
                // Overwrite mode - find existing product by name
                $product = Product::where('name', $data['name'])->first();
            }

            // Parse expiry date
            $expiry_date = null;
            if (!empty($data['expiry_date'])) {
                try {
                    $expiry_date = Date::excelToDateTimeObject($data['expiry_date']);
                } catch (\Exception $e) {
                    $expiry_date = null;
                }
            }

            if ($product == null) {
                // Create new product
                $productData = $this->prepareProductData($data, $expiry_date);
                $product = Product::create($productData);

                // Create transactions for initial stock
                $initialStock = $data['initial_stock'] ?? 0;
                if ($initialStock > 0) {
                    $this->createStockTransactions($product, $initialStock, 'Opening Stock');
                }
            } else {
                // Update existing product
                $previous_initial_stock = $product->initial_stock ?? 0;
                $new_initial_stock = $data['initial_stock'] ?? 0;
                $stock_difference = $new_initial_stock - $previous_initial_stock;

                $productData = $this->prepareProductData($data, $expiry_date);
                $productData['initial_stock'] = $new_initial_stock;
                $product->update($productData);

                // Update current stock
                if ($stock_difference != 0) {
                    $product->stock = ($product->stock ?? 0) + $stock_difference;
                    $product->save();
                }

                // Handle transactions for stock changes
                if ($stock_difference != 0) {
                    // Delete previous stock transactions
                    Transaction::where('ref_id', $product->id)
                        ->where('ref_type', 'product')
                        ->where('description', 'like', '%Opening Stock%')
                        ->delete();

                    if ($stock_difference > 0) {
                        // Increase stock
                        $this->createStockTransactions($product, $stock_difference, 
                            'Opening Stock Adjustment +' . $stock_difference, 'increase');
                    } else {
                        // Decrease stock
                        $this->createStockTransactions($product, abs($stock_difference), 
                            'Opening Stock Adjustment -' . abs($stock_difference), 'decrease');
                    }
                }
            }
        }
    }

    protected function mapRowData($row)
    {
        // If field mappings are provided, map the data
        if (!empty($this->fieldMappings)) {
            $mappedData = [];
            foreach ($this->fieldMappings as $systemField => $fileHeader) {
                if (!empty($fileHeader) && isset($row[$fileHeader])) {
                    $mappedData[$systemField] = $row[$fileHeader];
                }
            }
            return $mappedData;
        }

        // Otherwise, return row as-is (for backward compatibility)
        return $row;
    }

    protected function prepareProductData($data, $expiry_date)
    {
        // Map field names from import format to database fields
        $productData = [
            'name' => $data['name'] ?? '',
            'type' => $data['type'] ?? $data['item_type'] ?? 'product',
            'descriptions' => $data['description'] ?? $data['descriptions'] ?? null,
            'selling_price' => $data['rate'] ?? $data['selling_price'] ?? 0,
            'purchase_cost' => $data['purchase_rate'] ?? $data['purchase_cost'] ?? 0,
            'image' => $data['image'] ?? 'default.png',
            'stock_management' => $data['track_inventory'] ?? $data['stock_management'] ?? 0,
            'initial_stock' => $data['initial_stock'] ?? 0,
            'stock' => $data['initial_stock'] ?? 0,
            'allow_for_selling' => $data['sellable'] ?? $data['allow_for_selling'] ?? 0,
            'allow_for_purchasing' => $data['purchasable'] ?? $data['allow_for_purchasing'] ?? 0,
            'status' => $data['status'] ?? 1,
            'expiry_date' => $expiry_date,
            'code' => $data['code'] ?? null,
            'reorder_point' => $data['reorder_point'] ?? null,
        ];

        // Handle product unit
        if (!empty($data['usage_unit'] ?? $data['unit'])) {
            $unitName = $data['usage_unit'] ?? $data['unit'];
            $unit = ProductUnit::where('unit', 'like', '%' . $unitName . '%')->first();
            if ($unit) {
                $productData['product_unit_id'] = $unit->id;
            }
        }

        // Handle accounts
        if (!empty($data['account'] ?? $data['income_account_name'])) {
            $accountName = $data['account'] ?? $data['income_account_name'];
            $account = get_account($accountName);
            if ($account) {
                $productData['income_account_id'] = $account->id;
            }
        }

        if (!empty($data['expense_account_name'])) {
            $account = get_account($data['expense_account_name']);
            if ($account) {
                $productData['expense_account_id'] = $account->id;
            }
        }

        // Handle brand
        if (!empty($data['brand'])) {
            $brand = Brands::where('name', $data['brand'])->first();
            if ($brand) {
                $productData['brand_id'] = $brand->id;
            }
        }

        // Handle sub category
        if (!empty($data['sub_category'])) {
            $category = SubCategory::where('name', $data['sub_category'])->first();
            if ($category) {
                $productData['sub_category_id'] = $category->id;
            }
        }

        return $productData;
    }

    protected function createStockTransactions($product, $quantity, $descriptionSuffix, $type = 'increase')
    {
        $purchaseCost = $product->purchase_cost ?? 0;
        $amount = $purchaseCost * $quantity;
        $currency = request()->activeBusiness->currency;
        $exchangeRate = Currency::where('name', $currency)->first()->exchange_rate ?? 1;

        if ($type === 'increase') {
            // Increase stock: Debit Inventory, Credit Common Shares
            // Debit Inventory
            $transaction = new Transaction();
            $transaction->trans_date = now()->format('Y-m-d H:i:s');
            $transaction->account_id = get_account('Inventory')->id;
            $transaction->dr_cr = 'dr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' ' . $descriptionSuffix;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product';
            $transaction->save();

            // Credit Common Shares
            $transaction = new Transaction();
            $transaction->trans_date = now()->format('Y-m-d H:i:s');
            $transaction->account_id = get_account('Common Shares')->id;
            $transaction->dr_cr = 'cr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' ' . $descriptionSuffix;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product';
            $transaction->save();
        } else {
            // Decrease stock: Credit Inventory, Debit Common Shares
            // Credit Inventory
            $transaction = new Transaction();
            $transaction->trans_date = now()->format('Y-m-d H:i:s');
            $transaction->account_id = get_account('Inventory')->id;
            $transaction->dr_cr = 'cr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' ' . $descriptionSuffix;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product';
            $transaction->save();

            // Debit Common Shares
            $transaction = new Transaction();
            $transaction->trans_date = now()->format('Y-m-d H:i:s');
            $transaction->account_id = get_account('Common Shares')->id;
            $transaction->dr_cr = 'dr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' ' . $descriptionSuffix;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product';
            $transaction->save();
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
            'income_account_name' => 'required_if:allow_for_selling,1|exists:accounts,account_name',
            'allow_for_purchasing' => 'required|in:1,0',
            'expense_account_name' => 'required_if:allow_for_purchasing,1|exists:accounts,account_name',
            'status' => 'required|in:1,0',
            'expiry_date' => 'nullable',
            'code' => 'nullable',
            'brand' => 'nullable|exists:product_brands,name',
            'sub_category' => 'nullable|exists:sub_categories,name',
        ];
    }
}
