<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProjectSubcontract;
use App\Models\ProjectSubcontractPayment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ProjectSubcontractPaymentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'account_id' => 'required',
            'method'     => 'nullable',
            'reference'  => 'nullable',
            'amount'     => 'required',
            'project_subcontract_id' => 'required',
            'vendor_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $payment = new ProjectSubcontractPayment();
        $contract = ProjectSubcontract::find($request->project_subcontract_id);

        if ($request->amount > ($contract->grand_total - $contract->paid)) {
            return redirect()->back()->withErrors(['payment_amount' => _lang('Payment amount cannot be greater than the paid amount')])->withInput();
        }

        $payment->date = Carbon::parse($request->date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->project_subcontract_id = $request->project_subcontract_id;
        $payment->payment_method = $request->method;
        $payment->amount = $request->amount;
        $payment->vendor_id = $request->vendor_id;
        $payment->reference = $request->reference;
        $payment->created_by = auth()->user()->id;
        $payment->save();

        $currentTime = Carbon::now();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = $request->account_id;
        $transaction->transaction_method      = $request->method;
        $transaction->dr_cr       = 'cr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount);
        $transaction->transaction_currency    = $contract->currency;
        $transaction->currency_rate           = $contract->exchange_rate;
        $transaction->base_currency_amount = convert_currency($contract->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount));
        $transaction->reference   = $request->reference;
        $transaction->description = _lang('Project Subcontract Payment') . ' #' . $contract->subcontract_no;
        $transaction->ref_id      = $contract->id;
        $transaction->ref_type    = 'project subcontract payment';
        $transaction->vendor_id = $request->vendor_id;
        $transaction->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
        $transaction->account_id  = get_account('Accounts Payable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount);
        $transaction->transaction_currency    = $contract->currency;
        $transaction->currency_rate           = $contract->exchange_rate;
        $transaction->base_currency_amount = convert_currency($contract->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount));
        $transaction->reference   = $request->reference;
        $transaction->description = _lang('Project Subcontract Payment') . ' #' . $contract->subcontract_no;
        $transaction->ref_id      = $contract->id;
        $transaction->ref_type    = 'project subcontract payment';
        $transaction->vendor_id = $request->vendor_id;
        $transaction->save();

        $contract->paid   = $contract->paid + convert_currency($contract->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount));
        $contract->status = 1; //Partially Paid
        if ($contract->paid >= $contract->grand_total) {
            $contract->status = 2; //Paid
        }
        $contract->save();

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Project Subcontract Payment Created for ' . $contract->subcontract_no;
        $audit->save();

        return redirect()->back()->with('success', _lang('Payment Made Successfully'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'account_id' => 'required',
            'method'     => 'nullable',
            'reference'  => 'nullable',
            'amount'     => 'required',
            'project_subcontract_id' => 'required',
            'vendor_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $contract = ProjectSubcontract::find($request->project_subcontract_id);
        $payment = ProjectSubcontractPayment::findOrFail($id);

        if ($request->amount > ($contract->grand_total - $contract->paid)) {
            return redirect()->back()->withErrors(['payment_amount' => _lang('Payment amount cannot be greater than the paid amount')])->withInput();
        }

        // Update payment details
        $payment->date = Carbon::parse($request->date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $request->amount;
        $payment->project_subcontract_id = $request->project_subcontract_id;
        $payment->vendor_id = $request->vendor_id;
        $payment->reference = $request->reference;
        $payment->updated_by = auth()->user()->id;
        $payment->save();

        $currentTime = Carbon::now();

        // destroy old transaction
        $transaction = Transaction::where('ref_id', $payment->project_subcontract_id . ',' . $payment->id)->where('ref_type', 'project subcontract payment')->get();
        foreach ($transaction as $trans) {
            $trans->delete();
        }
        // Reset project subcontract paid amount

        $contract->paid = $contract->paid - $payment->amount;
        $contract->status = $this->calculateProjectSubcontractStatus($contract->paid, $contract->grand_total);
        $contract->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = $request->account_id;
        $transaction->transaction_method      = $request->method;
        $transaction->dr_cr       = 'cr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount);
        $transaction->transaction_currency    = $contract->currency;
        $transaction->currency_rate           = $contract->exchange_rate;
        $transaction->base_currency_amount = convert_currency($contract->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount));
        $transaction->reference   = $request->reference;
        $transaction->description = _lang('Project Subcontract Payment') . ' #' . $contract->subcontract_no;
        $transaction->ref_id      = $contract->id;
        $transaction->ref_type    = 'project subcontract payment';
        $transaction->vendor_id = $request->vendor_id;
        $transaction->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
        $transaction->account_id  = get_account('Accounts Payable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount);
        $transaction->transaction_currency    = $contract->currency;
        $transaction->currency_rate           = $contract->exchange_rate;
        $transaction->base_currency_amount = convert_currency($contract->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $contract->currency, $request->amount));
        $transaction->reference   = $request->reference;
        $transaction->description = _lang('Project Subcontract Payment') . ' #' . $contract->subcontract_no;
        $transaction->ref_id      = $contract->id;
        $transaction->ref_type    = 'project subcontract payment';
        $transaction->vendor_id = $request->vendor_id;
        $transaction->save();


        // Create audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Project Subcontract Payment Updated for ' . $contract->subcontract_no;
        $audit->save();

        DB::commit();
        return redirect()->back()->with('success', _lang('Payment Updated Successfully'));
    }

    private function calculateProjectSubcontractStatus($paid, $grand_total)
    {
        if ($paid >= $grand_total) {
            return 2; // Paid
        } elseif ($paid == 0) {
            return 0; // Unpaid
        } else {
            return 1; // Partially Paid
        }
    }

    public function destroy($id)
    {
        $payment = ProjectSubcontractPayment::find($id);
        $payment->deleted_by = auth()->user()->id;
        $payment->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Project Subcontract Payment Deleted for ' . $payment->id;
        $audit->save();

        $contract = ProjectSubcontract::find($payment->project_subcontract_id);
        $contract->paid = $contract->paid - $payment->amount;
        $contract->status = $this->calculateProjectSubcontractStatus($contract->paid, $contract->grand_total);
        $contract->save();


        $transaction = Transaction::where('ref_id', $contract->id)->where('ref_type', 'project subcontract payment')->get();

        foreach ($transaction as $trans) {
            $trans->delete();
        }

        $payment->delete();

        return redirect()->back()->with('success', _lang('Payment Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $payment = ProjectSubcontractPayment::find($id);
            $payment->deleted_by = auth()->user()->id;
            $payment->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Project Subcontract Payment Deleted for ' . $payment->id;
            $audit->save();

            $contract = ProjectSubcontract::find($payment->project_subcontract_id);
            $contract->paid = $contract->paid - $payment->amount;
            $contract->status = $this->calculateProjectSubcontractStatus($contract->paid, $contract->grand_total);
            $contract->save();

            $transaction = Transaction::where('ref_id', $contract->id . ',' . $payment->id)->where('ref_type', 'project subcontract payment')->get();
            foreach ($transaction as $trans) {
                $trans->delete();
            }

            $payment->delete();
        }

        return redirect()->back()->with('success', _lang('Payment Deleted Successfully'));
    }

    public function show($id)
    {
        $payment = ProjectSubcontractPayment::where('id', $id)->with('project_subcontract.project', 'account', 'vendor', 'business')->first();

        return Inertia::render('Backend/User/Construction/Project/Subcontract/PaymentVoucher', [
            'payment' => $payment,
        ]);
    }
}
