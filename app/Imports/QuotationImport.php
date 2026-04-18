<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\InsuranceFamilySize;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationItemTax;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class QuotationImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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

        $groupedQuotations = [];
        $autoGroupCounter = 0;

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());
            $quotationNumber = $this->normalizeQuotationNumber($data['quotation_number'] ?? null);
            $productName = trim((string) ($data['product_name'] ?? ''));

            if ($quotationNumber === null && $productName === '') {
                continue;
            }

            $groupKey = $quotationNumber !== null
                ? 'quotation::' . strtolower($quotationNumber)
                : 'auto::' . (++$autoGroupCounter);

            if (!isset($groupedQuotations[$groupKey])) {
                $groupedQuotations[$groupKey] = [
                    'quotation_number' => $quotationNumber,
                    'header' => $data,
                    'items' => [],
                ];
            }

            $groupedQuotations[$groupKey]['items'][] = $data;
        }

        foreach ($groupedQuotations as $groupKey => $quotationData) {
            DB::beginTransaction();

            try {
                $this->createQuotation($quotationData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                $loggedQuotationNumber = $quotationData['quotation_number'] ?? $groupKey;
                \Log::error("Error importing quotation {$loggedQuotationNumber}: " . $e->getMessage());
            }
        }
    }

    private function createQuotation(array $quotationData): void
    {
        $quotationNumber = $quotationData['quotation_number'] ?? null;
        $header = $quotationData['header'];
        $items = $quotationData['items'];

        $quotationDateRaw = $header['quotation_date'] ?? null;
        if ($quotationDateRaw === null || trim((string) $quotationDateRaw) === '') {
            throw new \RuntimeException('Quotation date is required for imported quotations.');
        }

        $expiredDateRaw = $header['expired_date'] ?? null;
        if ($expiredDateRaw === null || trim((string) $expiredDateRaw) === '') {
            throw new \RuntimeException('Expiry date is required for imported quotations.');
        }

        $quotationDate = $this->parseDate($quotationDateRaw);
        $expiredDate = $this->parseDate($expiredDateRaw);

        if (Carbon::parse($expiredDate)->lt(Carbon::parse($quotationDate))) {
            throw new \RuntimeException('Expiry date must be on or after the quotation date.');
        }

        $customerName = trim((string) ($header['customer_name'] ?? ''));
        if ($customerName === '') {
            throw new \RuntimeException('Customer name is required for imported quotations.');
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
        $exchangeRate = $this->resolveExchangeRate($header['exchange_rate'] ?? null, $currency);

        $isDeferred = $this->parseDeferredFlag($header['is_deffered'] ?? 0);
        $invoiceCategory = $this->normalizeInvoiceCategory($header['invoice_category'] ?? null);

        if ($isDeferred && $invoiceCategory === null) {
            throw new \RuntimeException('Deferred quotations require an invoice category (medical, gpa, or other).');
        }

        $subTotal = 0.0;
        $totalTax = 0.0;
        $itemsData = [];

        foreach ($items as $itemData) {
            $productName = trim((string) ($itemData['product_name'] ?? ''));
            if ($productName === '') {
                throw new \RuntimeException('Product name is required for each imported quotation item.');
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

            $lineTotal = $quantity * $unitCost;
            $subTotal += $lineTotal;

            $familySize = trim((string) ($itemData['family_size'] ?? ''));
            if ($isDeferred && $invoiceCategory === 'medical' && $familySize !== '') {
                $familySizeExists = InsuranceFamilySize::where('size', 'like', '%' . $familySize . '%')->exists();
                if (!$familySizeExists) {
                    throw new \RuntimeException('Family size "' . $familySize . '" was not found.');
                }
            }

            $sumInsured = $this->resolveSumInsuredValue($itemData['sum_insured'] ?? null, $isDeferred, $invoiceCategory);

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
                'product_id' => $product->id,
                'product_name' => $product->name,
                'description' => trim((string) ($itemData['description'] ?? ($product->descriptions ?? ''))),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'sub_total' => $lineTotal,
                'family_size' => $isDeferred && $invoiceCategory === 'medical' ? ($familySize !== '' ? $familySize : null) : null,
                'sum_insured' => $sumInsured,
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

        $quotation = new Quotation();
        $quotation->customer_id = $customer->id;
        $quotation->title = trim((string) ($header['title'] ?? get_business_option('quotation_title', 'Quotation')));

        if ($quotationNumber === null || $quotationNumber === '') {
            $quotation->quotation_number = $this->nextQuotationNumber();
        } else {
            $quotation->quotation_number = $quotationNumber;
        }

        $quotation->po_so_number = trim((string) ($header['po_so_number'] ?? $header['order_number'] ?? ''));
        $quotation->quotation_date = $quotationDate;
        $quotation->expired_date = $expiredDate;
        $quotation->sub_total = $subTotal / $exchangeRate;
        $quotation->grand_total = $grandTotal / $exchangeRate;
        $quotation->currency = $currencyCode;
        $quotation->converted_total = $grandTotal;
        $quotation->exchange_rate = $exchangeRate;
        $quotation->discount = $discountAmount / $exchangeRate;
        $quotation->discount_type = $discountType;
        $quotation->discount_value = $discountValue;
        $quotation->template_type = 0;
        $quotation->template = 'default';
        $quotation->note = trim((string) ($header['note'] ?? ''));
        $quotation->footer = trim((string) ($header['footer'] ?? ''));
        $quotation->is_deffered = $isDeferred ? 1 : 0;
        $quotation->invoice_category = $isDeferred ? $invoiceCategory : null;
        $quotation->short_code = rand(100000, 9999999) . uniqid();
        $quotation->save();

        foreach ($itemsData as $item) {
            $quotationItem = $quotation->items()->save(new QuotationItem([
                'quotation_id' => $quotation->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'sub_total' => $item['sub_total'],
                'family_size' => $item['family_size'],
                'sum_insured' => $item['sum_insured'],
            ]));

            foreach ($item['item_taxes'] as $itemTax) {
                $quotationItem->taxes()->save(new QuotationItemTax([
                    'quotation_id' => $quotation->id,
                    'tax_id' => $itemTax['tax_id'],
                    'name' => $itemTax['name'],
                    'amount' => $itemTax['amount'],
                ]));
            }
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
            'quotation_no' => 'quotation_number',
            'quote_number' => 'quotation_number',
            'customer' => 'customer_name',
            'client_name' => 'customer_name',
            'transaction_currency' => 'currency',
            'order_number' => 'po_so_number',
            'expiry_date' => 'expired_date',
            'quote_date' => 'quotation_date',
            'deferred' => 'is_deffered',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    private function normalizeQuotationNumber($quotationNumber): ?string
    {
        if ($quotationNumber === null) {
            return null;
        }

        $normalized = trim((string) $quotationNumber);
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

    private function parseDeferredFlag($value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes'], true);
    }

    private function normalizeInvoiceCategory($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['medical', 'gpa', 'other'], true) ? $normalized : null;
    }

    private function resolveSumInsuredValue($value, bool $isDeferred, ?string $invoiceCategory): float
    {
        if (!$isDeferred || $invoiceCategory !== 'other') {
            return 0.0;
        }

        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }

        if (!is_numeric($value) || (float) $value < 0) {
            throw new \RuntimeException('Sum insured must be a non-negative number for deferred quotations in the other category.');
        }

        return (float) $value;
    }

    private function nextQuotationNumber(): string
    {
        $currentNumber = (string) get_business_option('quotation_number', '100001');
        BusinessSetting::where('name', 'quotation_number')->increment('value');

        return $currentNumber;
    }
}
