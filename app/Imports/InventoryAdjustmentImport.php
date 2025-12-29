<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\Currency;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

final class InventoryAdjustmentImport implements SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
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
        
        foreach ($row as $header => $value) {
            $normalizedHeader = $this->normalizeHeader($header);
            
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
    public function model(array $row): ?InventoryAdjustment
    {
        // Apply field mappings
        $data = $this->mapRowData($row);
        
        // Skip empty rows
        if (empty($data['product_id']) && empty($data['product_name'])) {
            return null;
        }

        // Get product - by ID or name
        $product = null;
        if (!empty($data['product_id'])) {
            $product = Product::find($data['product_id']);
        } elseif (!empty($data['product_name'])) {
            $product = Product::where('name', $data['product_name'])->first();
        }
        
        if (!$product) {
            return null;
        }

        // Get account - by ID or name
        $account = null;
        if (!empty($data['account_id'])) {
            $account = Account::find($data['account_id']);
        } elseif (!empty($data['account_name'])) {
            $account = Account::where('account_name', $data['account_name'])->first();
        }
        
        if (!$account) {
            return null;
        }

        // Parse adjustment date
        $adjustmentDate = now()->format('Y-m-d');
        if (!empty($data['adjustment_date'])) {
            try {
                if (is_numeric($data['adjustment_date'])) {
                    $adjustmentDate = Date::excelToDateTimeObject($data['adjustment_date'])->format('Y-m-d');
                } else {
                    $adjustmentDate = Carbon::parse($data['adjustment_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $adjustmentDate = now()->format('Y-m-d');
            }
        }

        // Get quantity values
        $quantityOnHand = !empty($data['quantity_on_hand']) ? (float) $data['quantity_on_hand'] : $product->stock;
        $adjustedQuantity = !empty($data['adjusted_quantity']) ? (float) $data['adjusted_quantity'] : 0;
        
        if ($adjustedQuantity == 0) {
            return null;
        }

        // Calculate new quantity
        if ($adjustedQuantity >= 0) {
            $newQuantityOnHand = $quantityOnHand + $adjustedQuantity;
        } else {
            $newQuantityOnHand = $quantityOnHand - abs($adjustedQuantity);
        }

        // Create adjustment
        $adjustment = new InventoryAdjustment();
        $adjustment->adjustment_date = $adjustmentDate;
        $adjustment->account_id = $account->id;
        $adjustment->product_id = $product->id;
        $adjustment->quantity_on_hand = $quantityOnHand;
        $adjustment->adjusted_quantity = $adjustedQuantity;
        $adjustment->new_quantity_on_hand = $newQuantityOnHand;
        $adjustment->description = $data['description'] ?? null;
        $adjustment->adjustment_type = $adjustedQuantity >= 0 ? 'adds' : 'deducts';
        $adjustment->save();

        // Update product stock
        $product->stock = $newQuantityOnHand;
        $product->save();

        // Create transactions
        $this->createTransactions($adjustment, $product, $account, $adjustedQuantity, $adjustmentDate);

        return null; // Return null since we've already saved manually
    }

    /**
     * Create accounting transactions for the adjustment
     */
    protected function createTransactions(InventoryAdjustment $adjustment, Product $product, Account $account, float $adjustedQuantity, string $adjustmentDate): void
    {
        $currentTime = Carbon::now();
        $currency = request()->activeBusiness->currency;
        $exchangeRate = Currency::where('name', $currency)->first()->exchange_rate ?? 1;
        $purchaseCost = $product->purchase_cost ?? 0;
        $amount = abs($adjustedQuantity) * $purchaseCost;
        
        // Skip transaction creation if the amount is zero (product has no purchase cost)
        if ($amount <= 0) {
            return;
        }
        
        $transDate = Carbon::parse($adjustmentDate)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');

        if ($adjustment->adjustment_type == 'adds') {
            // Credit the adjustment account
            $transaction = new Transaction();
            $transaction->trans_date = $transDate;
            $transaction->account_id = $account->id;
            $transaction->dr_cr = 'cr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' Inventory Adjustment #' . $adjustedQuantity;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product adjustment';
            $transaction->save();

            // Debit Inventory account
            $transaction = new Transaction();
            $transaction->trans_date = $transDate;
            $transaction->account_id = get_account('Inventory')->id;
            $transaction->dr_cr = 'dr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' Inventory Adjustment #' . $adjustedQuantity;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product adjustment';
            $transaction->save();
        } else {
            // Debit the adjustment account
            $transaction = new Transaction();
            $transaction->trans_date = $transDate;
            $transaction->account_id = $account->id;
            $transaction->dr_cr = 'dr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' Inventory Adjustment #' . $adjustedQuantity;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product adjustment';
            $transaction->save();

            // Credit Inventory account
            $transaction = new Transaction();
            $transaction->trans_date = $transDate;
            $transaction->account_id = get_account('Inventory')->id;
            $transaction->dr_cr = 'cr';
            $transaction->transaction_currency = $currency;
            $transaction->currency_rate = $exchangeRate;
            $transaction->base_currency_amount = $amount;
            $transaction->transaction_amount = $amount;
            $transaction->description = $product->name . ' Inventory Adjustment #' . $adjustedQuantity;
            $transaction->ref_id = $product->id;
            $transaction->ref_type = 'product adjustment';
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
