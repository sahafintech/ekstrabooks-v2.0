<?php

namespace App\Cronjobs;

use App\Models\Account;
use App\Models\Business;
use App\Models\DefferedEarning;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DefferedInvoiceTask
{
    private const BATCH_SIZE = 200;
    private const RECOGNITION_REF_TYPE = 'd invoice income';
    private const REFERENCE_PREFIX = 'deferred-earning';
    private ?bool $transactionsHaveTypeColumn = null;

    public function __invoke(): array
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now();
        $summary = [
            'processed' => 0,
            'already_processed' => 0,
            'failed' => 0,
        ];

        DefferedEarning::query()
            ->where('status', 0)
            ->whereDate('end_date', '<=', $today)
            ->whereHas('invoice', function ($query) {
                $query->where('is_deffered', 1);
            })
            ->chunkById(self::BATCH_SIZE, function ($earnings) use (&$summary, $currentTime) {
                foreach ($earnings as $earning) {
                    try {
                        $result = $this->recognizeEarning((int) $earning->id, $currentTime);
                        $summary[$result]++;
                    } catch (Throwable $exception) {
                        $summary['failed']++;

                        Log::error('Deferred earning recognition failed.', [
                            'earning_id' => $earning->id,
                            'invoice_id' => $earning->invoice_id,
                            'message' => $exception->getMessage(),
                            'exception' => $exception,
                        ]);
                    }
                }
            });

        if (array_sum($summary) > 0) {
            Log::info('Deferred invoice recognition run completed.', $summary);
        }

        return $summary;
    }

    private function recognizeEarning(int $earningId, Carbon $currentTime): string
    {
        return DB::transaction(function () use ($earningId, $currentTime) {
            $earning = DefferedEarning::whereKey($earningId)
                ->where('status', 0)
                ->lockForUpdate()
                ->first();

            if (! $earning) {
                return 'already_processed';
            }

            $invoice = Invoice::with([
                'business',
                'items' => function ($query) {
                    $query->whereNull('deleted_at')->with('product');
                },
            ])
                ->whereKey($earning->invoice_id)
                ->where('is_deffered', 1)
                ->first();

            if (! $invoice) {
                throw new \RuntimeException('Deferred invoice was not found or is no longer active.');
            }

            $transactionCurrency = $earning->currency ?: $invoice->currency;
            $baseCurrency = optional($invoice->business)->currency;
            $transactionAmount = (float) $earning->getRawOriginal('transaction_amount');
            $baseCurrencyAmount = $this->toBaseCurrency($transactionCurrency, $baseCurrency, $transactionAmount);

            if ($transactionAmount == 0.0 && $baseCurrencyAmount == 0.0) {
                $earning->status = 1;
                $earning->save();

                return 'processed';
            }

            $allocations = $this->buildIncomeAllocations($invoice, $transactionAmount, $baseCurrencyAmount);
            $unearnedRevenue = $this->getOrCreateUnearnedRevenueAccount($invoice);
            $transDate = Carbon::parse($earning->getRawOriginal('end_date'))
                ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
                ->format('Y-m-d H:i:s');

            foreach ($allocations as $allocation) {
                $this->upsertRecognitionTransaction(
                    invoice: $invoice,
                    earning: $earning,
                    accountId: $allocation['account_id'],
                    drCr: 'cr',
                    transactionAmount: $allocation['transaction_amount'],
                    baseCurrencyAmount: $allocation['base_currency_amount'],
                    transDate: $transDate,
                    description: _lang('Deffered Invoice Income') . ' #' . $invoice->invoice_number
                );
            }

            $this->upsertRecognitionTransaction(
                invoice: $invoice,
                earning: $earning,
                accountId: (int) $unearnedRevenue->id,
                drCr: 'dr',
                transactionAmount: $transactionAmount,
                baseCurrencyAmount: $baseCurrencyAmount,
                transDate: $transDate,
                description: _lang('Deffered Invoice Income') . ' #' . $invoice->invoice_number
            );

            $earning->status = 1;
            $earning->save();

            return 'processed';
        });
    }

    private function buildIncomeAllocations(Invoice $invoice, float $transactionAmount, float $baseCurrencyAmount): array
    {
        $incomeLines = [];

        foreach ($invoice->items as $item) {
            $product = $item->product;
            $lineSubTotal = (float) $item->getRawOriginal('sub_total');

            if ($lineSubTotal <= 0 || ! $product || ! $product->id || empty($product->income_account_id)) {
                continue;
            }

            $incomeLines[] = [
                'account_id' => (int) $product->income_account_id,
                'sub_total' => $lineSubTotal,
            ];
        }

        if ($incomeLines === []) {
            throw new \RuntimeException('No invoice item has a valid income account for revenue recognition.');
        }

        $totalSubTotal = array_sum(array_column($incomeLines, 'sub_total'));

        if ($totalSubTotal <= 0) {
            throw new \RuntimeException('Deferred invoice items have no positive subtotal to allocate.');
        }

        $allocations = [];
        $allocatedTransactionAmount = 0.0;
        $allocatedBaseCurrencyAmount = 0.0;
        $lastIndex = count($incomeLines) - 1;

        foreach ($incomeLines as $index => $line) {
            if ($index === $lastIndex) {
                $lineTransactionAmount = round($transactionAmount - $allocatedTransactionAmount, 8);
                $lineBaseCurrencyAmount = round($baseCurrencyAmount - $allocatedBaseCurrencyAmount, 8);
            } else {
                $ratio = $line['sub_total'] / $totalSubTotal;
                $lineTransactionAmount = round($transactionAmount * $ratio, 8);
                $lineBaseCurrencyAmount = round($baseCurrencyAmount * $ratio, 8);
                $allocatedTransactionAmount += $lineTransactionAmount;
                $allocatedBaseCurrencyAmount += $lineBaseCurrencyAmount;
            }

            if (! isset($allocations[$line['account_id']])) {
                $allocations[$line['account_id']] = [
                    'account_id' => $line['account_id'],
                    'transaction_amount' => 0.0,
                    'base_currency_amount' => 0.0,
                ];
            }

            $allocations[$line['account_id']]['transaction_amount'] = round(
                $allocations[$line['account_id']]['transaction_amount'] + $lineTransactionAmount,
                8
            );
            $allocations[$line['account_id']]['base_currency_amount'] = round(
                $allocations[$line['account_id']]['base_currency_amount'] + $lineBaseCurrencyAmount,
                8
            );
        }

        return array_values($allocations);
    }

    private function getOrCreateUnearnedRevenueAccount(Invoice $invoice): Account
    {
        $businessId = (int) $invoice->business_id;

        $account = Account::query()
            ->where('business_id', $businessId)
            ->where('account_name', 'Unearned Revenue')
            ->first();

        if ($account) {
            return $account;
        }

        $account = new Account();
        $account->account_code = '2300';
        $account->account_name = 'Unearned Revenue';
        $account->account_type = 'Current Liability';
        $account->opening_date = now()->format('Y-m-d');
        $account->opening_balance = 0;
        $account->currency = optional($invoice->business)->currency;
        $account->dr_cr = 'cr';
        $account->business_id = $businessId;
        $account->user_id = $this->resolveUserId($invoice);
        $account->created_user_id = $invoice->created_user_id;
        $account->save();

        return $account;
    }

    private function upsertRecognitionTransaction(
        Invoice $invoice,
        DefferedEarning $earning,
        int $accountId,
        string $drCr,
        float $transactionAmount,
        float $baseCurrencyAmount,
        string $transDate,
        string $description
    ): void {
        $transaction = Transaction::query()->firstOrNew([
            'ref_id' => (string) $invoice->id,
            'ref_type' => self::RECOGNITION_REF_TYPE,
            'reference' => self::REFERENCE_PREFIX . ':' . $earning->id,
            'account_id' => $accountId,
            'dr_cr' => $drCr,
        ]);

        $transaction->trans_date = $transDate;
        if ($this->transactionsHaveTypeColumn()) {
            $transaction->type = 'income';
        }
        $transaction->transaction_amount = $transactionAmount;
        $transaction->transaction_currency = $earning->currency ?: $invoice->currency;
        $transaction->currency_rate = (float) ($earning->getRawOriginal('exchange_rate') ?: $invoice->getRawOriginal('exchange_rate') ?: 1);
        $transaction->base_currency_amount = $baseCurrencyAmount;
        $transaction->description = $description;
        $transaction->customer_id = $invoice->customer_id;
        $transaction->business_id = $invoice->business_id;
        $transaction->user_id = $this->resolveUserId($invoice);
        $transaction->created_user_id = $invoice->created_user_id;
        $transaction->save();
    }

    private function resolveUserId(Invoice $invoice): int
    {
        $userId = $invoice->user_id
            ?: optional($invoice->business)->user_id
            ?: optional(Business::query()->find($invoice->business_id))->user_id
            ?: $invoice->created_user_id;

        if (! $userId) {
            throw new \RuntimeException('Unable to resolve the owning user for deferred invoice recognition.');
        }

        return (int) $userId;
    }

    private function transactionsHaveTypeColumn(): bool
    {
        if ($this->transactionsHaveTypeColumn === null) {
            $this->transactionsHaveTypeColumn = Schema::hasColumn('transactions', 'type');
        }

        return $this->transactionsHaveTypeColumn;
    }

    private function toBaseCurrency(?string $transactionCurrency, ?string $baseCurrency, float $amount): float
    {
        if (! $transactionCurrency || ! $baseCurrency || $transactionCurrency === $baseCurrency || $amount == 0.0) {
            return round($amount, 8);
        }

        return round(convert_currency($transactionCurrency, $baseCurrency, $amount), 8);
    }
}
