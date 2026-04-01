<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemTax;
use App\Models\Tax;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * PurchaseOrderImport handles importing purchase orders from Excel/CSV files.
 *
 * Important: Multiple rows with the same order_number will be grouped into
 * a single PurchaseOrder with multiple PurchaseOrderItem records.
 */
class PurchaseOrderImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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

        $groupedOrders = [];
        $autoGroupCounter = 0;

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());
            $orderNumber = $this->normalizeOrderNumber($data['order_number'] ?? null);
            $productName = trim((string) ($data['product_name'] ?? ''));

            if ($orderNumber === null && $productName === '') {
                continue;
            }

            $groupKey = $orderNumber !== null
                ? 'order::' . strtolower($orderNumber)
                : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedOrders[$groupKey])) {
                $groupedOrders[$groupKey] = [
                    'order_number' => $orderNumber,
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedOrders[$groupKey]['items'][] = $data;
        }

        foreach ($groupedOrders as $groupKey => $orderData) {
            DB::beginTransaction();
            try {
                $this->createPurchaseOrder($orderData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $loggedOrderNumber = $orderData['order_number'] ?? $groupKey;
                \Log::error("Error importing purchase order {$loggedOrderNumber}: " . $e->getMessage());
            }
        }
    }

    private function createPurchaseOrder(array $orderData): void
    {
        $orderNumber = $orderData['order_number'] ?? null;
        $header = $orderData['header'];
        $items = $orderData['items'];

        $orderDate = $this->parseDate($header['order_date'] ?? null);

        $vendor = null;
        if (!empty($header['supplier_name'])) {
            $vendor = Vendor::where('name', 'like', '%' . trim((string) $header['supplier_name']) . '%')->first();
        }

        $currency = trim((string) ($header['transaction_currency'] ?? request()->activeBusiness->currency));
        if ($currency === '') {
            $currency = request()->activeBusiness->currency;
        }

        $exchangeRate = 1.0;
        if ($currency !== '') {
            $currencyModel = Currency::where('name', 'like', '%' . $currency . '%')->first();
            if ($currencyModel && $currencyModel->exchange_rate > 0) {
                $exchangeRate = (float) $currencyModel->exchange_rate;
            }
        }

        $inventoryAccount = get_account('Inventory');
        $subTotal = 0.0;
        $totalTax = 0.0;
        $itemsData = [];

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
                throw new \RuntimeException('Unable to resolve an account for imported purchase order item "' . ($productName !== '' ? $productName : 'N/A') . '".');
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
                'product_id' => $isInventory === 1 ? ($product->id ?? null) : null,
                'product_name' => $product->name ?? ($productName !== '' ? $productName : 'Unknown Product'),
                'description' => trim((string) ($itemData['description'] ?? '')),
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

        $purchaseOrder = new PurchaseOrder();
        $purchaseOrder->vendor_id = $vendor->id ?? null;
        $purchaseOrder->title = trim((string) ($header['title'] ?? get_business_option('purchase_order_title', 'Purchase Order')));

        if ($orderNumber === null || $orderNumber === '') {
            $purchaseOrder->order_number = get_business_option('purchase_order_number');
            BusinessSetting::where('name', 'purchase_order_number')->increment('value');
        } else {
            $purchaseOrder->order_number = $orderNumber;
        }

        $purchaseOrder->order_date = $orderDate;
        $purchaseOrder->sub_total = $subTotal / $exchangeRate;
        $purchaseOrder->grand_total = $grandTotal / $exchangeRate;
        $purchaseOrder->converted_total = $grandTotal;
        $purchaseOrder->exchange_rate = $exchangeRate;
        $purchaseOrder->currency = $currency;
        $purchaseOrder->discount = $discountAmount / $exchangeRate;
        $purchaseOrder->discount_type = $discountType;
        $purchaseOrder->discount_value = $discountValue;
        $purchaseOrder->template_type = 0;
        $purchaseOrder->template = 'default';
        $purchaseOrder->note = trim((string) ($header['note'] ?? ''));
        $purchaseOrder->footer = trim((string) ($header['footer'] ?? ''));
        $purchaseOrder->created_by = auth()->id();
        $purchaseOrder->short_code = rand(100000, 9999999) . uniqid();
        $purchaseOrder->status = 0;
        $purchaseOrder->save();

        foreach ($itemsData as $item) {
            $purchaseOrderItem = new PurchaseOrderItem([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'sub_total' => $item['sub_total'],
                'account_id' => $item['account_id'],
            ]);
            $purchaseOrder->items()->save($purchaseOrderItem);

            foreach ($item['item_taxes'] as $itemTax) {
                $purchaseOrderItem->taxes()->save(new PurchaseOrderItemTax([
                    'purchase_order_id' => $purchaseOrder->id,
                    'tax_id' => $itemTax['tax_id'],
                    'name' => $itemTax['name'],
                    'amount' => $itemTax['amount'],
                ]));
            }
        }
    }

    /**
     * Normalize order number; empty values return null.
     */
    private function normalizeOrderNumber($orderNumber): ?string
    {
        if ($orderNumber === null) {
            return null;
        }

        $normalized = trim((string) $orderNumber);
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
            }
        }

        if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $rawDate) === 1) {
            try {
                return Carbon::createFromFormat('d-m-Y', $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        foreach (['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'n/j/Y', 'n-j-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
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
