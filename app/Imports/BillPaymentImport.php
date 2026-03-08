<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\BillPayment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * BillPaymentImport handles importing bill payments from Excel/CSV files.
 *
 * Grouping rule:
 * - Create one BillPayment per supplier + payment_date combination.
 */
class BillPaymentImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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
     * Determine whether all relevant import fields are empty.
     */
    private function isEmptyRow(array $data): bool
    {
        $supplierName = trim((string) ($data['supplier_name'] ?? ''));
        $billNumber = trim((string) ($data['bill_number'] ?? ''));
        $amount = trim((string) ($data['amount'] ?? ''));
        $paymentDate = trim((string) ($data['payment_date'] ?? ''));
        $paymentAccount = trim((string) ($data['payment_account'] ?? ''));
        $paymentMethod = trim((string) ($data['payment_method'] ?? ''));
        $reference = trim((string) ($data['reference'] ?? ''));

        return $supplierName === ''
            && $billNumber === ''
            && $amount === ''
            && $paymentDate === ''
            && $paymentAccount === ''
            && $paymentMethod === ''
            && $reference === '';
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $vendorLookupCache = [];
        $accountLookupCache = [];
        $methodLookupCache = [];
        $billLookupCache = [];
        $billRemainingDueCache = [];
        $groupHeaderCache = [];
        $groupedPayments = [];

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $supplierName = trim((string) ($data['supplier_name'] ?? ''));
            $billNumber = trim((string) ($data['bill_number'] ?? ''));
            $paymentAccountName = trim((string) ($data['payment_account'] ?? ''));
            $paymentMethodName = trim((string) ($data['payment_method'] ?? ''));
            $reference = trim((string) ($data['reference'] ?? ''));
            $paymentDate = $this->parseDate($data['payment_date'] ?? null);
            $amount = $this->parseAmount($data['amount'] ?? null);

            if (
                $supplierName === ''
                || $billNumber === ''
                || $paymentAccountName === ''
                || $paymentDate === null
                || $amount === null
                || $amount <= 0
            ) {
                continue;
            }

            $vendorCacheKey = strtolower($supplierName);
            if (!array_key_exists($vendorCacheKey, $vendorLookupCache)) {
                $vendorLookupCache[$vendorCacheKey] = Vendor::where('name', 'like', '%' . $supplierName . '%')->first();
            }
            $vendor = $vendorLookupCache[$vendorCacheKey];
            if (!$vendor) {
                continue;
            }

            $accountCacheKey = strtolower($paymentAccountName);
            if (!array_key_exists($accountCacheKey, $accountLookupCache)) {
                $accountLookupCache[$accountCacheKey] = Account::where(function ($query) {
                    $query->where('account_type', 'Bank')
                        ->orWhere('account_type', 'Cash');
                })->where('account_name', 'like', '%' . $paymentAccountName . '%')->first();
            }
            $account = $accountLookupCache[$accountCacheKey];
            if (!$account) {
                continue;
            }

            if ($paymentMethodName !== '') {
                $methodCacheKey = strtolower($paymentMethodName);
                if (!array_key_exists($methodCacheKey, $methodLookupCache)) {
                    $methodLookupCache[$methodCacheKey] = TransactionMethod::where('name', 'like', '%' . $paymentMethodName . '%')->first();
                }
                if (!$methodLookupCache[$methodCacheKey]) {
                    continue;
                }
            }

            $billCacheKey = strtolower((string) $vendor->id . '::' . $billNumber);
            if (!array_key_exists($billCacheKey, $billLookupCache)) {
                $billLookupCache[$billCacheKey] = Purchase::where('vendor_id', $vendor->id)
                    ->where('cash', 0)
                    ->where('order', 0)
                    ->where('bill_no', $billNumber)
                    ->first();
            }
            $purchase = $billLookupCache[$billCacheKey];
            if (!$purchase) {
                continue;
            }

            $purchaseId = (int) $purchase->id;
            if (!array_key_exists($purchaseId, $billRemainingDueCache)) {
                $billRemainingDueCache[$purchaseId] = (float) $purchase->getRawOriginal('grand_total') - (float) $purchase->getRawOriginal('paid');
            }

            if ($billRemainingDueCache[$purchaseId] <= 0 || $amount > $billRemainingDueCache[$purchaseId] + 0.00001) {
                continue;
            }

            $groupKey = strtolower((string) $vendor->id . '|' . $paymentDate);
            $normalizedHeaderData = [
                'account_id' => (int) $account->id,
                'payment_method' => strtolower($paymentMethodName),
                'reference' => strtolower($reference),
            ];

            if (!isset($groupHeaderCache[$groupKey])) {
                $groupHeaderCache[$groupKey] = $normalizedHeaderData;
            } else {
                $existingHeader = $groupHeaderCache[$groupKey];
                if (
                    $existingHeader['account_id'] !== $normalizedHeaderData['account_id']
                    || $existingHeader['payment_method'] !== $normalizedHeaderData['payment_method']
                    || $existingHeader['reference'] !== $normalizedHeaderData['reference']
                ) {
                    continue;
                }
            }

            if (!isset($groupedPayments[$groupKey])) {
                $groupedPayments[$groupKey] = [
                    'vendor_id' => (int) $vendor->id,
                    'date' => $paymentDate,
                    'account_id' => (int) $account->id,
                    'payment_method' => $paymentMethodName !== '' ? $paymentMethodName : null,
                    'reference' => $reference !== '' ? $reference : null,
                    'rows' => [],
                ];
            }

            $groupedPayments[$groupKey]['rows'][] = [
                'purchase_id' => $purchaseId,
                'amount' => $amount,
            ];

            $billRemainingDueCache[$purchaseId] = $billRemainingDueCache[$purchaseId] - $amount;
        }

        foreach ($groupedPayments as $groupKey => $groupData) {
            DB::beginTransaction();
            try {
                $this->createBillPayment($groupData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error importing bill payment {$groupKey}: " . $e->getMessage());
            }
        }
    }

    /**
     * Create a bill payment and all linked payment/transaction entries.
     */
    private function createBillPayment(array $groupData): void
    {
        if (empty($groupData['rows'])) {
            return;
        }

        $payment = new BillPayment();
        $payment->date = $groupData['date'];
        $payment->vendor_id = $groupData['vendor_id'];
        $payment->account_id = $groupData['account_id'];
        $payment->payment_method = $groupData['payment_method'];
        $payment->reference = $groupData['reference'];
        $payment->amount = array_sum(array_column($groupData['rows'], 'amount'));
        $payment->save();

        $currentTime = Carbon::now();
        $accountsPayable = get_account('Accounts Payable');
        if (!$accountsPayable) {
            throw new \RuntimeException('Accounts Payable account was not found.');
        }

        foreach ($groupData['rows'] as $paymentRow) {
            $purchase = Purchase::find($paymentRow['purchase_id']);
            if (!$purchase) {
                continue;
            }

            $amount = (float) $paymentRow['amount'];
            if ($amount <= 0) {
                continue;
            }

            $currentDue = (float) $purchase->getRawOriginal('grand_total') - (float) $purchase->getRawOriginal('paid');
            if ($amount > $currentDue + 0.00001) {
                continue;
            }

            $purchasePaymentAmount = convert_currency(
                $purchase->currency,
                request()->activeBusiness->currency,
                convert_currency(request()->activeBusiness->currency, $purchase->currency, $amount)
            );

            $purchasePayment = new PurchasePayment();
            $purchasePayment->purchase_id = $purchase->id;
            $purchasePayment->payment_id = $payment->id;
            $purchasePayment->amount = $purchasePaymentAmount;
            $purchasePayment->save();

            $transDate = Carbon::parse($groupData['date'])
                ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
                ->format('Y-m-d H:i:s');

            $transactionAmount = convert_currency(request()->activeBusiness->currency, $purchase->currency, $amount);
            $baseCurrencyAmount = convert_currency(
                $purchase->currency,
                request()->activeBusiness->currency,
                $transactionAmount
            );

            $this->createTransaction(
                $transDate,
                $groupData['account_id'],
                'cr',
                $transactionAmount,
                $baseCurrencyAmount,
                $purchase,
                $payment,
                $groupData['payment_method'],
                $groupData['reference']
            );

            $this->createTransaction(
                $transDate,
                $accountsPayable->id,
                'dr',
                $transactionAmount,
                $baseCurrencyAmount,
                $purchase,
                $payment,
                $groupData['payment_method'],
                $groupData['reference']
            );

            $updatedPaid = (float) $purchase->getRawOriginal('paid') + $purchasePaymentAmount;
            $grandTotal = (float) $purchase->getRawOriginal('grand_total');

            $purchase->paid = $updatedPaid;
            if ($updatedPaid >= $grandTotal) {
                $purchase->status = 2;
            } elseif ($updatedPaid <= 0) {
                $purchase->status = 0;
            } else {
                $purchase->status = 1;
            }
            $purchase->save();
        }

        $importedAmount = (float) PurchasePayment::where('payment_id', $payment->id)->sum('amount');
        if ($importedAmount <= 0) {
            $payment->delete();
            return;
        }

        $payment->amount = $importedAmount;
        $payment->save();
    }

    /**
     * Create a bill payment transaction entry.
     */
    private function createTransaction(
        string $transDate,
        int $accountId,
        string $drCr,
        float $transactionAmount,
        float $baseCurrencyAmount,
        Purchase $purchase,
        BillPayment $payment,
        ?string $paymentMethod,
        ?string $reference
    ): void {
        $transaction = new Transaction();
        $transaction->trans_date = $transDate;
        $transaction->account_id = $accountId;
        $transaction->transaction_method = $paymentMethod;
        $transaction->dr_cr = $drCr;
        $transaction->transaction_amount = $transactionAmount;
        $transaction->transaction_currency = $purchase->currency;
        $transaction->currency_rate = $purchase->exchange_rate;
        $transaction->base_currency_amount = $baseCurrencyAmount;
        $transaction->reference = $reference;
        $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
        $transaction->ref_id = $purchase->id . ',' . $payment->id;
        $transaction->ref_type = 'bill payment';
        $transaction->vendor_id = $payment->vendor_id;
        $transaction->project_id = $purchase->project_id;
        $transaction->save();
    }

    /**
     * Parse numeric amount.
     */
    private function parseAmount($amount): ?float
    {
        if ($amount === null) {
            return null;
        }

        $raw = trim((string) $amount);
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace([',', ' '], '', $raw);
        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Parse date from Excel numeric or text date formats.
     */
    private function parseDate($date): ?string
    {
        if ($date === null || $date === '') {
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
        if ($rawDate === '') {
            return null;
        }

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
}
