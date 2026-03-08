<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\Approvals;
use App\Models\BusinessSetting;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemTax;
use App\Models\Tax;
use App\Models\User;
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

        // Group rows by bill_no (duplicate bill_no = multi-item purchase)
        $groupedPurchases = [];
        $autoGroupCounter = 0;

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());
            $billNo = $this->normalizeBillNo($data['bill_no'] ?? null);
            $productName = trim((string) ($data['product_name'] ?? ''));

            // Skip empty rows
            if ($billNo === null && $productName === '') {
                continue;
            }

            // Rows without bill_no become their own purchases
            $groupKey = $billNo !== null
                ? 'bill::' . strtolower($billNo)
                : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedPurchases[$groupKey])) {
                $groupedPurchases[$groupKey] = [
                    'bill_no' => $billNo,
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedPurchases[$groupKey]['items'][] = $data;
        }

        // Process each grouped purchase
        foreach ($groupedPurchases as $groupKey => $purchaseData) {
            DB::beginTransaction();
            try {
                $this->createPurchase($purchaseData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                // Log error but continue with other purchases
                $loggedBillNo = $purchaseData['bill_no'] ?? $groupKey;
                \Log::error("Error importing purchase {$loggedBillNo}: " . $e->getMessage());
            }
        }
    }

    /**
     * Create a purchase with its items
     */
    private function createPurchase(array $purchaseData): void
    {
        $billNo = $purchaseData['bill_no'] ?? null;
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
        $totalTax = 0;
        $itemsData = [];
        $inventoryAccount = get_account('Inventory');

        foreach ($items as $itemData) {
            $quantity = floatval($itemData['quantity'] ?? 1);
            $unitCost = floatval($itemData['unit_cost'] ?? 0);
            $lineTotal = $quantity * $unitCost;
            $subTotal += $lineTotal;
            $isInventory = $this->parseIsInventoryValue($itemData['is_inventory'] ?? 1);
            $productName = trim((string) ($itemData['product_name'] ?? ''));
            $itemTaxes = [];
            $itemTaxTotal = 0;

            // Find product
            $product = null;
            if ($productName !== '') {
                $product = Product::where('name', 'like', '%' . $productName . '%')->first();
            }

            // Find expense account
            $expenseAccount = null;
            if (!empty($itemData['expense_account'])) {
                $expenseAccount = Account::where('account_name', 'like', '%' . trim((string) $itemData['expense_account']) . '%')->first();
            }

            // Inventory rows debit Inventory account and can update stock.
            // Account rows debit provided expense account and never update stock.
            $itemAccountId = $isInventory === 1
                ? ($inventoryAccount->id ?? $expenseAccount->id ?? null)
                : ($expenseAccount->id ?? null);

            if ($isInventory === 0 && !$expenseAccount) {
                throw new \RuntimeException('Expense account is required for account purchase rows (is_inventory = 0).');
            }

            if ($itemAccountId === null) {
                throw new \RuntimeException('Unable to resolve an account for imported purchase item "' . ($productName !== '' ? $productName : 'N/A') . '".');
            }

            foreach ($this->parseTaxNames($itemData['tax'] ?? null) as $taxName) {
                $tax = Tax::where('name', 'like', '%' . $taxName . '%')->first();

                if (!$tax) {
                    throw new \RuntimeException('Tax "' . $taxName . '" was not found in Tax Database.');
                }

                $taxAmount = ($lineTotal / 100) * $tax->rate;
                $itemTaxTotal += $taxAmount;

                $itemTaxes[] = [
                    'tax_id' => $tax->id,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => $taxAmount,
                ];
            }

            $totalTax += $itemTaxTotal;

            $itemsData[] = [
                'product' => $product,
                'product_id' => $isInventory === 1 ? ($product->id ?? null) : null,
                'is_inventory' => $isInventory,
                'product_name' => $product->name ?? ($productName !== '' ? $productName : 'Unknown Product'),
                'description' => trim((string) ($itemData['description'] ?? ($product->descriptions ?? ''))),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'sub_total' => $lineTotal,
                'account_id' => $itemAccountId,
                'item_taxes' => $itemTaxes,
                'tax_total' => $itemTaxTotal,
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

        $grandTotal = ($subTotal + $totalTax) - $discountAmount;
        $exchangeRate = floatval($header['exchange_rate'] ?? 1);
        $currency = trim((string) ($header['currency'] ?? request()->activeBusiness->currency));

        // Create purchase
        $purchase = new Purchase();
        $purchase->vendor_id = $vendor->id ?? null;
        $purchase->title = trim((string) ($header['title'] ?? ''));

        // Use provided bill_no or generate new one
        if ($billNo === null || $billNo === '') {
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

        // All imported cash purchases begin in pending state and move to transactions after approval.
        $purchase->approval_status = 0;
        $purchase->checker_status = 0;
        $purchase->approved_by = null;
        $purchase->checked_by = null;
        $purchase->created_by = auth()->user()->id;
        $purchase->benificiary = trim((string) ($header['beneficiary'] ?? ''));
        $purchase->short_code = rand(100000, 9999999) . uniqid();
        $purchase->status = 2;

        $purchase->save();
        $this->createPurchaseApprovalRecords($purchase);
        $this->createPurchaseCheckerRecords($purchase);

        // Create purchase items
        foreach ($itemsData as $item) {
            $purchaseItem = new PurchaseItem([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'sub_total' => $item['sub_total'],
                'account_id' => $item['account_id'],
            ]);
            $purchase->items()->save($purchaseItem);

            foreach ($item['item_taxes'] as $itemTax) {
                $purchaseItem->taxes()->save(new PurchaseItemTax([
                    'purchase_id' => $purchase->id,
                    'tax_id' => $itemTax['tax_id'],
                    'name' => $itemTax['name'],
                    'amount' => $itemTax['amount'],
                ]));
            }

            // Update stock only for inventory rows.
            if (
                $item['is_inventory'] === 1 &&
                $item['product'] &&
                $item['product']->type == 'product' &&
                $item['product']->stock_management == 1
            ) {
                $item['product']->stock += $item['quantity'];
                $item['product']->save();
            }
        }

        $this->createPendingTransactions($purchase, $paymentAccount);
    }

    /**
     * Create accounting transactions in pending_transactions for the purchase.
     */
    private function createPendingTransactions(Purchase $purchase, ?Account $paymentAccount): void
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

        $transactionDate = $purchase->getRawOriginal('purchase_date') ?: $purchase->purchase_date;
        $transDate = Carbon::parse($transactionDate)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');
        $purchase->load('items.taxes');
        $taxCache = [];

        // Debit the expense accounts (one per item)
        foreach ($purchase->items as $item) {
            if ($item->account_id && $item->sub_total > 0) {
                $transaction = new PendingTransaction();
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

            foreach ($item->taxes as $itemTax) {
                if ($itemTax->amount <= 0 || !$itemTax->tax_id) {
                    continue;
                }

                if (!array_key_exists((int) $itemTax->tax_id, $taxCache)) {
                    $taxCache[(int) $itemTax->tax_id] = Tax::find($itemTax->tax_id);
                }

                $tax = $taxCache[(int) $itemTax->tax_id];
                if (!$tax || !$tax->account_id) {
                    continue;
                }

                $taxTransaction = new PendingTransaction();
                $taxTransaction->trans_date = $transDate;
                $taxTransaction->account_id = $tax->account_id;
                $taxTransaction->dr_cr = 'dr';
                $taxTransaction->transaction_currency = $currency;
                $taxTransaction->currency_rate = $exchangeRate;
                $taxTransaction->base_currency_amount = $itemTax->amount;
                $taxTransaction->transaction_amount = $itemTax->amount * $exchangeRate;
                $taxTransaction->description = 'Cash Purchase Tax #' . $purchase->bill_no;
                $taxTransaction->ref_id = $purchase->id;
                $taxTransaction->ref_type = 'cash purchase tax';
                $taxTransaction->tax_id = $itemTax->tax_id;
                $taxTransaction->vendor_id = $purchase->vendor_id;
                $taxTransaction->save();
            }
        }

        // Credit the payment account
        $transaction = new PendingTransaction();
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
                $transaction = new PendingTransaction();
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

    private function createPurchaseApprovalRecords(Purchase $purchase): void
    {
        $purchaseApprovalUsersJson = get_business_option('purchase_approval_users', '[]');
        $configuredUserIds = json_decode($purchaseApprovalUsersJson, true);

        if (!is_array($configuredUserIds) || empty($configuredUserIds)) {
            return;
        }

        $validUserIds = User::whereIn('id', $configuredUserIds)->pluck('id')->toArray();
        if (empty($validUserIds)) {
            return;
        }

        foreach ($validUserIds as $userId) {
            $exists = Approvals::where('ref_id', $purchase->id)
                ->where('ref_name', 'purchase')
                ->where('checker_type', 'approval')
                ->where('action_user', $userId)
                ->exists();

            if (!$exists) {
                Approvals::create([
                    'ref_id' => $purchase->id,
                    'ref_name' => 'purchase',
                    'checker_type' => 'approval',
                    'action_user' => $userId,
                    'status' => 0,
                ]);
            }
        }
    }

    private function createPurchaseCheckerRecords(Purchase $purchase): void
    {
        $purchaseCheckerUsersJson = get_business_option('purchase_checker_users', '[]');
        $configuredUserIds = json_decode($purchaseCheckerUsersJson, true);

        if (!is_array($configuredUserIds) || empty($configuredUserIds)) {
            return;
        }

        $validUserIds = User::whereIn('id', $configuredUserIds)->pluck('id')->toArray();
        if (empty($validUserIds)) {
            return;
        }

        foreach ($validUserIds as $userId) {
            $exists = Approvals::where('ref_id', $purchase->id)
                ->where('ref_name', 'purchase')
                ->where('checker_type', 'checker')
                ->where('action_user', $userId)
                ->exists();

            if (!$exists) {
                Approvals::create([
                    'ref_id' => $purchase->id,
                    'ref_name' => 'purchase',
                    'checker_type' => 'checker',
                    'action_user' => $userId,
                    'status' => 0,
                ]);
            }
        }
    }

    /**
     * Normalize bill number; empty values return null.
     */
    private function normalizeBillNo($billNo): ?string
    {
        if ($billNo === null) {
            return null;
        }

        $normalized = trim((string) $billNo);
        return $normalized === '' ? null : $normalized;
    }

    /**
     * Parse is_inventory flag.
     * 1 = inventory item (increase stock), 0 = account purchase row.
     */
    private function parseIsInventoryValue($value): int
    {
        if ($value === null) {
            return 1;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '' || $normalized === '1' || $normalized === 'true') {
            return 1;
        }

        if ($normalized === '0' || $normalized === 'false') {
            return 0;
        }

        return 1;
    }

    /**
     * Parse tax names from cell value. Supports comma/semicolon separated names.
     */
    private function parseTaxNames($value): array
    {
        if ($value === null) {
            return [];
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[;,]/', $raw) ?: [];
        $names = [];
        foreach ($parts as $part) {
            $name = trim((string) $part);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
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

        $rawDate = trim((string) $date);

        // Excel text dates like 08/03/2026 should be treated as dd/mm/yyyy.
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $rawDate) === 1) {
            try {
                return Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // Fall through to other parsers.
            }
        }

        if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $rawDate) === 1) {
            try {
                return Carbon::createFromFormat('d-m-Y', $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // Fall through to other parsers.
            }
        }

        // Try a few explicit formats before generic parsing.
        foreach (['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'n/j/Y', 'n-j-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // Continue trying formats.
            }
        }

        // Final fallback
        try {
            return Carbon::parse($rawDate)->format('Y-m-d');
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
