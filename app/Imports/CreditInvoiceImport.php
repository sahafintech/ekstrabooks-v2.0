<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\Product;
use App\Models\Project;
use App\Models\Tax;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CreditInvoiceImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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

        $groupedInvoices = [];
        $autoGroupCounter = 0;

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());
            $invoiceNumber = $this->normalizeInvoiceNumber($data['invoice_number'] ?? null);
            $productName = trim((string) ($data['product_name'] ?? ''));

            if ($invoiceNumber === null && $productName === '') {
                continue;
            }

            $groupKey = $invoiceNumber !== null
                ? 'invoice::' . strtolower($invoiceNumber)
                : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedInvoices[$groupKey])) {
                $groupedInvoices[$groupKey] = [
                    'invoice_number' => $invoiceNumber,
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedInvoices[$groupKey]['items'][] = $data;
        }

        foreach ($groupedInvoices as $groupKey => $invoiceData) {
            DB::beginTransaction();

            try {
                $this->createInvoice($invoiceData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                $loggedInvoiceNumber = $invoiceData['invoice_number'] ?? $groupKey;
                \Log::error("Error importing credit invoice {$loggedInvoiceNumber}: " . $e->getMessage());
            }
        }
    }

    private function createInvoice(array $invoiceData): void
    {
        $invoiceNumber = $invoiceData['invoice_number'] ?? null;
        $header = $invoiceData['header'];
        $items = $invoiceData['items'];

        $invoiceDateRaw = $header['invoice_date'] ?? null;
        if ($invoiceDateRaw === null || trim((string) $invoiceDateRaw) === '') {
            throw new \RuntimeException('Invoice date is required for imported invoices.');
        }

        $dueDateRaw = $header['due_date'] ?? null;
        if ($dueDateRaw === null || trim((string) $dueDateRaw) === '') {
            throw new \RuntimeException('Due date is required for imported invoices.');
        }

        $invoiceDate = $this->parseDate($invoiceDateRaw);
        $dueDate = $this->parseDate($dueDateRaw);

        if (Carbon::parse($dueDate)->lt(Carbon::parse($invoiceDate))) {
            throw new \RuntimeException('Due date must be on or after the invoice date.');
        }

        $customerName = trim((string) ($header['customer_name'] ?? ''));
        if ($customerName === '') {
            throw new \RuntimeException('Customer name is required for imported invoices.');
        }

        $customer = Customer::where('name', 'like', '%' . $customerName . '%')->first();
        if (!$customer) {
            throw new \RuntimeException('Customer "' . $customerName . '" was not found.');
        }

        $client = null;
        if (!empty($header['client_name'])) {
            $clientName = trim((string) $header['client_name']);
            $client = Customer::where('name', 'like', '%' . $clientName . '%')->first();

            if (!$client) {
                throw new \RuntimeException('Client "' . $clientName . '" was not found.');
            }
        }

        $project = null;
        if (!empty($header['project_name'])) {
            $projectName = trim((string) $header['project_name']);
            $project = Project::where('project_name', 'like', '%' . $projectName . '%')->first();

            if (!$project) {
                throw new \RuntimeException('Project "' . $projectName . '" was not found.');
            }
        }

        $currencyCode = trim((string) ($header['currency'] ?? request()->activeBusiness->currency));
        if ($currencyCode === '') {
            $currencyCode = request()->activeBusiness->currency;
        }

        $currency = Currency::where('name', $currencyCode)->first();
        $exchangeRate = $this->resolveExchangeRate($header['exchange_rate'] ?? null, $currency);

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

            if ((int) $product->allow_for_purchasing === 1 && empty($product->expense_account_id)) {
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

        $grandTotalTransaction = ($subTotal + $totalTax) - $discountAmount;
        $grandTotalBase = $grandTotalTransaction / $exchangeRate;
        $projectId = $project->id ?? null;

        $invoice = new Invoice();
        $invoice->customer_id = $customer->id;
        $invoice->client_id = $client->id ?? null;
        $invoice->title = trim((string) ($header['title'] ?? get_business_option('invoice_title', 'Invoice')));

        if ($invoiceNumber === null || $invoiceNumber === '') {
            $invoice->invoice_number = get_business_option('invoice_number');
            BusinessSetting::where('name', 'invoice_number')->increment('value');
        } else {
            $invoice->invoice_number = $invoiceNumber;
        }

        $invoice->order_number = trim((string) ($header['order_number'] ?? ''));
        $invoice->invoice_date = $invoiceDate;
        $invoice->due_date = $dueDate;
        $invoice->sub_total = $subTotal / $exchangeRate;
        $invoice->grand_total = $grandTotalBase;
        $invoice->currency = $currencyCode;
        $invoice->converted_total = $grandTotalTransaction;
        $invoice->exchange_rate = $exchangeRate;
        $invoice->paid = 0;
        $invoice->discount = $discountAmount / $exchangeRate;
        $invoice->discount_type = $discountType;
        $invoice->discount_value = $discountValue;
        $invoice->template_type = 0;
        $invoice->template = 'default';
        $invoice->project_id = $projectId;
        $invoice->note = trim((string) ($header['note'] ?? ''));
        $invoice->footer = trim((string) ($header['footer'] ?? get_business_option('invoice_footer')));
        $invoice->short_code = rand(100000, 9999999) . uniqid();
        $invoice->save();

        $currentTime = Carbon::now();
        $transDate = Carbon::parse($invoiceDate)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');
        $shortTransDate = Carbon::parse($invoiceDate)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i');

        foreach ($itemsData as $item) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'sub_total' => $item['sub_total'],
            ]));

            $product = $item['product'];

            if ((int) $product->allow_for_selling === 1) {
                $transaction = new Transaction();
                $transaction->trans_date = $transDate;
                $transaction->account_id = $product->income_account_id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $currencyCode;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        $invoiceItem->sub_total / $invoice->exchange_rate
                    )
                );
                $transaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $invoiceItem->sub_total / $invoice->exchange_rate
                );
                $transaction->description = _lang('Credit Invoice Income') . ' #' . $invoice->invoice_number;
                $transaction->ref_id = $invoice->id;
                $transaction->ref_type = 'invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->project_id = $projectId;
                $transaction->save();
            }

            if ((int) $product->stock_management === 1) {
                $inventoryTransaction = new Transaction();
                $inventoryTransaction->trans_date = $transDate;
                $inventoryTransaction->account_id = get_account('Inventory')->id;
                $inventoryTransaction->dr_cr = 'cr';
                $inventoryTransaction->transaction_currency = $currencyCode;
                $inventoryTransaction->currency_rate = $invoice->exchange_rate;
                $inventoryTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        $product->purchase_cost * $invoiceItem->quantity
                    )
                );
                $inventoryTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $product->purchase_cost * $invoiceItem->quantity
                );
                $inventoryTransaction->description = $invoiceItem->product_name . ' Sales #' . $invoice->invoice_number;
                $inventoryTransaction->ref_id = $invoice->id;
                $inventoryTransaction->ref_type = 'invoice';
                $inventoryTransaction->customer_id = $invoice->customer_id;
                $inventoryTransaction->project_id = $projectId;
                $inventoryTransaction->save();
            }

            if ((int) $product->allow_for_purchasing === 1) {
                $expenseTransaction = new Transaction();
                $expenseTransaction->trans_date = $shortTransDate;
                $expenseTransaction->account_id = $product->expense_account_id;
                $expenseTransaction->dr_cr = 'dr';
                $expenseTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $product->purchase_cost * $invoiceItem->quantity
                );
                $expenseTransaction->transaction_currency = $currencyCode;
                $expenseTransaction->currency_rate = $invoice->exchange_rate;
                $expenseTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        $product->purchase_cost * $invoiceItem->quantity
                    )
                );
                $expenseTransaction->ref_type = 'invoice';
                $expenseTransaction->customer_id = $invoice->customer_id;
                $expenseTransaction->ref_id = $invoice->id;
                $expenseTransaction->description = 'Credit Invoice #' . $invoice->invoice_number;
                $expenseTransaction->project_id = $projectId;
                $expenseTransaction->save();
            }

            foreach ($item['item_taxes'] as $itemTax) {
                $invoiceItem->taxes()->save(new InvoiceItemTax([
                    'invoice_id' => $invoice->id,
                    'tax_id' => $itemTax['tax_id'],
                    'name' => $itemTax['name'],
                    'amount' => $itemTax['amount'],
                ]));

                $taxTransaction = new Transaction();
                $taxTransaction->trans_date = $transDate;
                $taxTransaction->account_id = $itemTax['account_id'];
                $taxTransaction->dr_cr = 'cr';
                $taxTransaction->transaction_currency = $currencyCode;
                $taxTransaction->currency_rate = $invoice->exchange_rate;
                $taxTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        (($invoiceItem->sub_total / $invoice->exchange_rate) / 100) * $itemTax['rate']
                    )
                );
                $taxTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    (($invoiceItem->sub_total / $invoice->exchange_rate) / 100) * $itemTax['rate']
                );
                $taxTransaction->description = _lang('Credit Invoice Tax') . ' #' . $invoice->invoice_number;
                $taxTransaction->ref_id = $invoice->id;
                $taxTransaction->ref_type = 'invoice tax';
                $taxTransaction->tax_id = $itemTax['tax_id'];
                $taxTransaction->project_id = $projectId;
                $taxTransaction->save();
            }

            if ($product->type === 'product' && (int) $product->stock_management === 1) {
                if ((float) $product->stock < (float) $invoiceItem->quantity) {
                    throw new \RuntimeException($product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = (float) $product->stock - (float) $invoiceItem->quantity;
                $product->save();
            }
        }

        $accountsReceivableTransaction = new Transaction();
        $accountsReceivableTransaction->trans_date = $transDate;
        $accountsReceivableTransaction->account_id = get_account('Accounts Receivable')->id;
        $accountsReceivableTransaction->dr_cr = 'dr';
        $accountsReceivableTransaction->transaction_amount = convert_currency(
            request()->activeBusiness->currency,
            $currencyCode,
            $grandTotalBase
        );
        $accountsReceivableTransaction->transaction_currency = $currencyCode;
        $accountsReceivableTransaction->currency_rate = $invoice->exchange_rate;
        $accountsReceivableTransaction->base_currency_amount = convert_currency(
            $currencyCode,
            request()->activeBusiness->currency,
            convert_currency(
                request()->activeBusiness->currency,
                $currencyCode,
                $grandTotalBase
            )
        );
        $accountsReceivableTransaction->ref_id = $invoice->id;
        $accountsReceivableTransaction->ref_type = 'invoice';
        $accountsReceivableTransaction->customer_id = $invoice->customer_id;
        $accountsReceivableTransaction->description = 'Credit Invoice #' . $invoice->invoice_number;
        $accountsReceivableTransaction->project_id = $projectId;
        $accountsReceivableTransaction->save();

        if ($discountAmount > 0) {
            $discountTransaction = new Transaction();
            $discountTransaction->trans_date = $transDate;
            $discountTransaction->account_id = get_account('Sales Discount Allowed')->id;
            $discountTransaction->dr_cr = 'dr';
            $discountTransaction->transaction_amount = convert_currency(
                request()->activeBusiness->currency,
                $currencyCode,
                $discountAmount / $invoice->exchange_rate
            );
            $discountTransaction->transaction_currency = $currencyCode;
            $discountTransaction->currency_rate = $invoice->exchange_rate;
            $discountTransaction->base_currency_amount = convert_currency(
                $currencyCode,
                request()->activeBusiness->currency,
                convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $discountAmount / $invoice->exchange_rate
                )
            );
            $discountTransaction->description = _lang('Credit Invoice Discount') . ' #' . $invoice->invoice_number;
            $discountTransaction->ref_id = $invoice->id;
            $discountTransaction->ref_type = 'invoice';
            $discountTransaction->customer_id = $invoice->customer_id;
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
            'invoice_no' => 'invoice_number',
            'customer' => 'customer_name',
            'provider_name' => 'customer_name',
            'client' => 'client_name',
            'project' => 'project_name',
            'transaction_currency' => 'currency',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    private function normalizeInvoiceNumber($invoiceNumber): ?string
    {
        if ($invoiceNumber === null) {
            return null;
        }

        $normalized = trim((string) $invoiceNumber);
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
            ['name' => 'Accounts Receivable', 'code' => '1100', 'type' => 'Other Current Asset', 'dr_cr' => 'dr'],
            ['name' => 'Sales Tax Payable', 'code' => '2200', 'type' => 'Current Liability', 'dr_cr' => 'cr'],
            ['name' => 'Sales Discount Allowed', 'code' => '4009', 'type' => 'Other Income', 'dr_cr' => 'dr'],
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
