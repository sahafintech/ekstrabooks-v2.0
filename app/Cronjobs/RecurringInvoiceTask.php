<?php

namespace App\Cronjobs;

use App\Models\Invoice;
use App\Models\BusinessSetting;
use App\Notifications\NewInvoiceCreated;

class RecurringInvoiceTask {

    public function __invoke() {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $invoices = Invoice::where('is_recurring', 1)
            ->whereDate('recurring_invoice_date', '<=', date('Y-m-d'))
            ->limit(10)
            ->get();

        foreach($invoices as $invoice){

            if($invoice->recurring_end < date('Y-m-d')){
                $invoice->status = 2;
                $invoice->save();
                continue;
            }

            $newInvoice                         = $invoice->replicate();
            $newInvoice->status                 = 1;
            $newInvoice->invoice_number         = get_business_option('invoice_number', rand());
            $newInvoice->invoice_date           = $invoice->getRawOriginal('recurring_invoice_date');
            $newInvoice->due_date               = date("Y-m-d", strtotime($invoice->getRawOriginal('recurring_invoice_date') . ' ' . $invoice->getRawOriginal('recurring_due_date')));
            $newInvoice->is_recurring           = 0;
            $newInvoice->recurring_completed    = 0;
            $newInvoice->recurring_start        = null;
            $newInvoice->recurring_end          = null;
            $newInvoice->recurring_value        = null;
            $newInvoice->recurring_invoice_date = null;
            $newInvoice->recurring_due_date     = null;
            $newInvoice->short_code             = rand(100000, 9999999) . uniqid();
            $newInvoice->parent_id              = $invoice->id;
            $newInvoice->save();

            foreach ($invoice->items as $invoiceItem) {
                $newInvoiceItem             = $invoiceItem->replicate();
                $newInvoiceItem->invoice_id = $newInvoice->id;
                $newInvoiceItem->save();

                foreach ($invoiceItem->taxes as $InvoiceItemTax) {
                    $newInvoiceItemTax                  = $InvoiceItemTax->replicate();
                    $newInvoiceItemTax->invoice_id      = $newInvoice->id;
                    $newInvoiceItemTax->invoice_item_id = $newInvoiceItem->id;
                    $newInvoiceItemTax->save();
                }

                //Update Stock
                $product = $invoiceItem->product;
                if ($product->type == 'product' && $product->stock_management == 1) {
                    $product->stock = $product->stock - $newInvoiceItem->quantity;
                    $product->save();
                }
            }

            //Increment Invoice Number
            BusinessSetting::where('name', 'invoice_number')->increment('value');

            //Update Next Invoice Date
            $invoice->recurring_invoice_date = date("Y-m-d", strtotime($invoice->getRawOriginal('recurring_invoice_date') . ' +' . $invoice->recurring_value . ' ' . $invoice->recurring_type));
            $invoice->save();

            try {
                $newInvoice->customer->notify(new NewInvoiceCreated($newInvoice));
            } catch (\Exception $e) {
                // Nothing
            }
        }

    }

}