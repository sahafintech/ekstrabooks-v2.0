<?php

namespace App\Cronjobs;

use App\Models\Account;
use App\Models\DefferedEarning;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;

class DefferedInvoiceTask
{
    public function __invoke()
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $invoices = Invoice::where('is_deffered', 1)
            ->limit(10)
            ->get();

        $currentTime = Carbon::now();

        foreach ($invoices as $invoice) {
            $defferedEarning = DefferedEarning::where('invoice_id', $invoice->id)->get();

            foreach ($defferedEarning as $earning) {
                if ($earning->end_date <= date('Y-m-d') && $earning->status == 0) {

                    foreach ($invoice->items as $item) {
                        $product = $item->product;

                        if ($product->allow_for_selling == 1) {

                            $transaction              = Transaction::where('business_id', $invoice->business_id)->where('ref_type', 'd invoice')->first()->replicate();
                            $transaction->trans_date  = Carbon::parse($earning->end_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                            $transaction->account_id  = $product->income_account_id;
                            $transaction->dr_cr       = 'cr';
                            $transaction->transaction_currency    = $invoice->currency;
                            $transaction->currency_rate = $invoice->exchange_rate;
                            $transaction->base_currency_amount = $earning->base_currency_amount;
                            $transaction->transaction_amount      = $earning->transaction_amount;
                            $transaction->description = _lang('Deffered Invoice Income') . ' #' . $invoice->invoice_number;
                            $transaction->ref_id      = $invoice->id;
                            $transaction->ref_type    = 'd invoice income';
                            $transaction->save();

                            $transaction              = Transaction::where('business_id', $invoice->business_id)->where('ref_type', 'd invoice')->first()->replicate();
                            $transaction->trans_date  = Carbon::parse($earning->end_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                            $transaction->account_id  = Account::where('account_name', 'Unearned Revenue')->where('business_id', $invoice->business_id)->first()->id;
                            $transaction->dr_cr       = 'dr';
                            $transaction->transaction_currency    = $invoice->currency;
                            $transaction->currency_rate = $invoice->exchange_rate;
                            $transaction->base_currency_amount = $earning->base_currency_amount;
                            $transaction->transaction_amount      = $earning->transaction_amount;
                            $transaction->description = _lang('Deffered Invoice Income') . ' #' . $invoice->invoice_number;
                            $transaction->ref_id      = $invoice->id;
                            $transaction->ref_type    = 'd invoice income';
                            $transaction->save();

                            $earning->status = 1;
                            $earning->save();
                        }
                    }
                }
            }
        }
    }
}
