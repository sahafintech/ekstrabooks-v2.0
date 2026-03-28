<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\ReceivePayment;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * ReceivePaymentImport handles importing receive payments from Excel/CSV files.
 *
 * Grouping rule:
 * - Create one ReceivePayment per customer + payment_date combination.
 */
class ReceivePaymentImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $this->ensureDefaultAccounts();

        $customerLookupCache = [];
        $accountLookupCache = [];
        $methodLookupCache = [];
        $invoiceLookupCache = [];
        $invoiceRemainingDueCache = [];
        $groupedPayments = [];

        foreach ($rows as $row) {
            $data = $this->mapRowData($row->toArray());

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $customerName = trim((string) ($data['customer_name'] ?? ''));
            $invoiceNumber = trim((string) ($data['invoice_number'] ?? ''));
            $paymentAccountName = trim((string) ($data['payment_account'] ?? ''));
            $paymentMethodName = trim((string) ($data['payment_method'] ?? ''));
            $reference = trim((string) ($data['reference'] ?? ''));
            $paymentDate = $this->parseDate($data['payment_date'] ?? null);
            $amount = $this->parseAmount($data['amount'] ?? null);

            if (
                $customerName === ''
                || $invoiceNumber === ''
                || $paymentAccountName === ''
                || $paymentDate === null
                || $amount === null
                || $amount <= 0
            ) {
                continue;
            }

            $customerCacheKey = strtolower($customerName);
            if (!array_key_exists($customerCacheKey, $customerLookupCache)) {
                $customerLookupCache[$customerCacheKey] = Customer::where('business_id', request()->activeBusiness->id)
                    ->where('name', 'like', '%' . $customerName . '%')
                    ->first();
            }
            $customer = $customerLookupCache[$customerCacheKey];
            if (!$customer) {
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

            $invoiceCacheKey = strtolower((string) $customer->id . '::' . $invoiceNumber);
            if (!array_key_exists($invoiceCacheKey, $invoiceLookupCache)) {
                $invoiceLookupCache[$invoiceCacheKey] = Invoice::where('customer_id', $customer->id)
                    ->where('business_id', request()->activeBusiness->id)
                    ->where('invoice_number', $invoiceNumber)
                    ->first();
            }
            $invoice = $invoiceLookupCache[$invoiceCacheKey];
            if (!$invoice) {
                continue;
            }

            $invoiceId = (int) $invoice->id;
            if (!array_key_exists($invoiceId, $invoiceRemainingDueCache)) {
                $invoiceRemainingDueCache[$invoiceId] = (float) $invoice->getRawOriginal('grand_total') - (float) $invoice->getRawOriginal('paid');
            }

            if ($invoiceRemainingDueCache[$invoiceId] <= 0 || $amount > $invoiceRemainingDueCache[$invoiceId] + 0.00001) {
                continue;
            }

            $groupKey = strtolower(
                (string) $customer->id
                . '|'
                . $paymentDate
                . '|'
                . $account->id
                . '|'
                . strtolower($paymentMethodName)
                . '|'
                . strtolower($reference)
            );

            if (!isset($groupedPayments[$groupKey])) {
                $groupedPayments[$groupKey] = [
                    'customer_id' => (int) $customer->id,
                    'date' => $paymentDate,
                    'account_id' => (int) $account->id,
                    'payment_method' => $paymentMethodName !== '' ? $paymentMethodName : null,
                    'reference' => $reference !== '' ? $reference : null,
                    'rows' => [],
                ];
            }

            $groupedPayments[$groupKey]['rows'][] = [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
            ];

            $invoiceRemainingDueCache[$invoiceId] = $invoiceRemainingDueCache[$invoiceId] - $amount;
        }

        foreach ($groupedPayments as $groupKey => $groupData) {
            DB::beginTransaction();
            try {
                $this->createReceivePayment($groupData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error importing receive payment {$groupKey}: " . $e->getMessage());
            }
        }
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
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $invoiceNumber = trim((string) ($data['invoice_number'] ?? ''));
        $amount = trim((string) ($data['amount'] ?? ''));
        $paymentDate = trim((string) ($data['payment_date'] ?? ''));
        $paymentAccount = trim((string) ($data['payment_account'] ?? ''));
        $paymentMethod = trim((string) ($data['payment_method'] ?? ''));
        $reference = trim((string) ($data['reference'] ?? ''));

        return $customerName === ''
            && $invoiceNumber === ''
            && $amount === ''
            && $paymentDate === ''
            && $paymentAccount === ''
            && $paymentMethod === ''
            && $reference === '';
    }

    /**
     * Create a receive payment and all linked payment/transaction entries.
     */
    private function createReceivePayment(array $groupData): void
    {
        if (empty($groupData['rows'])) {
            return;
        }

        $payment = new ReceivePayment();
        $payment->date = $groupData['date'];
        $payment->customer_id = $groupData['customer_id'];
        $payment->account_id = $groupData['account_id'];
        $payment->payment_method = $groupData['payment_method'];
        $payment->reference = $groupData['reference'];
        $payment->amount = array_sum(array_column($groupData['rows'], 'amount'));
        $payment->type = 'offline';
        $payment->deffered_payment = 0;
        $payment->save();

        $currentTime = Carbon::now();
        $accountsReceivable = get_account('Accounts Receivable');
        if (!$accountsReceivable) {
            throw new \RuntimeException('Accounts Receivable account was not found.');
        }

        foreach ($groupData['rows'] as $paymentRow) {
            $invoice = Invoice::find($paymentRow['invoice_id']);
            if (!$invoice) {
                continue;
            }

            $amount = (float) $paymentRow['amount'];
            if ($amount <= 0) {
                continue;
            }

            $currentDue = (float) $invoice->getRawOriginal('grand_total') - (float) $invoice->getRawOriginal('paid');
            if ($amount > $currentDue + 0.00001) {
                continue;
            }

            $invoicePaymentAmount = convert_currency(
                $invoice->currency,
                request()->activeBusiness->currency,
                convert_currency(request()->activeBusiness->currency, $invoice->currency, $amount)
            );

            $invoicePayment = new InvoicePayment();
            $invoicePayment->invoice_id = $invoice->id;
            $invoicePayment->payment_id = $payment->id;
            $invoicePayment->amount = $invoicePaymentAmount;
            $invoicePayment->save();

            $transDate = Carbon::parse($groupData['date'])
                ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
                ->format('Y-m-d H:i:s');

            $shortTransDate = Carbon::parse($groupData['date'])
                ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
                ->format('Y-m-d H:i');

            $transactionAmount = convert_currency(request()->activeBusiness->currency, $invoice->currency, $amount);
            $baseCurrencyAmount = convert_currency(
                $invoice->currency,
                request()->activeBusiness->currency,
                convert_currency(request()->activeBusiness->currency, $invoice->currency, $amount)
            );

            $this->createTransaction(
                $transDate,
                $groupData['account_id'],
                'dr',
                $transactionAmount,
                $baseCurrencyAmount,
                $invoice,
                $payment,
                $groupData['payment_method'],
                $groupData['reference'],
                true
            );

            $this->createTransaction(
                $shortTransDate,
                $accountsReceivable->id,
                'cr',
                $transactionAmount,
                $baseCurrencyAmount,
                $invoice,
                $payment,
                $groupData['payment_method'],
                $groupData['reference'],
                false
            );

            $updatedPaid = (float) $invoice->getRawOriginal('paid') + $invoicePaymentAmount;
            $grandTotal = (float) $invoice->getRawOriginal('grand_total');

            $invoice->paid = $updatedPaid;
            if ($updatedPaid >= $grandTotal) {
                $invoice->status = 2;
            } elseif ($updatedPaid <= 0) {
                $invoice->status = 1;
            } else {
                $invoice->status = 3;
            }
            $invoice->save();
        }

        $importedAmount = (float) InvoicePayment::where('payment_id', $payment->id)->sum('amount');
        if ($importedAmount <= 0) {
            $payment->delete();
            return;
        }

        $payment->amount = $importedAmount;
        $payment->save();
    }

    /**
     * Create a receive payment transaction entry.
     */
    private function createTransaction(
        string $transDate,
        int $accountId,
        string $drCr,
        float $transactionAmount,
        float $baseCurrencyAmount,
        Invoice $invoice,
        ReceivePayment $payment,
        ?string $paymentMethod,
        ?string $reference,
        bool $includeMethod
    ): void {
        $transaction = new Transaction();
        $transaction->trans_date = $transDate;
        $transaction->account_id = $accountId;
        if ($includeMethod) {
            $transaction->transaction_method = $paymentMethod;
        }
        $transaction->dr_cr = $drCr;
        $transaction->transaction_amount = $transactionAmount;
        $transaction->transaction_currency = $invoice->currency;
        $transaction->currency_rate = $invoice->exchange_rate;
        $transaction->base_currency_amount = $baseCurrencyAmount;
        $transaction->reference = $reference;
        $transaction->description = _lang('Credit Invoice Payment') . ' #' . $invoice->invoice_number;
        $transaction->ref_id = $invoice->id . ',' . $payment->id;
        $transaction->ref_type = 'invoice payment';
        $transaction->customer_id = $payment->customer_id;
        $transaction->project_id = $invoice->project_id;
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

    /**
     * Ensure default accounting records required for invoice payments exist.
     */
    private function ensureDefaultAccounts(): void
    {
        $defaultAccounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        foreach ($defaultAccounts as $accountName) {
            if (Account::where('account_name', $accountName)->where('business_id', request()->activeBusiness->id)->exists()) {
                continue;
            }

            $account = new Account();

            if ($accountName === 'Accounts Receivable') {
                $account->account_code = '1100';
                $account->account_type = 'Other Current Asset';
                $account->dr_cr = 'dr';
            } elseif ($accountName === 'Sales Tax Payable') {
                $account->account_code = '2200';
                $account->account_type = 'Current Liability';
                $account->dr_cr = 'cr';
            } elseif ($accountName === 'Sales Discount Allowed') {
                $account->account_code = '4009';
                $account->account_type = 'Other Income';
                $account->dr_cr = 'dr';
            } else {
                $account->account_code = '1000';
                $account->account_type = 'Other Current Asset';
                $account->dr_cr = 'dr';
            }

            $account->account_name = $accountName;
            $account->business_id = request()->activeBusiness->id;
            $account->user_id = request()->activeBusiness->user->id;
            $account->opening_date = now()->format('Y-m-d');
            $account->save();
        }
    }
}
