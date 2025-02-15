<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\SalesReturn;
use App\Models\Transaction;
use App\Models\UncategorizedTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UncategorizedTransactionController extends Controller
{
    public function store(Request $request)
    {
        return $request;
        
        $uncategorized_transaction = UncategorizedTransaction::find($request->id);


        $validator = Validator::make($request->all(), [
            'method'     => 'required',
            'attachment' => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            }
        }

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->invoices); $i++) {
            DB::beginTransaction();

            $invoice = Invoice::find($request->invoices[$i]);
            $account = Account::find($uncategorized_transaction->account_id);

            $refAmount = convert_currency($account->currency, $request->activeBusiness->currency, $request->amount[$request->invoices[$i]]);

            if ($refAmount > ($invoice->grand_total - $invoice->paid)) {
                return response()->json(['result' => 'error', 'message' => _lang('Amount must be equal or less than due amount')]);
            }

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::createFromFormat('d/m/Y', $uncategorized_transaction->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $uncategorized_transaction->account_id;
            $transaction->method      = $request->method;
            $transaction->dr_cr       = 'dr';
            $transaction->amount      = $request->amount[$request->invoices[$i]];
            $transaction->ref_amount  = $refAmount;
            $transaction->reference   = $uncategorized_transaction->reference;
            $transaction->description = $uncategorized_transaction->description;
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i];
            $transaction->ref_type    = 'invoice';
            $transaction->customer_id = $request->customer_id;

            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::now()->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Receivable')->id;
            $transaction->method      = $request->method;
            $transaction->dr_cr       = 'cr';
            $transaction->amount      = $request->amount[$request->invoices[$i]];
            $transaction->ref_amount  = $refAmount;
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Invoice Payment') . ' #' . $request->invoices[$i];
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i];
            $transaction->ref_type    = 'invoice';

            $transaction->save();

            $invoice->paid   = $invoice->paid + $transaction->ref_amount;
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            }
            $invoice->save();

            DB::commit();
        }

        $uncategorized_transaction->delete();

        return redirect()->back()->with('success', _lang('Categorized Successfully'));
    }
}
