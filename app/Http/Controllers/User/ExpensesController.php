<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    public function index()
    {
        $expenses = Expense::all();
        return view('backend.user.expenses.list', compact('expenses'));
    }

    public function create()
    {
        return view('backend.user.expenses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'date' => 'required',
            'payment_account' => 'required',
            'expense_account' => 'required',
            'description' => 'required',
            'amount' => 'required',
        ]);

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $currentTime = Carbon::now();

        $expense = new Expense();
        $expense->vendor_id = $request->vendor_id;
        $expense->date = Carbon::parse($request->date)->format('Y-m-d');
        $expense->payment_account = $request->payment_account;
        $expense->expense_account = $request->expense_account;
        $expense->method = $request->method;
        $expense->description = $request->description;
        $expense->amount = $request->amount;
        $expense->user_id = auth()->user()->id;
        $expense->business_id = request()->activeBusiness->id;
        $expense->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = $request->payment_account;
        $transaction->method      = $request->method;
        $transaction->dr_cr       = 'cr';
        $transaction->amount      = $request->amount;
        $transaction->reference   = $request->reference;
        $transaction->description = $request->description;
        $transaction->attachment  = $attachment;
        $transaction->type        = 'expense';
        $transaction->ref_id      = $expense->id;
        $transaction->ref_type    = 'expense';
        $transaction->vendor_id   = $request->vendor_id ?? null;
        $transaction->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = $request->expense_account;
        $transaction->dr_cr       = 'dr';
        $transaction->amount      = $request->amount;
        $transaction->description = $request->description;
        $transaction->ref_id      = $expense->id;
        $transaction->ref_type    = 'expense';
        $transaction->save();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'status' => 'success', 'message' => _lang('Expense Created'), 'load' => true]);
        } else {
            return redirect()->route('expenses.index')->with('success', _lang('Expense Created'));
        }
    }
}
