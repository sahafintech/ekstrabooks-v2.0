<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Account;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInventoryAdjustmentImportRow implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $businessId;
    protected $rowData;

    /**
     * Create a new job instance.
     * 
     * @param int $businessId
     * @param array $rowData
     */
    public function __construct($businessId, $rowData)
    {
        $this->businessId = $businessId;
        $this->rowData = $rowData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // If batch is cancelled, don't process this job
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            // Process the row data and create inventory adjustment
            // Expected columns in rowData:
            // 0: product_code, 1: adjustment_type, 2: quantity, 3: date, 4: reference, 5: account, 6: currency_code, 7: notes
            
            // Extract data from the row
            $productCode = $this->rowData[0] ?? null;
            $adjustmentType = strtolower($this->rowData[1] ?? '');
            $quantity = (float)($this->rowData[2] ?? 0);
            $dateStr = $this->rowData[3] ?? null;
            $reference = $this->rowData[4] ?? null;
            $accountName = $this->rowData[5] ?? null;
            $currencyCode = strtoupper($this->rowData[6] ?? 'USD');
            $notes = $this->rowData[7] ?? null;
            
            // Validate required fields
            if (!$productCode || !$adjustmentType || !$quantity || !$dateStr || !$accountName) {
                throw new \Exception("Missing required fields in import row");
            }
            
            // Find the product by code
            $product = Product::where('business_id', $this->businessId)
                ->where('code', $productCode)
                ->first();
                
            if (!$product) {
                throw new \Exception("Product not found: {$productCode}");
            }
            
            // Find the account
            $account = Account::where('business_id', $this->businessId)
                ->where('name', $accountName)
                ->first();
                
            if (!$account) {
                throw new \Exception("Account not found: {$accountName}");
            }
            
            // Process date
            try {
                $date = Carbon::createFromFormat('Y-m-d', $dateStr);
            } catch (\Exception $e) {
                try {
                    $date = Carbon::createFromFormat('d/m/Y', $dateStr);
                } catch (\Exception $e) {
                    $date = Carbon::now();
                }
            }

            // Get the currency
            $currency = Currency::where('code', $currencyCode)
                ->where('business_id', $this->businessId)
                ->first();
            
            if (!$currency) {
                // Try to find the default currency (base currency with exchange_rate = 1.000000)
                $currency = Currency::where('business_id', $this->businessId)
                    ->where('exchange_rate', 1.0)
                    ->first();
                    
                if (!$currency) {
                    Log::error('Currency not found for import', [
                        'business_id' => $this->businessId,
                        'currency_code' => $currencyCode
                    ]);
                    throw new \Exception("Currency not found: {$currencyCode}");
                }
            }
            
            // Validate that currency follows ISO 4217 standards
            if (strlen($currency->code) !== 3) {
                Log::error('Invalid currency code (not ISO 4217 compliant)', [
                    'business_id' => $this->businessId,
                    'currency_code' => $currency->code
                ]);
                throw new \Exception("Currency code not ISO 4217 compliant: {$currency->code}");
            }
            
            // Set the adjustment type
            $type = 'addition';
            if (in_array($adjustmentType, ['subtraction', 'deduction', 'removal', 'minus', 'negative', 'deduct'])) {
                $type = 'subtraction';
                // Ensure quantity is positive for proper calculations
                $quantity = abs($quantity);
            }
            
            // Get the default warehouse
            $warehouse = Warehouse::where('business_id', $this->businessId)
                ->where('is_default', 1)
                ->first();
                
            if (!$warehouse) {
                // Get any warehouse if default not found
                $warehouse = Warehouse::where('business_id', $this->businessId)->first();
                
                if (!$warehouse) {
                    Log::error('No warehouse found for import', [
                        'business_id' => $this->businessId
                    ]);
                    return;
                }
            }
            
            DB::beginTransaction();
            try {
                // Create the inventory adjustment
                $adjustment = new InventoryAdjustment();
                $adjustment->business_id = $this->businessId;
                $adjustment->product_id = $product->id;
                $adjustment->warehouse_id = $warehouse->id;
                $adjustment->account_id = $account->id;
                $adjustment->adjustment_type = $type;
                $adjustment->quantity = $quantity;
                $adjustment->date = $date;
                $adjustment->reference = $reference;
                $adjustment->currency_id = $currency->id;
                $adjustment->notes = $notes;
                $adjustment->save();
                
                // Update product stock
                if ($type === 'addition') {
                    $product->increment('stock', $quantity);
                } else {
                    $product->decrement('stock', $quantity);
                }
                
                DB::commit();
                
                Log::info('Successfully imported inventory adjustment', [
                    'business_id' => $this->businessId,
                    'product_id' => $product->id,
                    'adjustment_id' => $adjustment->id
                ]);
                
                // Report progress to the batch
                if ($this->batch()) {
                    $this->batch()->progress();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error in transaction for inventory adjustment import: ' . $e->getMessage(), [
                    'business_id' => $this->businessId,
                    'row_data' => $this->rowData,
                    'exception' => $e
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error processing inventory adjustment import row: ' . $e->getMessage(), [
                'business_id' => $this->businessId,
                'row_data' => $this->rowData,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
