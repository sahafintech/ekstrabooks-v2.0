<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\UncategorizedTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class TransactionController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $transactions = Transaction::select('transactions.*')
            ->with('category', 'account')
            ->orderBy("transactions.id", "desc")
            ->get();
        return view('backend.user.transaction.list', compact('transactions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.transaction.modal.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trans_date'              => 'required',
            'account_id'              => 'required',
            'method'                  => 'required',
            'type'                    => 'required',
            'transaction_category_id' => 'required',
            'amount'                  => 'required|numeric',
            'attachment'              => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('transactions.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $transaction                          = new Transaction();
        $transaction->trans_date              = $request->input('trans_date');
        $transaction->transaction_category_id = $request->input('transaction_category_id');
        $transaction->account_id              = $request->input('account_id');
        $transaction->method                  = $request->method;
        $transaction->dr_cr                   = $request->type == 'income' ? 'cr' : 'dr';
        $transaction->type                    = $request->type;
        $transaction->amount                  = $request->input('amount');
        $transaction->reference               = $request->input('reference');
        $transaction->description             = $request->input('description');
        $transaction->attachment              = $attachment;

        $transaction->save();

        if (!$request->ajax()) {
            return redirect()->route('transactions.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $transaction, 'table' => '#expenses_table']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $transaction = Transaction::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.transaction.modal.view', compact('transaction', 'id'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $transaction = Transaction::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.transaction.modal.edit', compact('transaction', 'id'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'trans_date'              => 'required',
            'transaction_category_id' => 'nullable',
            'account_id'              => 'required',
            'method'                  => 'required',
            'amount'                  => 'required|numeric',
            'attachment'              => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('transactions.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        DB::beginTransaction();

        $transaction = Transaction::find($id);

        if ($transaction->ref_type == 'invoice') {
            $invoice       = Invoice::find($transaction->ref_id);
            $invoice->paid = $invoice->paid - $transaction->ref_amount;
        }

        if ($transaction->ref_type == 'purchase') {
            $purcahse       = Purchase::find($transaction->ref_id);
            $purcahse->paid = $purcahse->paid - $transaction->ref_amount;
        }

        $transaction->trans_date              = $request->input('trans_date');
        $transaction->transaction_category_id = $request->input('transaction_category_id');
        $transaction->account_id              = $request->input('account_id');
        $transaction->method                  = $request->method;
        $transaction->amount                  = $request->input('amount');
        if ($transaction->ref_id != null) {
            $transaction->ref_amount = convert_currency($transaction->account->currency, $request->activeBusiness->currency, $transaction->amount);
        }
        $transaction->reference   = $request->input('reference');
        $transaction->description = $request->input('description');
        if ($request->hasfile('attachment')) {
            $transaction->attachment = $attachment;
        }

        $transaction->save();

        if ($transaction->ref_type == 'invoice') {
            $invoice->paid   = $invoice->paid + $transaction->ref_amount;
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            }
            $invoice->save();
        }

        if ($transaction->ref_type == 'purchase') {
            $purcahse->paid   = $purcahse->paid + $transaction->ref_amount;
            $purcahse->status = 1; //Partially Paid
            if ($purcahse->paid >= $purcahse->grand_total) {
                $purcahse->status = 2; //Paid
            }
            $purcahse->save();
        }

        DB::commit();

        if (!$request->ajax()) {
            return redirect()->route('transactions.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $transaction, 'table' => '#expenses_table']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::begintransaction();

        $transaction = Transaction::find($id);
        $transaction->delete();

        DB::commit();

        return redirect()->route('transactions.index')->with('success', _lang('Deleted Successfully'));
    }

    public function categorize_transactions(Request $request){
        $transaction = UncategorizedTransaction::find($request->id);
        return view('backend.user.transaction.categorize', compact('transaction'));
    }

    public function approve_transactions() {
        // Use for Permission Only
		return redirect()->route('journal.index');
    }
}
