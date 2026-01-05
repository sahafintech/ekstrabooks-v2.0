<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\Brands;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SubCategory;
use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

final class ProductImport implements SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    use SkipsFailures;

    /**
     * Field mappings from Excel header to system field
     * Format: ['Excel Header' => 'system_field']
     */
    private array $mappings;

    public function __construct(array $mappings = [])
    {
        $this->mappings = $mappings;
    }

    /**
     * Transform row data using field mappings
     */
    private function mapRowData(array $row): array
    {
        $mappedData = [];
        
        // Invert mappings: we have header => system_field, we need to find system_field values
        $invertedMappings = array_flip($this->mappings);
        
        foreach ($row as $header => $value) {
            // Normalize header (Excel/PhpSpreadsheet converts to lowercase with underscores)
            $normalizedHeader = $this->normalizeHeader($header);
            
            // Check if this header is mapped to a system field
            foreach ($this->mappings as $excelHeader => $systemField) {
                $normalizedExcelHeader = $this->normalizeHeader($excelHeader);
                if ($normalizedHeader === $normalizedExcelHeader && $systemField !== 'skip') {
                    $mappedData[$systemField] = $value;
                    break;
                }
            }
        }
        
        return $mappedData;
    }

    /**
     * Normalize header for comparison
     */
    private function normalizeHeader(string $header): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', trim($header)));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): ?Product
    {
        // Apply field mappings to transform row data
        $data = $this->mapRowData($row);
        
        // Skip empty rows
        if (empty($data['name']) && empty($data['code'])) {
            return null;
        }

        // Ensure required accounts exist
        $this->ensureAccountsExist();

        $status = 1; // Default to active
        if (isset($data['status'])) {
            $statusValue = is_string($data['status']) ? strtolower(trim($data['status'])) : $data['status'];
            if ($statusValue === 'inactive' || $statusValue === '0' || $statusValue === 0) {
                $status = 0;
            }
        }

        // Parse stock management
        $stockManagement = 1; // Default to enabled
        if (isset($data['stock_management'])) {
            $smValue = is_string($data['stock_management']) ? strtolower(trim($data['stock_management'])) : $data['stock_management'];
            if ($smValue === 'no' || $smValue === '0' || $smValue === 0 || $smValue === 'false') {
                $stockManagement = 0;
            }
        }

        // Parse allow_for_selling
        $allowForSelling = 1; // Default to enabled
        if (isset($data['allow_for_selling'])) {
            $afsValue = is_string($data['allow_for_selling']) ? strtolower(trim($data['allow_for_selling'])) : $data['allow_for_selling'];
            if ($afsValue === 'no' || $afsValue === '0' || $afsValue === 0 || $afsValue === 'false') {
                $allowForSelling = 0;
            }
        }

        // Parse allow_for_purchasing
        $allowForPurchasing = 1; // Default to enabled
        if (isset($data['allow_for_purchasing'])) {
            $afpValue = is_string($data['allow_for_purchasing']) ? strtolower(trim($data['allow_for_purchasing'])) : $data['allow_for_purchasing'];
            if ($afpValue === 'no' || $afpValue === '0' || $afpValue === 0 || $afpValue === 'false') {
                $allowForPurchasing = 0;
            }
        }

        // Handle product unit
        $productUnitId = null;
        if (!empty($data['unit'])) {
            $unit = ProductUnit::where('unit', 'like', '%' . $data['unit'] . '%')->first();
            if ($unit) {
                $productUnitId = $unit->id;
            }
        }

        // Handle accounts
        $incomeAccountId = null;
        if (!empty($data['income_account_name'])) {
            $account = Account::where('account_name', $data['income_account_name'])->first();
            if ($account) {
                $incomeAccountId = $account->id;
            }
        }

        $expenseAccountId = null;
        if (!empty($data['expense_account_name'])) {
            $account = Account::where('account_name', $data['expense_account_name'])->first();
            if ($account) {
                $expenseAccountId = $account->id;
            }
        }

        // Handle brand
        $brandId = null;
        if (!empty($data['brand'])) {
            $brand = Brands::where('name', $data['brand'])->first();
            if ($brand) {
                $brandId = $brand->id;
            }
        }

        // Handle sub category
        $subCategoryId = null;
        if (!empty($data['sub_category'])) {
            $category = SubCategory::where('name', $data['sub_category'])->first();
            if ($category) {
                $subCategoryId = $category->id;
            }
        }

        // Parse expiry date
        $expiryDate = null;
        if (!empty($data['expiry_date'])) {
            try {
                if (is_numeric($data['expiry_date'])) {
                    $expiryDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['expiry_date']);
                } else {
                    $expiryDate = Carbon::parse($data['expiry_date']);
                }
            } catch (\Exception $e) {
                $expiryDate = null;
            }
        }

        $initialStock = !empty($data['initial_stock']) ? (float) $data['initial_stock'] : 0;

        $productData = [
            'code' => !empty($data['code']) ? strtoupper((string) $data['code']) : null,
            'name' => !empty($data['name']) ? ucwords($data['name']) : '',
            'type' => !empty($data['type']) ? strtolower($data['type']) : 'product',
            'product_unit_id' => $productUnitId,
            'purchase_cost' => !empty($data['purchase_cost']) ? (float) $data['purchase_cost'] : 0,
            'selling_price' => !empty($data['selling_price']) ? (float) $data['selling_price'] : 0,
            'descriptions' => $data['descriptions'] ?? null,
            'stock_management' => $stockManagement,
            'initial_stock' => $initialStock,
            'stock' => $initialStock,
            'allow_for_selling' => $allowForSelling,
            'allow_for_purchasing' => $allowForPurchasing,
            'income_account_id' => $incomeAccountId,
            'expense_account_id' => $expenseAccountId,
            'status' => $status,
            'expiry_date' => $expiryDate,
            'brand_id' => $brandId,
            'sub_category_id' => $subCategoryId,
            'image' => 'default.png',
        ];

        // Check if ID is provided for update
        if (!empty($data['id'])) {
            $product = Product::find($data['id']);
            if ($product) {
                // Handle stock difference for updates
                $previousInitialStock = $product->initial_stock ?? 0;
                $stockDifference = $initialStock - $previousInitialStock;
                
                $product->update($productData);
                
                // Update current stock
                if ($stockDifference != 0) {
                    $product->stock = ($product->stock ?? 0) + $stockDifference;
                    $product->save();
                    
                    // Handle transactions for stock changes
                    $this->handleStockTransactions($product, $stockDifference);
                }
                
                return null; // Return null since we've already updated
            }
        }

        // Create new product
        $product = new Product($productData);
        $product->save();
        
        if ($initialStock > 0) {
            $this->createStockTransactions($product, $initialStock, 'Opening Stock');
        }
        
        return null; // Return null since we've already saved manually
    }

    /**
     * Ensure required accounts exist
     */
    protected function ensureAccountsExist(): void
    {
        if (!Account::where('account_name', 'Common Shares')->where('business_id', request()->activeBusiness->id)->exists()) {
            $account = new Account();
            $account->account_code = '3000';
            $account->account_name = 'Common Shares';
            $account->account_type = 'Equity';
            $account->opening_date = Carbon::now()->format('Y-m-d');
            $account->business_id = request()->activeBusiness->id;
            $account->user_id = request()->activeBusiness->user->id;
            $account->dr_cr = 'cr';
            $account->save();
        }

        if (!Account::where('account_name', 'Inventory')->where('business_id', request()->activeBusiness->id)->exists()) {
            $account = new Account();
            $account->account_code = '1000';
            $account->account_name = 'Inventory';
            $account->account_type = 'Other Current Asset';
            $account->opening_date = Carbon::now()->format('Y-m-d');
            $account->business_id = request()->activeBusiness->id;
            $account->user_id = request()->activeBusiness->user->id;
            $account->dr_cr = 'dr';
            $account->save();
        }
    }

    /**
     * Handle stock transactions for updates
     */
    protected function handleStockTransactions(Product $product, float $stockDifference): void
    {
        // Delete previous stock transactions
        Transaction::where('ref_id', $product->id)
            ->where('ref_type', 'product')
            ->where('description', 'like', '%Opening Stock%')
            ->delete();

        if ($stockDifference > 0) {
            $this->createStockTransactions($product, $stockDifference, 'Opening Stock Adjustment +' . $stockDifference, 'increase');
        } else {
            $this->createStockTransactions($product, abs($stockDifference), 'Opening Stock Adjustment -' . abs($stockDifference), 'decrease');
        }
    }

    /**
     * Create stock transactions
     */
    protected function createStockTransactions(Product $product, float $quantity, string $descriptionSuffix, string $type = 'increase'): void
    {
        $purchaseCost = $product->purchase_cost ?? 0;
        $amount = $purchaseCost * $quantity;
        $currency = request()->activeBusiness->currency;
        $exchangeRate = Currency::where('name', $currency)->first()->exchange_rate ?? 1;

        if ($type === 'increase') {
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

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
