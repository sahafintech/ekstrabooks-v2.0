<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BillPayment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BillPaymentsController extends Controller
{
    public function index()
    {
        $bill_payments = BillPayment::with('purchases')->get();
        return view('backend.user.purchase.pay_bills.payments', compact('bill_payments'));
    }

    public function create()
    {
        return view('backend.user.purchase.pay_bills.add-payment');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'required',
            'vendor_id' => 'required',
            'attachment' => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            }
        }

        if ($request->invoices == null) {
            return redirect()->back()->with('error', _lang('Please Select At Least One Invoice'));
        }

        for ($i = 0; $i < count($request->invoices); $i++) {

            $purchase = Purchase::find($request->invoices[$i]);

            if ($request->amount[$request->invoices[$i]] > ($purchase->grand_total - $purchase->paid)) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }

            if ($request->amount[$request->invoices[$i]] <= 0) {
                return redirect()->back()->with('error', _lang('Amount must be greater than 0'));
            }
        }

        $amount = 0;

        foreach ($request->invoices as $invoice) {
            $amount += $request->amount[$invoice];
        }

        $payment = new BillPayment();
        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        if ($request->vendor_id != 'all') {
            $payment->vendor_id = $request->vendor_id;
        } else {
            $payment->vendor_id = null;
        }
        $payment->reference = $request->reference;
        $payment->save();

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->invoices); $i++) {
            DB::beginTransaction();

            $purchase = Purchase::find($request->invoices[$i]);

            $purchase_payment = new PurchasePayment();
            $purchase_payment->purchase_id = $request->invoices[$i];
            $purchase_payment->payment_id = $payment->id;
            $purchase_payment->amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            $purchase_payment->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]);
            $transaction->transaction_currency    = $purchase->currency;
            $transaction->currency_rate           = $purchase->exchange_rate;
            $transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i] . ',' . $payment->id;
            $transaction->ref_type    = 'bill payment';
            $transaction->vendor_id = $request->vendor_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Payable')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]);
            $transaction->transaction_currency    = $purchase->currency;
            $transaction->currency_rate           = $purchase->exchange_rate;
            $transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i] . ',' . $payment->id;
            $transaction->ref_type    = 'bill payment';
            $transaction->vendor_id = $request->vendor_id;
            $transaction->save();

            $purchase->paid   = $purchase->paid + convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            $purchase->status = 1; //Partially Paid
            if ($purchase->paid >= $purchase->grand_total) {
                $purchase->status = 2; //Paid
            }
            $purchase->save();

            DB::commit();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Bill Payment Created for ' . $purchase->bill_no;
        $audit->save();

        return redirect()->route('bill_payments.index')->with('success', _lang('Payment Made Successfully'));
    }

    public function edit(Request $request, $id)
    {
        $payment = BillPayment::where('id', $id)->with('purchases')->first();

        return view('backend.user.purchase.pay_bills.edit', compact('payment', 'id'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'required',
            'customer_id' => 'required',
            'attachment' => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            }
        }

        if ($request->invoices == null) {
            return redirect()->back()->with('error', _lang('Please Select At Least One Invoice'));
        }

        $payment = BillPayment::find($id);

        for ($i = 0; $i < count($request->invoices); $i++) {
            DB::beginTransaction();

            $purchase = Purchase::find($request->invoices[$i]);

            $old_purchase_payment = PurchasePayment::where('purchase_id', $request->invoices[$i])->where('payment_id', $payment->id)->first();

            if ($request->amount[$request->invoices[$i]] > ($purchase->grand_total - ($purchase->paid - $old_purchase_payment->amount))) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }

            if ($request->amount[$request->invoices[$i]] <= 0) {
                return redirect()->back()->with('error', _lang('Amount must be greater than 0'));
            }
        }

        $amount = 0;

        foreach ($request->invoices as $invoice) {
            $amount += $request->amount[$invoice];
        }

        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        $payment->vendor_id = $request->vendor_id;
        $payment->reference = $request->reference;
        $payment->save();

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->invoices); $i++) {
            DB::beginTransaction();

            $purchase = Purchase::find($request->invoices[$i]);

            $old_purchase_payment = PurchasePayment::where('purchase_id', $request->invoices[$i])->where('payment_id', $payment->id)->first();

            $purchase->paid   = $purchase->paid - $old_purchase_payment->amount;
            if ($purchase->paid >= $purchase->grand_total) {
                $purchase->status = 2; //Paid
            } else if ($purchase->paid == 0) {
                $purchase->status = 0; //Unpaid
            } else if ($purchase->paid < $purchase->grand_total) {
                $purchase->status = 1; //Partially Paid
            }
            $purchase->save();

            $old_purchase_payment->delete();

            $transactions = Transaction::where('ref_id', $request->invoices[$i])->where('ref_type', 'bill payment')->get();

            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $purchase_payment = new PurchasePayment();
            $purchase_payment->purchase_id = $request->invoices[$i];
            $purchase_payment->payment_id = $payment->id;
            $purchase_payment->amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            $purchase_payment->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]);
            $transaction->transaction_currency    = $purchase->currency;
            $transaction->currency_rate           = $purchase->exchange_rate;
            $transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i] . ',' . $payment->id;
            $transaction->ref_type    = 'bill payment';
            $transaction->vendor_id = $request->vendor_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Payable')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]);
            $transaction->transaction_currency    = $purchase->currency;
            $transaction->currency_rate           = $purchase->exchange_rate;
            $transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i] . ',' . $payment->id;
            $transaction->ref_type    = 'bill payment';
            $transaction->vendor_id = $request->vendor_id;
            $transaction->save();

            DB::commit();

            $purchase->paid   = $purchase->paid + convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
            if ($purchase->paid >= $purchase->grand_total) {
                $purchase->status = 2; //Paid
            } else if ($purchase->paid == 0) {
                $purchase->status = 0; //Unpaid
            } else if ($purchase->paid < $purchase->grand_total) {
                $purchase->status = 1; //Partially Paid
            }
            $purchase->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Bill Payment Updated for ' . $purchase->bill_no;
        $audit->save();

        return redirect()->route('bill_payments.index')->with('success', _lang('Payment Updated Successfully'));
    }

    public function destroy(Request $request, $id)
    {
        $payment = BillPayment::find($id);
        $purchase_payments = PurchasePayment::where('payment_id', $id)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Bill Payment Deleted for ' . $payment->id;
        $audit->save();

        foreach ($purchase_payments as $purchase_payment) {
            $purchase = Purchase::find($purchase_payment->purchase_id);

            $purchase->paid = $purchase->paid - $purchase_payment->amount;
            $purchase->status = 1; //Partially Paid
            if ($purchase->paid >= $purchase->grand_total) {
                $purchase->status = 2; //Paid
            } else if ($purchase->paid == 0) {
                $purchase->status = 0; //Unpaid
            }
            $purchase->save();


            $transaction = Transaction::where('ref_id', $purchase->id . ',' . $payment->id)->where('ref_type', 'bill payment')->get();

            foreach ($transaction as $trans) {
                $trans->delete();
            }

            $purchase_payment->delete();
        }

        $payment->delete();

        return redirect()->route('bill_payments.index')->with('success', _lang('Payment Deleted Successfully'));
    }

    public function show(Request $request, $id)
    {
        $payment = BillPayment::where('id', $id)->with('purchases', 'vendor')->first();

        return view('backend.user.purchase.pay_bills.view', compact('payment', 'id'));
    }
}
