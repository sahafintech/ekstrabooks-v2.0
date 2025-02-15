<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DefferedPayment;
use App\Models\DefferedReceivePayment;
use App\Models\Invoice;
use App\Models\ReceivePayment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DefferedPaymentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'required',
            'customer_id' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            }
        }

        if ($request->payments == null) {
            return redirect()->back()->withInput()->with('error', _lang('Please Select At Least One Payment'));
        }

        for ($i = 0; $i < count($request->payments); $i++) {
            $d_payment = DefferedPayment::find($request->payments[$i]);
            $invoice = Invoice::find($request->invoices[$i]);

            if ($request->amount[$request->payments[$i]] > ($d_payment->amount - $d_payment->paid)) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }
        }

        $amount = 0;

        foreach ($request->payments as $payment) {
            $amount += $request->amount[$payment];
        }

        $payment = new ReceivePayment();
        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        $payment->customer_id = $request->customer_id;
        $payment->reference = $request->reference;
        $payment->type = 'offline';
        $payment->deffered_payment = 1;
        $payment->save();

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->payments); $i++) {
            DB::beginTransaction();

            $d_payment = DefferedPayment::find($request->payments[$i]);
            $invoice = Invoice::find($request->invoices[$i]);

            if ($request->amount[$request->payments[$i]] > ($d_payment->amount - $d_payment->paid)) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }

            $invoice_payment = new DefferedReceivePayment();
            $invoice_payment->payment_id = $payment->id;
            $invoice_payment->deffered_payment_id = $d_payment->id;
            $invoice_payment->amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $invoice_payment->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Deferred Invoice Payment') . ' #' . $request->invoices[$i];
            $transaction->ref_id      = $invoice->id . ',' . $request->payments[$i];
            $transaction->ref_type    = 'd invoice payment';
            $transaction->customer_id = $request->customer_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Receivable')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Deferred Invoice Payment') . ' #' . $request->payments[$i];
            $transaction->ref_id      = $invoice->id . ',' . $request->payments[$i];
            $transaction->ref_type    = 'd invoice payment';
            $transaction->save();

            $invoice->paid   = $invoice->paid + convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            }
            $invoice->save();

            $d_payment->paid   = $d_payment->paid + convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $d_payment->status = 1; //Partially Paid
            if ($d_payment->paid >= $d_payment->amount) {
                $d_payment->status = 2; //Paid
            }
            $d_payment->save();

            DB::commit();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deffered Payment Created: ' . $payment->id;
        $audit->save();

        return redirect()->route('deffered_invoices.index')->with('success', _lang('Payment Received Successfully'));
    }

    public function destroy($id)
    {
        $payment = ReceivePayment::find($id);

        $deffered_receive_payments = DefferedReceivePayment::where('payment_id', $payment->id)->get();

        foreach ($deffered_receive_payments as $deffered_receive_payment) {

            $d_payment = DefferedPayment::find($deffered_receive_payment->deffered_payment_id);

            $invoice = Invoice::find($d_payment->invoice_id);

            $transaction = Transaction::where('ref_id', $invoice->id . ',' .$d_payment->id)->where('ref_type', 'd invoice payment')->get();

            foreach ($transaction as $value) {
                $value->delete();
            }

            $invoice->paid = $invoice->paid - $deffered_receive_payment->amount;
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            } else if ($invoice->paid == 0) {
                $invoice->status = 1; //Unpaid
            }
            $invoice->save();

            $d_payment->paid   = $d_payment->paid - $deffered_receive_payment->amount;
            if ($d_payment->paid == 0) {
                $d_payment->status = 0; //Unpaid
            } else if ($d_payment->paid < $d_payment->amount) {
                $d_payment->status = 1; //Partially Paid
            } else if ($d_payment->paid == $d_payment->amount) {
                $d_payment->status = 2; //Paid
            }
            $d_payment->save();
        }

        $payment->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deffered Payment Deleted: ' . $payment->id;
        $audit->save();

        return redirect()->route('deffered_invoices.index')->with('success', _lang('Payment Deleted Successfully'));
    }

    public function edit(Request $request, $id)
    {
        $payment = ReceivePayment::where('id', $id)->with('defferedReceivePayment')->first();

        return view('backend.user.invoice.deffered.tabs.edit_payment', compact('payment', 'id'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'required',
            'customer_id' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            }
        }

        $amount = 0;

        if ($request->payments == null) {
            return redirect()->back()->withInput()->with('error', _lang('Please Select At Least One Payment'));
        }

        $payment = ReceivePayment::find($id);

        for ($i = 0; $i < count($request->payments); $i++) {
            DB::beginTransaction();

            $d_payment = DefferedPayment::find($request->payments[$i]);
            $invoice = Invoice::find($request->invoices[$i]);

            $d_receive_payment = DefferedReceivePayment::where('payment_id', $payment->id)->where('deffered_payment_id', $d_payment->id)->first();

            if ($request->amount[$request->payments[$i]] > ($d_payment->amount - ($d_receive_payment->amount - $d_payment->paid))) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }
        }

        foreach ($request->payments as $payment) {
            $amount += $request->amount[$payment];
        }

        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        $payment->customer_id = $request->customer_id;
        $payment->reference = $request->reference;
        $payment->type = 'offline';
        $payment->deffered_payment = 1;
        $payment->save();

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->payments); $i++) {
            DB::beginTransaction();

            $d_payment = DefferedPayment::find($request->payments[$i]);
            $invoice = Invoice::find($request->invoices[$i]);

            $d_receive_payment = DefferedReceivePayment::where('payment_id', $payment->id)->where('deffered_payment_id', $d_payment->id)->first();

            if ($request->amount[$request->payments[$i]] > ($d_payment->amount - ($d_receive_payment->amount - $d_payment->paid))) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }


            $d_payment->paid   = $d_payment->paid - convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $d_receive_payment->amount));
            $d_payment->status = 1; //Partially Paid
            if ($d_payment->paid >= $d_payment->amount) {
                $d_payment->status = 2; //Paid
            }
            $d_payment->save();

            $invoice->paid   = $invoice->paid - convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $d_receive_payment->amount));
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            }
            $invoice->save();


            $d_receive_payment->delete();

            $transaction = Transaction::where('ref_id', $d_payment->id)->where('ref_type', 'd invoice payment')->get();

            foreach ($transaction as $value) {
                $value->delete();
            }

            $invoice_payment = new DefferedReceivePayment();
            $invoice_payment->payment_id = $payment->id;
            $invoice_payment->deffered_payment_id = $d_payment->id;
            $invoice_payment->amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $invoice_payment->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Deferred Invoice Payment') . ' #' . $request->invoices[$i];
            $transaction->ref_id      = $request->payments[$i];
            $transaction->ref_type    = 'd invoice payment';
            $transaction->customer_id = $request->customer_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Receivable')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Deferred Invoice Payment') . ' #' . $request->payments[$i];
            $transaction->ref_id      = $request->payments[$i];
            $transaction->ref_type    = 'd invoice payment';
            $transaction->save();

            $invoice->paid   = $invoice->paid + convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            }
            $invoice->save();

            $d_payment->paid   = $d_payment->paid + convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->amount[$request->payments[$i]]));
            $d_payment->status = 1; //Partially Paid
            if ($d_payment->paid >= $d_payment->amount) {
                $d_payment->status = 2; //Paid
            }
            $d_payment->save();

            DB::commit();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deffered Payment Updated: ' . $payment->id;
        $audit->save();

        return redirect()->route('deffered_invoices.index')->with('success', _lang('Payment Received Successfully'));
    }
}
