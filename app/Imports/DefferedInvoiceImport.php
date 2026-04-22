<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\DefferedEarning;
use App\Models\InsuranceFamilySize;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DefferedInvoiceImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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
            $policyNumber = $this->normalizeInvoiceNumber($data['order_number'] ?? ($data['policy_number'] ?? null));
            $productName = trim((string) ($data['product_name'] ?? ''));

            if ($policyNumber === null && $productName === '') {
                continue;
            }

            $groupKey = $policyNumber !== null ? 'policy::' . strtolower($policyNumber) : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedInvoices[$groupKey])) {
                $groupedInvoices[$groupKey] = [
                    'policy_number' => $policyNumber,
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
                $loggedPolicyNumber = $invoiceData['policy_number'] ?? $groupKey;
                \Log::error("Error importing deffered invoice {$loggedPolicyNumber}: " . $e->getMessage());
            }
        }
    }

    private function createInvoice(array $invoiceData): void
    {
        $policyNumber = $invoiceData['policy_number'] ?? null;
        $header = $invoiceData['header'];
        $items = $invoiceData['items'];

        $invoiceDate = $this->requireParsedDate($header['invoice_date'] ?? null, 'Invoice date');
        $dueDate = $this->requireParsedDate($header['due_date'] ?? null, 'Due date');
        $defferedStart = $this->requireParsedDate($header['deffered_start'] ?? null, 'Policy start date');
        $defferedEnd = $this->requireParsedDate($header['deffered_end'] ?? null, 'Policy end date');

        if (Carbon::parse($dueDate)->lt(Carbon::parse($invoiceDate))) {
            throw new \RuntimeException('Due date must be on or after the invoice date.');
        }

        if (Carbon::parse($defferedEnd)->lt(Carbon::parse($defferedStart))) {
            throw new \RuntimeException('Policy end date must be on or after the policy start date.');
        }

        $invoiceCategory = $this->normalizeInvoiceCategory($header['invoice_category'] ?? null);
        if ($invoiceCategory === null) {
            throw new \RuntimeException('Invoice category is required and must be medical, gpa, or other.');
        }

        $customerName = trim((string) ($header['customer_name'] ?? ''));
        if ($customerName === '') {
            throw new \RuntimeException('Customer name is required for imported deffered invoices.');
        }

        $customer = Customer::where('name', 'like', '%' . $customerName . '%')->first();
        if (!$customer) {
            throw new \RuntimeException('Customer "' . $customerName . '" was not found.');
        }

        $currencyCode = trim((string) ($header['currency'] ?? request()->activeBusiness->currency));
        if ($currencyCode === '') {
            $currencyCode = request()->activeBusiness->currency;
        }

        $currency = Currency::where('name', $currencyCode)->first();
        if (!$currency && $currencyCode !== request()->activeBusiness->currency) {
            throw new \RuntimeException('Currency "' . $currencyCode . '" was not found.');
        }

        $exchangeRate = $this->resolveExchangeRate($header['exchange_rate'] ?? null, $currency);
        $subTotal = 0.0;
        $totalTax = 0.0;
        $itemsData = [];

        foreach ($items as $itemData) {
            $productName = trim((string) ($itemData['product_name'] ?? ''));
            if ($productName === '') {
                throw new \RuntimeException('Product name is required for each imported deffered invoice item.');
            }

            $product = Product::where('name', 'like', '%' . $productName . '%')->first();
            if (!$product) {
                throw new \RuntimeException('Product "' . $productName . '" was not found.');
            }

            $quantity = (float) ($itemData['quantity'] ?? 0);
            $unitCost = (float) ($itemData['unit_cost'] ?? 0);
            $sumInsured = $this->normalizeOptionalNumber($itemData['sum_insured'] ?? null);

            if ($quantity <= 0) {
                throw new \RuntimeException('Quantity must be greater than 0 for product "' . $productName . '".');
            }

            if ($unitCost < 0) {
                throw new \RuntimeException('Unit cost must be non-negative for product "' . $productName . '".');
            }

            if ($sumInsured !== null && $sumInsured < 0) {
                throw new \RuntimeException('Sum insured must be non-negative for product "' . $productName . '".');
            }

            if ($product->type === 'product' && (int) $product->stock_management === 1 && (float) $product->stock < $quantity) {
                throw new \RuntimeException('Insufficient stock for product "' . $product->name . '".');
            }

            if ((int) $product->allow_for_purchasing === 1 && empty($product->expense_account_id)) {
                throw new \RuntimeException('Product "' . $product->name . '" is missing an expense account.');
            }

            $familySize = null;
            if (!empty($itemData['family_size'])) {
                $familySizeValue = trim((string) $itemData['family_size']);
                $familySizeModel = InsuranceFamilySize::where('size', 'like', '%' . $familySizeValue . '%')->first();

                if (!$familySizeModel) {
                    throw new \RuntimeException('Family size "' . $familySizeValue . '" was not found.');
                }

                $familySize = $familySizeModel->size;
            }

            if ($invoiceCategory === 'medical' && $familySize === null) {
                throw new \RuntimeException('Family size is required for medical deffered invoice items.');
            }

            $lineTotal = $quantity * $unitCost;
            $subTotal += $lineTotal;
            $itemTaxes = [];

            foreach ($this->parseTaxNames($itemData['tax'] ?? null) as $taxName) {
                $tax = Tax::where('name', 'like', '%' . $taxName . '%')->first();

                if (!$tax) {
                    throw new \RuntimeException('Tax "' . $taxName . '" was not found in Tax Database.');
                }

                if (!$tax->account_id) {
                    throw new \RuntimeException('Tax "' . $tax->name . '" is missing an account.');
                }

                $taxAmount = ($lineTotal / 100) * $tax->rate;
                $totalTax += $taxAmount;

                $itemTaxes[] = [
                    'tax_id' => $tax->id,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => $taxAmount,
                    'account_id' => $tax->account_id,
                    'rate' => $tax->rate,
                ];
            }

            $itemsData[] = [
                'product' => $product,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'sub_total' => $lineTotal,
                'sum_insured' => $sumInsured,
                'benefits' => $this->normalizeOptionalString($itemData['benefits'] ?? null),
                'family_size' => $familySize,
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
        $subTotalBase = $subTotal / $exchangeRate;
        $grandTotalBase = $grandTotalTransaction / $exchangeRate;
        $discountBase = $discountAmount / $exchangeRate;
        $template = $header['template'] ?? 0;
        $invoiceTitle = trim((string) ($header['title'] ?? get_business_option('invoice_title', 'Invoice')));

        if ($invoiceTitle === '') {
            $invoiceTitle = get_business_option('invoice_title', 'Invoice');
        }

        $earnings = $this->calculateEarningsSchedule($defferedStart, $defferedEnd, $subTotal);
        $currentTime = Carbon::now();
        $transDate = Carbon::parse($invoiceDate)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');

        $invoice = new Invoice();
        $invoice->customer_id = $customer->id;
        $invoice->title = $invoiceTitle;
        $invoice->invoice_number = get_business_option('invoice_number');
        $invoice->order_number = $policyNumber ?: trim((string) ($header['order_number'] ?? ''));
        $invoice->invoice_date = $transDate;
        $invoice->due_date = Carbon::parse($dueDate)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $invoice->sub_total = $subTotalBase;
        $invoice->grand_total = $grandTotalBase;
        $invoice->currency = $currencyCode;
        $invoice->converted_total = $grandTotalTransaction;
        $invoice->exchange_rate = $exchangeRate;
        $invoice->paid = 0;
        $invoice->discount = $discountBase;
        $invoice->discount_type = $discountType;
        $invoice->discount_value = $discountValue;
        $invoice->template_type = is_numeric($template) ? 1 : 0;
        $invoice->template = $template;
        $invoice->note = $this->normalizeOptionalString($header['note'] ?? null);
        $invoice->footer = $this->normalizeOptionalString($header['footer'] ?? get_business_option('invoice_footer'));
        $invoice->short_code = rand(100000, 9999999) . uniqid();
        $invoice->is_deffered = 1;
        $invoice->invoice_category = $invoiceCategory;
        $invoice->deffered_start = $defferedStart;
        $invoice->deffered_end = $defferedEnd;
        $invoice->active_days = $earnings['total_days'];
        $invoice->cost_per_day = $earnings['cost_per_day'];
        $invoice->save();

        if ($invoice->order_number === '') {
            throw new \RuntimeException('Policy number is required for imported deffered invoices.');
        }

        BusinessSetting::where('name', 'invoice_number')->increment('value');

        foreach ($earnings['schedule'] as $earning) {
            $defferedEarning = new DefferedEarning();
            $defferedEarning->invoice_id = $invoice->id;
            $defferedEarning->start_date = $earning['start_date'];
            $defferedEarning->end_date = $earning['end_date'];
            $defferedEarning->days = $earning['number_of_days'];
            $defferedEarning->currency = $currencyCode;
            $defferedEarning->exchange_rate = $exchangeRate;
            $defferedEarning->base_currency_amount = convert_currency(
                $currencyCode,
                request()->activeBusiness->currency,
                $earning['transaction_amount']
            );
            $defferedEarning->transaction_amount = $earning['transaction_amount'];
            $defferedEarning->save();
        }

        foreach ($itemsData as $item) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'description' => null,
                'sum_insured' => $item['sum_insured'],
                'benefits' => $item['benefits'],
                'family_size' => $item['family_size'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'sub_total' => $item['sub_total'],
            ]));

            $product = $item['product'];

            if ((int) $product->stock_management === 1) {
                $inventoryTransaction = new Transaction();
                $inventoryTransaction->trans_date = $transDate;
                $inventoryTransaction->account_id = get_account('Inventory')->id;
                $inventoryTransaction->dr_cr = 'cr';
                $inventoryTransaction->transaction_currency = $currencyCode;
                $inventoryTransaction->currency_rate = $exchangeRate;
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
                $inventoryTransaction->ref_type = 'd invoice';
                $inventoryTransaction->customer_id = $invoice->customer_id;
                $inventoryTransaction->save();
            }

            if ((int) $product->allow_for_purchasing === 1) {
                $expenseTransaction = new Transaction();
                $expenseTransaction->trans_date = $transDate;
                $expenseTransaction->account_id = $product->expense_account_id;
                $expenseTransaction->dr_cr = 'dr';
                $expenseTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $product->purchase_cost * $invoiceItem->quantity
                );
                $expenseTransaction->transaction_currency = $currencyCode;
                $expenseTransaction->currency_rate = $exchangeRate;
                $expenseTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        $product->purchase_cost * $invoiceItem->quantity
                    )
                );
                $expenseTransaction->ref_type = 'd invoice';
                $expenseTransaction->customer_id = $invoice->customer_id;
                $expenseTransaction->ref_id = $invoice->id;
                $expenseTransaction->description = 'Deffered Invoice #' . $invoice->invoice_number;
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
                $taxTransaction->currency_rate = $exchangeRate;
                $taxTransaction->base_currency_amount = convert_currency(
                    $currencyCode,
                    request()->activeBusiness->currency,
                    convert_currency(
                        request()->activeBusiness->currency,
                        $currencyCode,
                        (($invoiceItem->sub_total / $exchangeRate) / 100) * $itemTax['rate']
                    )
                );
                $taxTransaction->transaction_amount = convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    (($invoiceItem->sub_total / $exchangeRate) / 100) * $itemTax['rate']
                );
                $taxTransaction->description = _lang('Deffered Invoice Tax') . ' #' . $invoice->invoice_number;
                $taxTransaction->ref_id = $invoice->id;
                $taxTransaction->ref_type = 'd invoice tax';
                $taxTransaction->tax_id = $itemTax['tax_id'];
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

        $unearnedRevenueTransaction = new Transaction();
        $unearnedRevenueTransaction->trans_date = $transDate;
        $unearnedRevenueTransaction->account_id = get_account('Unearned Revenue')->id;
        $unearnedRevenueTransaction->dr_cr = 'cr';
        $unearnedRevenueTransaction->transaction_currency = $currencyCode;
        $unearnedRevenueTransaction->currency_rate = $exchangeRate;
        $unearnedRevenueTransaction->transaction_amount = convert_currency(
            request()->activeBusiness->currency,
            $currencyCode,
            $subTotalBase
        );
        $unearnedRevenueTransaction->base_currency_amount = convert_currency(
            $currencyCode,
            request()->activeBusiness->currency,
            convert_currency(
                request()->activeBusiness->currency,
                $currencyCode,
                $subTotalBase
            )
        );
        $unearnedRevenueTransaction->description = _lang('Deffered Invoice Liability') . ' #' . $invoice->invoice_number;
        $unearnedRevenueTransaction->ref_id = $invoice->id;
        $unearnedRevenueTransaction->ref_type = 'd invoice';
        $unearnedRevenueTransaction->customer_id = $invoice->customer_id;
        $unearnedRevenueTransaction->save();

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
        $accountsReceivableTransaction->currency_rate = $exchangeRate;
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
        $accountsReceivableTransaction->ref_type = 'd invoice';
        $accountsReceivableTransaction->customer_id = $invoice->customer_id;
        $accountsReceivableTransaction->description = 'Deffered Invoice #' . $invoice->invoice_number;
        $accountsReceivableTransaction->save();

        if ($discountAmount > 0) {
            $discountTransaction = new Transaction();
            $discountTransaction->trans_date = $transDate;
            $discountTransaction->account_id = get_account('Sales Discount Allowed')->id;
            $discountTransaction->dr_cr = 'dr';
            $discountTransaction->transaction_amount = convert_currency(
                request()->activeBusiness->currency,
                $currencyCode,
                $discountBase
            );
            $discountTransaction->transaction_currency = $currencyCode;
            $discountTransaction->currency_rate = $exchangeRate;
            $discountTransaction->base_currency_amount = convert_currency(
                $currencyCode,
                request()->activeBusiness->currency,
                convert_currency(
                    request()->activeBusiness->currency,
                    $currencyCode,
                    $discountBase
                )
            );
            $discountTransaction->description = _lang('Deffered Invoice Discount') . ' #' . $invoice->invoice_number;
            $discountTransaction->ref_id = $invoice->id;
            $discountTransaction->ref_type = 'd invoice';
            $discountTransaction->customer_id = $invoice->customer_id;
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
            'policy_number' => 'order_number',
            'policy_no' => 'order_number',
            'invoice_number' => 'order_number',
            'invoice_no' => 'order_number',
            'customer' => 'customer_name',
            'category' => 'invoice_category',
            'policy_start' => 'deffered_start',
            'policy_end' => 'deffered_end',
            'start_date' => 'deffered_start',
            'end_date' => 'deffered_end',
            'transaction_currency' => 'currency',
            'order_number' => 'order_number',
            'service_name' => 'product_name',
            'service' => 'product_name',
            'members' => 'quantity',
            'rate' => 'unit_cost',
            'unit_price' => 'unit_cost',
            'family' => 'family_size',
            'family_members' => 'family_size',
            'benefit' => 'benefits',
            'suminsured' => 'sum_insured',
            'taxes' => 'tax',
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

    private function parseDate($date): ?string
    {
        if ($date === null || trim((string) $date) === '') {
            return null;
        }

        if (is_numeric($date)) {
            try {
                return Date::excelToDateTimeObject($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
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
            return null;
        }
    }

    private function requireParsedDate($value, string $label): string
    {
        if ($value === null || trim((string) $value) === '') {
            throw new \RuntimeException($label . ' is required.');
        }

        $date = $this->parseDate($value);
        if ($date === null) {
            throw new \RuntimeException($label . ' is invalid.');
        }

        return $date;
    }

    private function normalizeInvoiceCategory($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        $aliases = [
            '1' => 'medical',
            'medical' => 'medical',
            'medical insurance invoice' => 'medical',
            'medical_insurance_invoice' => 'medical',
            '2' => 'gpa',
            'gpa' => 'gpa',
            'gpa insurance invoice' => 'gpa',
            'gpa_insurance_invoice' => 'gpa',
            '3' => 'other',
            'other' => 'other',
            'other insurance invoice' => 'other',
            'other_insurance_invoice' => 'other',
        ];

        return $aliases[$normalized] ?? null;
    }

    private function normalizeOptionalString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    private function normalizeOptionalNumber($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
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

    private function calculateEarningsSchedule(string $startDate, string $endDate, float $subTotal): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $decimalPlace = (int) get_business_option('decimal_place', 2);
        $factor = (int) (10 ** $decimalPlace);
        $totalDays = max(1, (int) round($start->diffInDays($end) + 1));
        $subTotalUnits = (int) round($subTotal * $factor);
        $unitsPerDay = intdiv($subTotalUnits, $totalDays);
        $scheduleUnits = [];
        $cursor = $start->copy();
        $usedUnits = 0;

        while ($cursor->lte($end)) {
            $sliceStart = $cursor->copy();
            $sliceEnd = $cursor->copy()->endOfMonth()->lt($end) ? $cursor->copy()->endOfMonth() : $end->copy();
            $days = max(1, (int) round($sliceStart->diffInDays($sliceEnd) + 1));
            $sliceUnits = $unitsPerDay * $days;

            $scheduleUnits[] = [
                'slice_start' => $sliceStart,
                'slice_end' => $sliceEnd,
                'days' => $days,
                'slice_units' => $sliceUnits,
            ];

            $usedUnits += $sliceUnits;
            $cursor = $sliceEnd->copy()->addDay()->startOfDay();
        }

        $remainder = $subTotalUnits - $usedUnits;
        if ($scheduleUnits !== [] && $remainder !== 0) {
            $scheduleUnits[count($scheduleUnits) - 1]['slice_units'] += $remainder;
        }

        $schedule = [];
        foreach ($scheduleUnits as $unit) {
            $schedule[] = [
                'start_date' => $unit['slice_start']->format('Y-m-d'),
                'end_date' => $unit['slice_end']->format('Y-m-d'),
                'number_of_days' => $unit['days'],
                'transaction_amount' => round($unit['slice_units'] / $factor, $decimalPlace),
            ];
        }

        return [
            'schedule' => $schedule,
            'total_days' => $totalDays,
            'cost_per_day' => round(($subTotalUnits / $factor) / $totalDays, $decimalPlace),
        ];
    }

    private function ensureDefaultAccounts(): void
    {
        $defaultAccounts = [
            ['name' => 'Accounts Receivable', 'code' => '1100', 'type' => 'Other Current Asset', 'dr_cr' => 'dr'],
            ['name' => 'Sales Tax Payable', 'code' => '2200', 'type' => 'Current Liability', 'dr_cr' => 'cr'],
            ['name' => 'Sales Discount Allowed', 'code' => '4009', 'type' => 'Other Income', 'dr_cr' => 'dr'],
            ['name' => 'Inventory', 'code' => '1000', 'type' => 'Other Current Asset', 'dr_cr' => 'dr'],
            ['name' => 'Unearned Revenue', 'code' => '2300', 'type' => 'Current Liability', 'dr_cr' => 'cr'],
        ];

        foreach ($defaultAccounts as $account) {
            if (!Account::where('account_name', $account['name'])->where('business_id', request()->activeBusiness->id)->exists()) {
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
