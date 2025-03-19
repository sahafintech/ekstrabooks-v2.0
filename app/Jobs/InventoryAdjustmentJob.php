<?php

namespace App\Jobs;

use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Imports\InventoryAdjustmentImport;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\Currency;

class InventoryAdjustmentJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $userId;
    protected $businessId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $userId, int $businessId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->businessId = $businessId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip processing if the batch has been cancelled
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            // Import and validate the Excel file
            $import = new InventoryAdjustmentImport();
            $rows = Excel::toCollection($import, $this->filePath)->first();
            
            DB::beginTransaction();

            foreach ($rows as $row) {
                // Skip processing if the batch has been cancelled
                if ($this->batch() && $this->batch()->cancelled()) {
                    DB::rollBack();
                    return;
                }

                $product = Product::where('name', $row['product_name'])->where('business_id', $this->businessId)->firstOrFail();
                $account = Account::where('account_name', $row['account_name'])->where('business_id', $this->businessId)->firstOrFail();

                $adjustment = new InventoryAdjustment();
                $adjustment->adjustment_date = Date::excelToDateTimeObject($row['adjustment_date'])->format('Y-m-d');
                $adjustment->account_id = $account->id;
                $adjustment->product_id = $product->id;
                $adjustment->quantity_on_hand = $row['quantity_on_hand'];
                $adjustment->adjusted_quantity = $row['adjusted_quantity'];
                $adjustment->new_quantity_on_hand = floatval($row['quantity_on_hand']) + floatval($row['adjusted_quantity']);
                $adjustment->description = $row['description'] ?? null;
                $adjustment->adjustment_type = $row['adjusted_quantity'] > 0 ? 'adds' : 'deducts';
                $adjustment->user_id = $this->userId;
                $adjustment->business_id = $this->businessId;
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
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
