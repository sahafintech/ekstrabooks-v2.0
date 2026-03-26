<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\ReceiptItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CashInvoiceImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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

        $groupedReceipts = [];
        $autoGroupCounter = 0;

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());
            $receiptNumber = $this->normalizeReceiptNumber($data['receipt_number'] ?? null);
            $productName = trim((string) ($data['product_name'] ?? ''));

            if ($receiptNumber === null && $productName === '') {
                continue;
            }

            $groupKey = $receiptNumber !== null
                ? 'receipt::' . strtolower($receiptNumber)
                : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedReceipts[$groupKey])) {
                $groupedReceipts[$groupKey] = [
                    'receipt_number' => $receiptNumber,
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedReceipts[$groupKey]['items'][] = $data;
        }

        foreach ($groupedReceipts as $groupKey => $receiptData) {
            DB::beginTransaction();

            try {
                $this->createReceipt($receiptData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                $loggedReceiptNumber = $receiptData['receipt_number'] ?? $groupKey;
                \Log::error("Error importing cash invoice {$loggedReceiptNumber}: " . $e->getMessage());
            }
        }
    }

    private function createReceipt(array $receiptData): void
    {
        $receiptNumber = $receiptData['receipt_number'] ?? null;
        $header = $receiptData['header'];
        $items = $receiptData['items'];

        $receiptDate = $this->parseDate($header['receipt_date'] ?? $header['invoice_date'] ?? null);

        $customer = null;
        if (!empty($header['customer_name'])) {
            $customer = Customer::where('name', 'like', '%' . trim((string) $header['customer_name']) . '%')->first();
        }

        $project = null;
        if (!empty($header['project_name'])) {
            $project = Project::where('project_name', 'like', '%' . trim((string) $header['project_name']) . '%')->first();
        }

        $currencyCode = trim((string) ($header['currency'] ?? request()->activeBusiness->currency));
        if ($currencyCode === '') {
            $currencyCode = request()->activeBusiness->currency;
        }

        $currency = Currency::where('name', $currencyCode)->first();
        $exchangeRate = $this->resolveExchangeRate($header['exchange_rate'] ?? null, $currency);

        $paymentAccountName = trim((string) ($header['payment_account'] ?? ''));
        $paymentAccount = $this->findPaymentAccount($paymentAccountName);
        if (!$paymentAccount) {
            throw new \RuntimeException('Payment account is required for imported cash invoices.');
        }

        $paymentMethodName = trim((string) ($header['payment_method'] ?? ''));
        $paymentMethod = null;
        if ($paymentMethodName !== '') {
            $paymentMethod = TransactionMethod::where('name', 'like', '%' . $paymentMethodName . '%')->first();
        }

        $subTotal = 0.0;
        $totalTax = 0.0;
        $itemsData = [];

        foreach ($items as $itemData) {
            $productName = trim((string) ($itemData['product_name'] ?? ''));
            if ($productName === '') {
                throw new \RuntimeException('Product name is required for each imported invoice item.');
            }

            $product = Product::where('name', 'like', '%' . $productName . '%')->first();
            if (!$product) {
                throw new \RuntimeException('Product "' . $productName . '" was not found.');
            }

            $quantity = (float) ($itemData['quantity'] ?? 0);
            $unitCost = (float) ($itemData['unit_cost'] ?? 0);

            if ($quantity <= 0) {
                throw new \RuntimeException('Quantity must be greater than 0 for product "' . $productName . '".');
            }

            if ($unitCost < 0) {
                throw new \RuntimeException('Unit cost must be non-negative for product "' . $productName . '".');
            }

            if (
                $product->type === 'product' &&
                (int) $product->stock_management === 1 &&
                (float) $product->stock < $quantity
            ) {
                throw new \RuntimeException('Insufficient stock for product "' . $product->name . '".');
            }

            if ((int) $product->allow_for_selling === 1 && empty($product->income_account_id)) {
                throw new \RuntimeException('Product "' . $product->name . '" is missing an income account.');
            }

            if (
                (int) $product->stock_management === 1 &&
                (int) $product->allow_for_purchasing === 1 &&
                empty($product->expense_account_id)
            ) {
                throw new \RuntimeException('Product "' . $product->name . '" is missing an expense account.');
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

                if (!$tax->account_id) {
                    throw new \RuntimeException('Tax "' . $tax->name . '" is missing an account.');
                }

                $taxAmount = ($lineTotal / 100) * $tax->rate;
                $itemTaxTotal += $taxAmount;

                $itemTaxes[] = [
                    'tax_id' => $tax->id,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => $taxAmount,
                    'account_id' => $tax->account_id,
                    'rate' => $tax->rate,
                ];
            }

            $totalTax += $itemTaxTotal;

            $itemsData[] = [
                'product' => $product,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'description' => trim((string) ($itemData['description'] ?? ($product->descriptions ?? ''))),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'sub_total' => $lineTotal,
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

        $receipt = new Receipt();
        $receipt->customer_id = $customer->id ?? null;
        $receipt->title = trim((string) ($header['title'] ?? get_business_option('receipt_title', 'Cash Invoice')));

        if ($receiptNumber === null || $receiptNumber === '') {
            $receipt->receipt_number = get_business_option('receipt_number');
            BusinessSetting::where('name', 'receipt_number')->increment('value');
        } else {
            $receipt->receipt_number = $receiptNumber;
        }

        $receipt->order_number = trim((string) ($header['order_number'] ?? ''));
        $receipt->receipt_date = $receiptDate;
        $receipt->sub_total = $subTotal / $exchangeRate;
        $receipt->grand_total = $grandTotal / $exchangeRate;
        $receipt->currency = $currencyCode;
        $receipt->converted_total = $grandTotal;
        $receipt->exchange_rate = $exchangeRate;
        $receipt->discount = $discountAmount / $exchangeRate;
        $receipt->discount_type = $discountType;
        $receipt->discount_value = $discountValue;
        $receipt->note = trim((string) ($header['note'] ?? ''));
        $receipt->footer = trim((string) ($header['footer'] ?? get_business_option('receipt_footer')));
        $receipt->project_id = $project->id ?? null;
        $receipt->user_id = auth()->id();
        $receipt->business_id = request()->activeBusiness->id;
        $receipt->short_code = rand(100000, 9999999) . uniqid();
        $receipt->save();

        $currentTime = Carbon::now();
        $transDate = Carbon::parse($receiptDate)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');
        $shortTransDate = Carbon::parse($receiptDate)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i');
        $projectId = $project->id ?? null;

        foreach ($itemsData as $item) {
            $receiptItem = $receipt->items()->save(new ReceiptItem([
                'receipt_id' => $receipt->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'sub_total' => $item['sub_total'],
                'user_id' => auth()->id(),
                'business_id' => request()->activeBusiness->id,
            ]));

            $product = $item['product'];

            if ((int) $product->allow_for_selling === 1) {
                $transaction = new Transaction();
                $transaction->trans_date = $transDate;
                $transaction->account_id = $product->income_account_id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $receiptItem->sub_total / $receipt->exchange_rate
                );
                $transaction->transaction_currency = $currencyCode;
                $transaction->currency_rate = $receipt->exchange_rate;
                $transaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        $receiptItem->sub_total / $receipt->exchange_rate
                    )
                );
                $transaction->description = _lang('Cash Invoice Income') . ' #' . $receipt->receipt_number;
                $transaction->reference = trim((string) ($header['payment_reference'] ?? ''));
                $transaction->ref_id = $receipt->id;
                $transaction->ref_type = 'receipt';
                $transaction->customer_id = $receipt->customer_id;
                $transaction->project_id = $projectId;
                $transaction->save();
            }

            if ((int) $product->stock_management === 1 && (int) $product->allow_for_purchasing === 1) {
                $inventoryTransaction = new Transaction();
                $inventoryTransaction->trans_date = $transDate;
                $inventoryTransaction->account_id = get_account('Inventory')->id;
                $inventoryTransaction->dr_cr = 'cr';
                $inventoryTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $product->purchase_cost * $receiptItem->quantity
                );
                $inventoryTransaction->transaction_currency = $currencyCode;
                $inventoryTransaction->currency_rate = $receipt->exchange_rate;
                $inventoryTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        $product->purchase_cost * $receiptItem->quantity
                    )
                );
                $inventoryTransaction->description = $receiptItem->product_name . ' Sales #' . $receipt->receipt_number;
                $inventoryTransaction->ref_id = $receipt->id;
                $inventoryTransaction->ref_type = 'receipt';
                $inventoryTransaction->customer_id = $receipt->customer_id;
                $inventoryTransaction->project_id = $projectId;
                $inventoryTransaction->save();

                $expenseTransaction = new Transaction();
                $expenseTransaction->trans_date = $shortTransDate;
                $expenseTransaction->account_id = $product->expense_account_id;
                $expenseTransaction->dr_cr = 'dr';
                $expenseTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $product->purchase_cost * $receiptItem->quantity
                );
                $expenseTransaction->transaction_currency = $currencyCode;
                $expenseTransaction->currency_rate = $receipt->exchange_rate;
                $expenseTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        $product->purchase_cost * $receiptItem->quantity
                    )
                );
                $expenseTransaction->ref_type = 'receipt';
                $expenseTransaction->customer_id = $receipt->customer_id;
                $expenseTransaction->ref_id = $receipt->id;
                $expenseTransaction->description = 'Cash Invoice #' . $receipt->receipt_number;
                $expenseTransaction->project_id = $projectId;
                $expenseTransaction->save();
            }

            foreach ($item['item_taxes'] as $itemTax) {
                $receiptItem->taxes()->save(new ReceiptItemTax([
                    'receipt_id' => $receipt->id,
                    'tax_id' => $itemTax['tax_id'],
                    'name' => $itemTax['name'],
                    'amount' => $itemTax['amount'],
                ]));

                $taxTransaction = new Transaction();
                $taxTransaction->trans_date = $transDate;
                $taxTransaction->account_id = $itemTax['account_id'];
                $taxTransaction->dr_cr = 'cr';
                $taxTransaction->transaction_currency = $currencyCode;
                $taxTransaction->currency_rate = $receipt->exchange_rate;
                $taxTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $itemTax['rate']
                    )
                );
                $taxTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    (($receiptItem->sub_total / $receipt->exchange_rate) / 100) * $itemTax['rate']
                );
                $taxTransaction->description = _lang('Cash Invoice Tax') . ' #' . $receipt->receipt_number;
                $taxTransaction->ref_id = $receipt->id;
                $taxTransaction->ref_type = 'receipt tax';
                $taxTransaction->tax_id = $itemTax['tax_id'];
                $taxTransaction->customer_id = $receipt->customer_id;
                $taxTransaction->project_id = $projectId;
                $taxTransaction->save();
            }

            if ($product->type === 'product' && (int) $product->stock_management === 1) {
                if ((float) $product->stock < (float) $receiptItem->quantity) {
                    throw new \RuntimeException($product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = (float) $product->stock - (float) $receiptItem->quantity;
                $product->save();
            }
        }

        $paymentTransaction = new Transaction();
        $paymentTransaction->customer_id = $receipt->customer_id;
        $paymentTransaction->trans_date = $transDate;
        $paymentTransaction->account_id = $paymentAccount->id;
        $paymentTransaction->dr_cr = 'dr';
        $paymentTransaction->transaction_amount = convert_currency(
            request()->activeBusiness->currency,
            $currencyCode,
            $grandTotal / $receipt->exchange_rate
        );
        $paymentTransaction->transaction_currency = $currencyCode;
        $paymentTransaction->currency_rate = $receipt->exchange_rate;
        $paymentTransaction->base_currency_amount = convert_currency(
            $currencyCode,
            request()->activeBusiness->currency,
            convert_currency(
                request()->activeBusiness->currency,
                $currencyCode,
                $grandTotal / $receipt->exchange_rate
            )
        );
        $paymentTransaction->description = 'Cash Invoice Payment #' . $receipt->receipt_number;
        $paymentTransaction->transaction_method = $paymentMethod->name ?? $paymentMethodName;
        $paymentTransaction->reference = trim((string) ($header['payment_reference'] ?? ''));
        $paymentTransaction->ref_id = $receipt->id;
        $paymentTransaction->ref_type = 'receipt';
        $paymentTransaction->customer_id = $receipt->customer_id;
        $paymentTransaction->project_id = $projectId;
        $paymentTransaction->save();

        if ($discountAmount > 0) {
            $discountTransaction = new Transaction();
            $discountTransaction->trans_date = $transDate;
            $discountTransaction->account_id = get_account('Sales Discount Allowed')->id;
            $discountTransaction->dr_cr = 'dr';
            $discountTransaction->transaction_amount = convert_currency(
                request()->activeBusiness->currency,
                $currencyCode,
                $discountAmount / $receipt->exchange_rate
            );
            $discountTransaction->transaction_currency = $currencyCode;
            $discountTransaction->currency_rate = $receipt->exchange_rate;
            $discountTransaction->base_currency_amount = convert_currency(
                $currencyCode,
                request()->activeBusiness->currency,
                convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $discountAmount / $receipt->exchange_rate
                )
            );
            $discountTransaction->description = _lang('Cash Invoice Discount') . ' #' . $receipt->receipt_number;
            $discountTransaction->ref_id = $receipt->id;
            $discountTransaction->ref_type = 'receipt';
            $discountTransaction->customer_id = $receipt->customer_id;
            $discountTransaction->project_id = $projectId;
            $discountTransaction->save();
        }
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
            'invoice_number' => 'receipt_number',
            'receipt_no' => 'receipt_number',
            'invoice_no' => 'receipt_number',
            'invoice_date' => 'receipt_date',
            'transaction_currency' => 'currency',
            'debit_account' => 'payment_account',
            'account' => 'payment_account',
            'payment_account_name' => 'payment_account',
            'payment_reference_number' => 'payment_reference',
            'reference' => 'payment_reference',
            'project' => 'project_name',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    private function normalizeReceiptNumber($receiptNumber): ?string
    {
        if ($receiptNumber === null) {
            return null;
        }

        $normalized = trim((string) $receiptNumber);
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

    private function findPaymentAccount(string $accountName): ?Account
    {
        if ($accountName === '') {
            return null;
        }

        return Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash')
                ->orWhere('account_type', 'bank')
                ->orWhere('account_type', 'cash');
        })
            ->where('account_name', 'like', '%' . $accountName . '%')
            ->first();
    }

    private function ensureDefaultAccounts(): void
    {
        $defaultAccounts = [
            ['name' => 'Accounts Receivable', 'code' => '1100', 'type' => 'Other Current Asset', 'dr_cr' => 'dr'],
            ['name' => 'Sales Tax Payable', 'code' => '2200', 'type' => 'Current Liability', 'dr_cr' => 'cr'],
            ['name' => 'Sales Discount Allowed', 'code' => '4009', 'type' => 'Other Income', 'dr_cr' => 'cr'],
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
