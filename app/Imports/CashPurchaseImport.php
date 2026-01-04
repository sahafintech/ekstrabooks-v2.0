<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * CashPurchaseImport handles importing cash purchases from Excel/CSV files.
 * 
 * Important: Multiple rows with the same bill_no will be grouped into
 * a single Purchase with multiple PurchaseItem records.
 */
class CashPurchaseImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * Field mappings from Excel header to system field
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
            $normalizedHeader = $this->normalizeHeader((string) $header);

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
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        // Ensure default accounts exist
        $this->ensureDefaultAccounts();

        // Group rows by bill_no
        $groupedPurchases = [];

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());

            // Skip empty rows
            if (empty($data['bill_no']) && empty($data['product_name'])) {
                continue;
            }

            $billNo = !empty($data['bill_no']) ? trim((string) $data['bill_no']) : 'AUTO_' . uniqid();

            if (!isset($groupedPurchases[$billNo])) {
                $groupedPurchases[$billNo] = [
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedPurchases[$billNo]['items'][] = $data;
        }

        // Process each grouped purchase
        foreach ($groupedPurchases as $billNo => $purchaseData) {
            DB::beginTransaction();
            try {
                $this->createPurchase((string) $billNo, $purchaseData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                // Log error but continue with other purchases
                \Log::error("Error importing purchase {$billNo}: " . $e->getMessage());
            }
        }
    }

    /**
     * Create a purchase with its items
     */
    private function createPurchase(string $billNo, array $purchaseData): void
    {
        $header = $purchaseData['header'];
        $items = $purchaseData['items'];

        // Parse purchase date
        $purchaseDate = $this->parseDate($header['purchase_date'] ?? null);

        // Find vendor
        $vendor = null;
        if (!empty($header['vendor_name'])) {
            $vendor = Vendor::where('name', 'like', '%' . trim((string) $header['vendor_name']) . '%')->first();
        }

        // Find payment account
        $paymentAccount = null;
        if (!empty($header['payment_account'])) {
            $paymentAccount = Account::where('account_name', 'like', '%' . trim((string) $header['payment_account']) . '%')->first();
        }

        // Calculate totals from items
        $subTotal = 0;
        $itemsData = [];

        foreach ($items as $itemData) {
            $quantity = floatval($itemData['quantity'] ?? 1);
            $unitCost = floatval($itemData['unit_cost'] ?? 0);
            $lineTotal = $quantity * $unitCost;
            $subTotal += $lineTotal;

            // Find product
            $product = null;
            if (!empty($itemData['product_name'])) {
                $product = Product::where('name', 'like', '%' . trim((string) $itemData['product_name']) . '%')->first();
            }

            // Find expense account
            $expenseAccount = null;
            if (!empty($itemData['expense_account'])) {
                $expenseAccount = Account::where('account_name', 'like', '%' . trim((string) $itemData['expense_account']) . '%')->first();
            }

            $itemsData[] = [
                'product' => $product,
                'product_name' => $product->name ?? trim((string) ($itemData['product_name'] ?? 'Unknown Product')),
                'description' => trim((string) ($itemData['description'] ?? ($product->descriptions ?? ''))),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'sub_total' => $lineTotal,
                'account_id' => $expenseAccount->id ?? null,
            ];
        }

        // Calculate discount
        $discountType = intval($header['discount_type'] ?? 0);
        $discountValue = floatval($header['discount_value'] ?? 0);
        $discountAmount = 0;

        if ($discountType == 0 && $discountValue > 0) {
            // Percentage discount
            $discountAmount = ($subTotal / 100) * $discountValue;
        } elseif ($discountType == 1 && $discountValue > 0) {
            // Fixed discount
            $discountAmount = $discountValue;
        }

        $grandTotal = $subTotal - $discountAmount;
        $exchangeRate = floatval($header['exchange_rate'] ?? 1);
        $currency = trim((string) ($header['currency'] ?? request()->activeBusiness->currency));

        // Create purchase
        $purchase = new Purchase();
        $purchase->vendor_id = $vendor->id ?? null;
        $purchase->title = trim((string) ($header['title'] ?? ''));

        // Use provided bill_no or generate new one
        if (strpos($billNo, 'AUTO_') === 0) {
            $purchase->bill_no = get_business_option('purchase_number');
            BusinessSetting::where('name', 'purchase_number')->increment('value');
        } else {
            $purchase->bill_no = $billNo;
        }

        $purchase->po_so_number = trim((string) ($header['po_so_number'] ?? ''));
        $purchase->purchase_date = $purchaseDate;
        $purchase->due_date = $purchaseDate;
        $purchase->sub_total = $subTotal / $exchangeRate;
        $purchase->grand_total = $grandTotal / $exchangeRate;
        $purchase->converted_total = $grandTotal;
        $purchase->exchange_rate = $exchangeRate;
        $purchase->currency = $currency;
        $purchase->paid = $grandTotal / $exchangeRate;
        $purchase->discount = $discountAmount / $exchangeRate;
        $purchase->cash = 1;
        $purchase->discount_type = $discountType;
        $purchase->discount_value = $discountValue;
        $purchase->template_type = 0;
        $purchase->template = 'default';
        $purchase->note = trim((string) ($header['note'] ?? ''));
        $purchase->footer = get_business_option('purchase_footer');

        // Set approval status
        if (has_permission('cash_purchases.bulk_approve') || request()->isOwner) {
            $purchase->approval_status = 1;
            $purchase->approved_by = auth()->user()->id;
        } else {
            $purchase->approval_status = 0;
            $purchase->approved_by = null;
        }

        $purchase->created_by = auth()->user()->id;
        $purchase->benificiary = trim((string) ($header['beneficiary'] ?? ''));
        $purchase->short_code = rand(100000, 9999999) . uniqid();
        $purchase->status = 2;

        $purchase->save();

        // Create purchase items
        foreach ($itemsData as $item) {
            $purchaseItem = new PurchaseItem([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product']->id ?? null,
                'product_name' => $item['product_name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'sub_total' => $item['sub_total'],
                'account_id' => $item['account_id'],
            ]);
            $purchase->items()->save($purchaseItem);

            // Update stock if applicable
            if ($item['product'] && $item['product']->type == 'product' && $item['product']->stock_management == 1) {
                $item['product']->stock += $item['quantity'];
                $item['product']->save();
            }
        }

        // Create transactions if approved
        if ($purchase->approval_status == 1) {
            $this->createTransactions($purchase, $paymentAccount);
        }
    }

    /**
     * Create accounting transactions for the purchase
     */
    private function createTransactions(Purchase $purchase, ?Account $paymentAccount): void
    {
        $currentTime = Carbon::now();
        $currency = $purchase->currency;
        $exchangeRate = $purchase->exchange_rate;

        // Skip if no payment account
        if (!$paymentAccount) {
            return;
        }

        // Skip if grand total is zero
        if ($purchase->grand_total <= 0) {
            return;
        }

        $transDate = Carbon::parse($purchase->purchase_date)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');

        // Debit the expense accounts (one per item)
        foreach ($purchase->items as $item) {
            if ($item->account_id && $item->sub_total > 0) {
                $transaction = new Transaction();
                $transaction->trans_date = $transDate;
                $transaction->account_id = $item->account_id;
                $transaction->dr_cr = 'dr';
                $transaction->transaction_currency = $currency;
                $transaction->currency_rate = $exchangeRate;
                $transaction->base_currency_amount = $item->sub_total;
                $transaction->transaction_amount = $item->sub_total * $exchangeRate;
                $transaction->description = 'Cash Purchase #' . $purchase->bill_no;
                $transaction->ref_id = $purchase->id;
                $transaction->ref_type = 'cash purchase';
                $transaction->vendor_id = $purchase->vendor_id;
                $transaction->save();
            }
        }

        // Credit the payment account
        $transaction = new Transaction();
        $transaction->trans_date = $transDate;
        $transaction->account_id = $paymentAccount->id;
        $transaction->dr_cr = 'cr';
        $transaction->transaction_currency = $currency;
        $transaction->currency_rate = $exchangeRate;
        $transaction->base_currency_amount = $purchase->grand_total;
        $transaction->transaction_amount = $purchase->grand_total * $exchangeRate;
        $transaction->description = 'Cash Purchase Payment #' . $purchase->bill_no;
        $transaction->ref_id = $purchase->id;
        $transaction->ref_type = 'cash purchase payment';
        $transaction->vendor_id = $purchase->vendor_id;
        $transaction->save();

        // Handle discount transaction if applicable
        if ($purchase->discount > 0) {
            $discountAccount = get_account('Purchase Discount Allowed');
            if ($discountAccount) {
                $transaction = new Transaction();
                $transaction->trans_date = $transDate;
                $transaction->account_id = $discountAccount->id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $currency;
                $transaction->currency_rate = $exchangeRate;
                $transaction->base_currency_amount = $purchase->discount;
                $transaction->transaction_amount = $purchase->discount * $exchangeRate;
                $transaction->description = 'Cash Purchase Discount #' . $purchase->bill_no;
                $transaction->ref_id = $purchase->id;
                $transaction->ref_type = 'cash purchase';
                $transaction->vendor_id = $purchase->vendor_id;
                $transaction->save();
            }
        }
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($date): string
    {
        if (empty($date)) {
            return now()->format('Y-m-d');
        }

        // If it's a numeric value, it's likely an Excel date serial number
        if (is_numeric($date)) {
            try {
                return Date::excelToDateTimeObject($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return now()->format('Y-m-d');
            }
        }

        // Try to parse as date string
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }

    /**
     * Ensure default accounts exist
     */
    private function ensureDefaultAccounts(): void
    {
        $defaultAccounts = [
            ['name' => 'Accounts Payable', 'code' => '2100', 'type' => 'Current Liability', 'dr_cr' => 'cr'],
            ['name' => 'Purchase Tax Payable', 'code' => '2201', 'type' => 'Current Liability', 'dr_cr' => 'dr'],
            ['name' => 'Purchase Discount Allowed', 'code' => '6003', 'type' => 'Cost Of Sale', 'dr_cr' => 'cr'],
            ['name' => 'Inventory', 'code' => '1000', 'type' => 'Other Current Asset', 'dr_cr' => 'dr'],
        ];

        foreach ($defaultAccounts as $account) {
            if (!Account::where('account_name', $account['name'])
                ->where('business_id', request()->activeBusiness->id)
                ->exists()) {
                $accountObj = new Account();
                $accountObj->account_code = $account['code'];
                $accountObj->account_name = $account['name'];
                $accountObj->account_type = $account['type'];
                $accountObj->dr_cr = $account['dr_cr'];
                $accountObj->business_id = request()->activeBusiness->id;
                $accountObj->user_id = request()->activeBusiness->user->id;
                $accountObj->opening_date = now()->format('Y-m-d');
                $accountObj->save();
            }
        }
    }
}
