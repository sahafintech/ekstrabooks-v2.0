<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\Approvals;
use App\Models\BusinessSetting;
use App\Models\Currency;
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
 * BillInvoiceImport handles importing credit purchases from Excel/CSV files.
 *
 * Important: Multiple rows with the same bill_number will be grouped into
 * a single Purchase with multiple PurchaseItem records.
 */
class BillInvoiceImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * Field mappings from Excel header to system field.
     */
    private array $mappings;

    public function __construct(array $mappings = [])
    {
        $this->mappings = $mappings;
    }

    /**
     * Transform row data using field mappings.
     */
    private function mapRowData(array $row): array
    {
        if (empty($this->mappings)) {
            return $row;
        }

        $mappedData = [];

        foreach ($row as $header => $value) {
            $normalizedHeader = $this->normalizeHeader((string) $header);

            foreach ($this->mappings as $excelHeader => $systemField) {
                $normalizedExcelHeader = $this->normalizeHeader((string) $excelHeader);
                if ($normalizedHeader === $normalizedExcelHeader && $systemField !== 'skip') {
                    $mappedData[$systemField] = $value;
                    break;
                }
            }
        }

        return $mappedData;
    }

    /**
     * Normalize header for comparison.
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

        $this->ensureDefaultAccounts();

        $groupedBills = [];
        $autoGroupCounter = 0;

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());
            $billNumber = $this->normalizeBillNumber($data['bill_number'] ?? null);
            $productName = trim((string) ($data['product_name'] ?? ''));

            if ($billNumber === null && $productName === '') {
                continue;
            }

            $groupKey = $billNumber !== null
                ? 'bill::' . strtolower($billNumber)
                : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedBills[$groupKey])) {
                $groupedBills[$groupKey] = [
                    'bill_number' => $billNumber,
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedBills[$groupKey]['items'][] = $data;
        }

        foreach ($groupedBills as $groupKey => $billData) {
            DB::beginTransaction();
            try {
                $this->createBill($billData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $loggedBillNumber = $billData['bill_number'] ?? $groupKey;
                \Log::error("Error importing bill {$loggedBillNumber}: " . $e->getMessage());
            }
        }
    }

    private function createBill(array $billData): void
    {
        $billNumber = $billData['bill_number'] ?? null;
        $header = $billData['header'];
        $items = $billData['items'];

        $invoiceDate = $this->parseDate($header['invoice_date'] ?? null);
        $dueDate = $this->parseDate($header['due_date'] ?? $invoiceDate);

        $vendor = null;
        if (!empty($header['supplier_name'])) {
            $vendor = Vendor::where('name', 'like', '%' . trim((string) $header['supplier_name']) . '%')->first();
        }

        $currency = trim((string) ($header['transaction_currency'] ?? request()->activeBusiness->currency));
        $exchangeRate = 1.0;
        if ($currency !== '') {
            $currencyModel = Currency::where('name', 'like', '%' . $currency . '%')->first();
            if ($currencyModel && $currencyModel->exchange_rate > 0) {
                $exchangeRate = (float) $currencyModel->exchange_rate;
            }
        }

        $subTotal = 0.0;
        $totalTax = 0.0;
        $itemsData = [];
        $inventoryAccount = get_account('Inventory');

        foreach ($items as $itemData) {
            $quantity = (float) ($itemData['quantity'] ?? 1);
            $unitCost = (float) ($itemData['unit_cost'] ?? 0);
            $lineTotal = $quantity * $unitCost;
            $subTotal += $lineTotal;

            $isInventory = $this->parseIsInventoryValue($itemData['is_inventory'] ?? 1);
            $productName = trim((string) ($itemData['product_name'] ?? ''));
            $itemTaxes = [];
            $itemTaxTotal = 0.0;

            $product = null;
            if ($productName !== '') {
                $product = Product::where('name', 'like', '%' . $productName . '%')->first();
            }

            $expenseAccount = null;
            if (!empty($itemData['expense_account'])) {
                $expenseAccount = Account::where('account_name', 'like', '%' . trim((string) $itemData['expense_account']) . '%')->first();
            }

            $itemAccountId = $isInventory === 1
                ? ($inventoryAccount->id ?? $expenseAccount->id ?? null)
                : ($expenseAccount->id ?? null);

            if ($isInventory === 0 && !$expenseAccount) {
                throw new \RuntimeException('Expense account is required for account purchase rows (is_inventory = 0).');
            }

            if ($itemAccountId === null) {
                throw new \RuntimeException('Unable to resolve an account for imported bill item "' . ($productName !== '' ? $productName : 'N/A') . '".');
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
            ];
        }

        $discountType = (int) ($header['discount_type'] ?? 0);
        $discountValue = (float) ($header['discount_value'] ?? 0);
        $discountAmount = 0.0;

        if ($discountType === 0 && $discountValue > 0) {
            $discountAmount = ($subTotal / 100) * $discountValue;
        } elseif ($discountType === 1 && $discountValue > 0) {
            $discountAmount = $discountValue;
        }

        $grandTotal = ($subTotal + $totalTax) - $discountAmount;

        $purchase = new Purchase();
        $purchase->vendor_id = $vendor->id ?? null;
        $purchase->title = trim((string) ($header['title'] ?? ''));

        if ($billNumber === null || $billNumber === '') {
            $purchase->bill_no = get_business_option('purchase_number');
            BusinessSetting::where('name', 'purchase_number')->increment('value');
        } else {
            $purchase->bill_no = $billNumber;
        }

        $purchase->po_so_number = trim((string) ($header['order_number'] ?? ''));
        $purchase->purchase_date = $invoiceDate;
        $purchase->due_date = $dueDate;
        $purchase->sub_total = $subTotal / $exchangeRate;
        $purchase->grand_total = $grandTotal / $exchangeRate;
        $purchase->converted_total = $grandTotal;
        $purchase->exchange_rate = $exchangeRate;
        $purchase->currency = $currency;
        $purchase->paid = 0;
        $purchase->discount = $discountAmount / $exchangeRate;
        $purchase->cash = 0;
        $purchase->discount_type = $discountType;
        $purchase->discount_value = $discountValue;
        $purchase->template_type = 0;
        $purchase->template = 'default';
        $purchase->note = trim((string) ($header['note'] ?? ''));
        $purchase->footer = get_business_option('purchase_footer');
        $purchase->withholding_tax = 0;

        // Imported bills begin in pending state and move to transactions after approval.
        $purchase->approval_status = 0;
        $purchase->checker_status = 0;
        $purchase->approved_by = null;
        $purchase->checked_by = null;
        $purchase->created_by = auth()->id();
        $purchase->short_code = rand(100000, 9999999) . uniqid();
        $purchase->status = 0;

        $purchase->save();
        $this->createBillApprovalRecords($purchase);
        $this->createBillCheckerRecords($purchase);

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

        $this->createPendingTransactions($purchase);
    }

    /**
     * Create accounting transactions in pending_transactions for the bill.
     */
    private function createPendingTransactions(Purchase $purchase): void
    {
        $currentTime = Carbon::now();
        $currency = $purchase->currency;
        $exchangeRate = $purchase->exchange_rate;

        if ($purchase->grand_total <= 0) {
            return;
        }

        $accountsPayable = get_account('Accounts Payable');
        if (!$accountsPayable) {
            throw new \RuntimeException('Accounts Payable account was not found.');
        }

        $transactionDate = $purchase->getRawOriginal('purchase_date') ?: $purchase->purchase_date;
        $transDate = Carbon::parse($transactionDate)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');

        $purchase->load('items.taxes');
        $taxCache = [];

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
                $transaction->description = 'Bill Invoice #' . $purchase->bill_no;
                $transaction->ref_id = $purchase->id;
                $transaction->ref_type = 'bill invoice';
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
                $taxTransaction->description = 'Bill Invoice Tax #' . $purchase->bill_no;
                $taxTransaction->ref_id = $purchase->id;
                $taxTransaction->ref_type = 'bill invoice tax';
                $taxTransaction->tax_id = $itemTax->tax_id;
                $taxTransaction->vendor_id = $purchase->vendor_id;
                $taxTransaction->save();
            }
        }

        $payableTransaction = new PendingTransaction();
        $payableTransaction->trans_date = $transDate;
        $payableTransaction->account_id = $accountsPayable->id;
        $payableTransaction->dr_cr = 'cr';
        $payableTransaction->transaction_currency = $currency;
        $payableTransaction->currency_rate = $exchangeRate;
        $payableTransaction->base_currency_amount = $purchase->grand_total;
        $payableTransaction->transaction_amount = $purchase->grand_total * $exchangeRate;
        $payableTransaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
        $payableTransaction->ref_id = $purchase->id;
        $payableTransaction->ref_type = 'bill invoice';
        $payableTransaction->vendor_id = $purchase->vendor_id;
        $payableTransaction->save();

        if ($purchase->discount > 0) {
            $discountAccount = get_account('Purchase Discount Allowed');
            if ($discountAccount) {
                $discountTransaction = new PendingTransaction();
                $discountTransaction->trans_date = $transDate;
                $discountTransaction->account_id = $discountAccount->id;
                $discountTransaction->dr_cr = 'cr';
                $discountTransaction->transaction_currency = $currency;
                $discountTransaction->currency_rate = $exchangeRate;
                $discountTransaction->base_currency_amount = $purchase->discount;
                $discountTransaction->transaction_amount = $purchase->discount * $exchangeRate;
                $discountTransaction->description = 'Bill Invoice Discount #' . $purchase->bill_no;
                $discountTransaction->ref_id = $purchase->id;
                $discountTransaction->ref_type = 'bill invoice';
                $discountTransaction->vendor_id = $purchase->vendor_id;
                $discountTransaction->save();
            }
        }
    }

    private function createBillApprovalRecords(Purchase $purchase): void
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

    private function createBillCheckerRecords(Purchase $purchase): void
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
    private function normalizeBillNumber($billNumber): ?string
    {
        if ($billNumber === null) {
            return null;
        }

        $normalized = trim((string) $billNumber);
        return $normalized === '' ? null : $normalized;
    }

    /**
     * Parse is_inventory flag.
     * 1 = inventory item, 0 = account purchase row.
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
     * Parse tax names from a comma/semicolon separated cell value.
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
     * Parse date from Excel numeric or text date formats.
     */
    private function parseDate($date): string
    {
        if (empty($date)) {
            return now()->format('Y-m-d');
        }

        if (is_numeric($date)) {
            try {
                return Date::excelToDateTimeObject($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return now()->format('Y-m-d');
            }
        }

        $rawDate = trim((string) $date);

        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $rawDate) === 1) {
            try {
                return Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // Continue to other formats.
            }
        }

        if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $rawDate) === 1) {
            try {
                return Carbon::createFromFormat('d-m-Y', $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // Continue to other formats.
            }
        }

        foreach (['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'n/j/Y', 'n-j-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // Continue trying formats.
            }
        }

        try {
            return Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }

    /**
     * Ensure required default accounts exist.
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
