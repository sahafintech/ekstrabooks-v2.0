<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\Approvals;
use App\Models\Currency;
use App\Models\HospitalPurchase as Purchase;
use App\Models\HospitalPurchaseItem as PurchaseItem;
use App\Models\HospitalPurchaseItemTax as PurchaseItemTax;
use App\Models\PendingTransaction;
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

class HospitalPurchaseImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private array $mappings;

    public function __construct(array $mappings = [])
    {
        $this->mappings = $mappings;
    }

    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $this->ensureDefaultAccounts();

        $groupedPurchases = [];
        $autoGroupCounter = 0;

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());
            $billNumber = $this->normalizeBillNumber($data['bill_number'] ?? null);
            $itemName = trim((string) ($data['product_name'] ?? ''));

            if ($billNumber === null && $itemName === '') {
                continue;
            }

            $groupKey = $billNumber !== null
                ? 'bill::' . strtolower($billNumber)
                : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedPurchases[$groupKey])) {
                $groupedPurchases[$groupKey] = [
                    'bill_number' => $billNumber,
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedPurchases[$groupKey]['items'][] = $data;
        }

        foreach ($groupedPurchases as $groupKey => $purchaseData) {
            DB::beginTransaction();

            try {
                $this->createPurchase($purchaseData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                $loggedBillNumber = $purchaseData['bill_number'] ?? $groupKey;
                \Log::error("Error importing hospital purchase {$loggedBillNumber}: " . $e->getMessage());
            }
        }
    }

    private function createPurchase(array $purchaseData): void
    {
        $billNumber = $purchaseData['bill_number'] ?? null;
        $header = $purchaseData['header'];
        $items = $purchaseData['items'];

        $purchaseDateRaw = $header['invoice_date'] ?? $header['purchase_date'] ?? null;
        if ($purchaseDateRaw === null || trim((string) $purchaseDateRaw) === '') {
            throw new \RuntimeException('Purchase date is required for imported hospital purchases.');
        }

        $dueDateRaw = $header['due_date'] ?? null;
        if ($dueDateRaw === null || trim((string) $dueDateRaw) === '') {
            throw new \RuntimeException('Due date is required for imported hospital purchases.');
        }

        $purchaseDate = $this->parseDate($purchaseDateRaw);
        $dueDate = $this->parseDate($dueDateRaw);

        if (Carbon::parse($dueDate)->lt(Carbon::parse($purchaseDate))) {
            throw new \RuntimeException('Due date must be on or after the purchase date.');
        }

        $vendor = null;
        if (!empty($header['supplier_name'])) {
            $supplierName = trim((string) $header['supplier_name']);
            $vendor = Vendor::where('name', 'like', '%' . $supplierName . '%')->first();
        }

        $currencyCode = trim((string) ($header['transaction_currency'] ?? $header['currency'] ?? request()->activeBusiness->currency));
        if ($currencyCode === '') {
            $currencyCode = request()->activeBusiness->currency;
        }

        $currency = Currency::where('name', $currencyCode)->first();
        $exchangeRate = $this->resolveExchangeRate($header['exchange_rate'] ?? null, $currency);

        $subTotal = 0.0;
        $totalTax = 0.0;
        $itemsData = [];

        foreach ($items as $itemData) {
            $itemName = trim((string) ($itemData['product_name'] ?? ''));
            if ($itemName === '') {
                throw new \RuntimeException('Item name is required for each imported hospital purchase line.');
            }

            $accountName = trim((string) ($itemData['expense_account'] ?? $itemData['account_name'] ?? ''));
            if ($accountName === '') {
                throw new \RuntimeException('Expense account is required for item "' . $itemName . '".');
            }

            $account = Account::where('business_id', request()->activeBusiness->id)
                ->whereIn('account_type', Purchase::ALLOWED_ACCOUNT_TYPES)
                ->where('account_name', 'like', '%' . $accountName . '%')
                ->first();

            if (!$account) {
                throw new \RuntimeException('Expense account "' . $accountName . '" was not found or is not allowed for hospital purchases.');
            }

            $quantity = (float) ($itemData['quantity'] ?? 0);
            $unitCost = (float) ($itemData['unit_cost'] ?? 0);

            if ($quantity <= 0) {
                throw new \RuntimeException('Quantity must be greater than 0 for item "' . $itemName . '".');
            }

            if ($unitCost < 0) {
                throw new \RuntimeException('Unit cost must be non-negative for item "' . $itemName . '".');
            }

            $lineTotal = $quantity * $unitCost;
            $subTotal += $lineTotal;

            $itemTaxes = [];
            $itemTaxTotal = 0.0;

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
                'product_name' => $itemName,
                'description' => trim((string) ($itemData['description'] ?? '')),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'sub_total' => $lineTotal,
                'account_id' => $account->id,
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
        $purchase->title = trim((string) ($header['title'] ?? $this->getHospitalBusinessOption('title', 'Hospital Purchase')));
        $purchase->bill_no = $billNumber ?: $this->nextHospitalPurchaseNumber();
        $purchase->po_so_number = trim((string) ($header['order_number'] ?? $header['po_so_number'] ?? ''));
        $purchase->purchase_date = $purchaseDate;
        $purchase->due_date = $dueDate;
        $purchase->sub_total = $subTotal / $exchangeRate;
        $purchase->grand_total = $grandTotal / $exchangeRate;
        $purchase->converted_total = $grandTotal;
        $purchase->exchange_rate = $exchangeRate;
        $purchase->currency = $currencyCode;
        $purchase->paid = 0;
        $purchase->discount = $discountAmount / $exchangeRate;
        $purchase->cash = 0;
        $purchase->discount_type = $discountType;
        $purchase->discount_value = $discountValue;
        $purchase->template_type = 0;
        $purchase->template = 'default';
        $purchase->note = trim((string) ($header['note'] ?? ''));
        $purchase->footer = trim((string) ($header['footer'] ?? $this->getHospitalBusinessOption('footer', get_business_option('purchase_footer', ''))));
        $purchase->withholding_tax = (float) ($header['withholding_tax'] ?? 0);
        $purchase->approval_status = 0;
        $purchase->checker_status = 0;
        $purchase->approved_by = null;
        $purchase->checked_by = null;
        $purchase->created_by = auth()->id();
        $purchase->benificiary = trim((string) ($header['beneficiary'] ?? $header['benificiary'] ?? ''));
        $purchase->short_code = rand(100000, 9999999) . uniqid();
        $purchase->status = 0;
        $purchase->save();

        $this->createApprovalRecords($purchase);
        $this->createCheckerRecords($purchase);

        foreach ($itemsData as $item) {
            $purchaseItem = new PurchaseItem([
                'purchase_id' => $purchase->id,
                'product_id' => null,
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
        }

        $this->createPendingTransactions($purchase);
    }

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
                $transaction->description = 'Hospital Purchase #' . $purchase->bill_no;
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
                $taxTransaction->description = 'Hospital Purchase Tax #' . $purchase->bill_no;
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
        $payableTransaction->description = 'Hospital Purchase Payable #' . $purchase->bill_no;
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
                $discountTransaction->description = 'Hospital Purchase Discount #' . $purchase->bill_no;
                $discountTransaction->ref_id = $purchase->id;
                $discountTransaction->ref_type = 'bill invoice';
                $discountTransaction->vendor_id = $purchase->vendor_id;
                $discountTransaction->save();
            }
        }
    }

    private function createApprovalRecords(Purchase $purchase): void
    {
        $configuredUserIds = $this->getHospitalPurchaseUserIds('approval_users');
        if (empty($configuredUserIds)) {
            return;
        }

        $validUserIds = User::whereIn('id', $configuredUserIds)->pluck('id')->toArray();
        if (empty($validUserIds)) {
            return;
        }

        foreach ($validUserIds as $userId) {
            $exists = Approvals::where('ref_id', $purchase->id)
                ->where('ref_name', Purchase::APPROVAL_REF_NAME)
                ->where('checker_type', 'approval')
                ->where('action_user', $userId)
                ->exists();

            if (!$exists) {
                Approvals::create([
                    'ref_id' => $purchase->id,
                    'ref_name' => Purchase::APPROVAL_REF_NAME,
                    'checker_type' => 'approval',
                    'action_user' => $userId,
                    'status' => 0,
                ]);
            }
        }
    }

    private function createCheckerRecords(Purchase $purchase): void
    {
        $configuredUserIds = $this->getHospitalPurchaseUserIds('checker_users');
        if (empty($configuredUserIds)) {
            return;
        }

        $validUserIds = User::whereIn('id', $configuredUserIds)->pluck('id')->toArray();
        if (empty($validUserIds)) {
            return;
        }

        foreach ($validUserIds as $userId) {
            $exists = Approvals::where('ref_id', $purchase->id)
                ->where('ref_name', Purchase::APPROVAL_REF_NAME)
                ->where('checker_type', 'checker')
                ->where('action_user', $userId)
                ->exists();

            if (!$exists) {
                Approvals::create([
                    'ref_id' => $purchase->id,
                    'ref_name' => Purchase::APPROVAL_REF_NAME,
                    'checker_type' => 'checker',
                    'action_user' => $userId,
                    'status' => 0,
                ]);
            }
        }
    }

    private function getHospitalPurchaseUserIds(string $suffix): array
    {
        $userIds = json_decode($this->getHospitalBusinessOption($suffix, '[]'), true);

        return is_array($userIds) ? $userIds : [];
    }

    private function getHospitalBusinessOption(string $suffix, $default = '')
    {
        return get_business_option(
            'hospital_purchase_' . $suffix,
            get_business_option('purchase_' . $suffix, $default)
        );
    }

    private function nextHospitalPurchaseNumber(): string
    {
        $currentNumber = (int) $this->getHospitalBusinessOption('number', 1000);
        update_business_option(
            'hospital_purchase_number',
            (string) ($currentNumber + 1),
            request()->activeBusiness->id
        );

        return (string) $currentNumber;
    }

    private function mapRowData(array $row): array
    {
        $mappedData = [];

        foreach ($row as $header => $value) {
            if ($this->mappings !== []) {
                $normalizedHeader = $this->normalizeHeader((string) $header);

                foreach ($this->mappings as $excelHeader => $systemField) {
                    if ($this->normalizeHeader((string) $excelHeader) !== $normalizedHeader) {
                        continue;
                    }

                    $normalizedField = $this->normalizeMappedField((string) $systemField);
                    if ($normalizedField !== null && $normalizedField !== 'skip') {
                        $mappedData[$normalizedField] = $value;
                    }

                    continue 2;
                }

                continue;
            }

            $normalizedField = $this->normalizeMappedField((string) $header);
            if ($normalizedField !== null && $normalizedField !== 'skip') {
                $mappedData[$normalizedField] = $value;
            }
        }

        return $mappedData;
    }

    private function normalizeHeader(string $header): string
    {
        return trim((string) preg_replace('/_+/', '_', strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', trim($header)))));
    }

    private function normalizeMappedField(string $field): ?string
    {
        $normalized = $this->normalizeHeader($field);

        $aliases = [
            'bill_no' => 'bill_number',
            'purchase_date' => 'invoice_date',
            'vendor_name' => 'supplier_name',
            'currency' => 'transaction_currency',
            'po_so_number' => 'order_number',
            'account_name' => 'expense_account',
            'benificiary' => 'beneficiary',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    private function normalizeBillNumber($billNumber): ?string
    {
        if ($billNumber === null) {
            return null;
        }

        $normalized = trim((string) $billNumber);
        return $normalized === '' ? null : $normalized;
    }

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

    private function resolveExchangeRate($value, ?Currency $currency): float
    {
        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value > 0) {
            return (float) $value;
        }

        if ($currency && is_numeric($currency->getRawOriginal('exchange_rate')) && (float) $currency->getRawOriginal('exchange_rate') > 0) {
            return (float) $currency->getRawOriginal('exchange_rate');
        }

        return 1.0;
    }

    private function ensureDefaultAccounts(): void
    {
        $defaultAccounts = [
            ['name' => 'Accounts Payable', 'code' => '2100', 'type' => 'Current Liability', 'dr_cr' => 'cr'],
            ['name' => 'Purchase Tax Payable', 'code' => '2201', 'type' => 'Current Liability', 'dr_cr' => 'dr'],
            ['name' => 'Purchase Discount Allowed', 'code' => '6003', 'type' => 'Cost Of Sale', 'dr_cr' => 'cr'],
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
