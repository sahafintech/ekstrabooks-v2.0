<?php

namespace App\Http\Controllers;

use App\Exports\JournalExport;
use App\Http\Middleware\Business;
use App\Imports\JournalImport;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\PendingTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class JournalController extends Controller
{
    public function index()
    {
        $journals = Journal::all();
        return view('backend.user.journal.list', compact('journals'));
    }

    public function create()
    {
        return view('backend.user.journal.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required',
            'journal_number' => 'required',
            'trans_currency' => 'required',
            'journal_entry.account_id' => 'required',
        ]);

        $month = Carbon::parse($request->date)->format('F');
        $year = Carbon::parse($request->date)->format('Y');
        $today = now()->format('d');

        // financial year
        $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
        $end_month = explode(',', $financial_year)[1];
        $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
        $end_day = $start_day + 5;

        // if login as this user dont check the financial year
        if (false) {
            if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
            }
        }

        $currentTime = Carbon::now();

        if ($request->journal_entry['account_id'][0] == 0) {
            return redirect()->back()->with('error', _lang('Choose At Least one account'));
        }

        $journal = new Journal();
        $journal->date = Carbon::parse($request->input('date'))->format('Y-m-d');
        $journal->journal_number = $request->journal_number;
        $journal->transaction_currency = $request->trans_currency;
        $journal->currency_rate = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
        $journal->transaction_amount      = array_sum($request->journal_entry['debit']);
        $journal->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, array_sum($request->journal_entry['debit']));
        $journal->user_id = auth()->user()->id;
        $journal->business_id = request()->activeBusiness->id;
        $journal->created_by = auth()->user()->id;
        if (has_permission('journals.approve') || request()->isOwner == true) {
            $journal->status = 1;
        } else {
            $journal->status = 0;
        }
        $journal->save();

        //increment journal number
        BusinessSetting::where('name', 'journal_number')->increment('value');

        for ($i = 0; $i < count($request->journal_entry['account_id']); $i++) {
            DB::beginTransaction();

            $month = Carbon::parse($request->journal_entry['date'][$i])->format('F');
            $year = Carbon::parse($request->journal_entry['date'][$i])->format('Y');
            $today = now()->format('d');

            // financial year
            $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
            $end_month = explode(',', $financial_year)[1];
            $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
            $end_day = $start_day + 5;

            // if login as this user dont check the financial year
            if (false) {
                if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                    return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
                }
            }

            if (has_permission('journals.approve') || request()->isOwner == true) {
                $transaction                              = new Transaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entry['date'][$i])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entry['account_id'][$i];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entry['debit'][$i] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entry['debit'][$i];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['debit'][$i]);
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entry['credit'][$i];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['credit'][$i]);
                }
                $transaction->description = $request->journal_entry['description'][$i];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->journal_entry['customer_id'][$i] ?? NULL;
                $transaction->vendor_id = $request->journal_entry['vendor_id'][$i] ?? NULL;
                $transaction->save();
            } else {
                $transaction                              = new PendingTransaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entry['date'][$i])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entry['account_id'][$i];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entry['debit'][$i] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entry['debit'][$i];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['debit'][$i]);
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entry['credit'][$i];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['credit'][$i]);
                }
                $transaction->description = $request->journal_entry['description'][$i];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->customer_id ?? NULL;
                $transaction->vendor_id = $request->vendor_id ?? NULL;
                $transaction->save();
            }

            DB::commit();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Journal Entry Created: ' . $journal->journal_number;
        $audit->save();

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Created'));
    }

    public function edit(Request $request, $id)
    {

        $journal = Journal::find($id);
        if ($journal->status == 1) {
            $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();
        } else {
            $transactions = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();
        }

        return view('backend.user.journal.edit', compact('journal', 'transactions', 'id'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required',
            'journal_number' => 'required',
            'trans_currency' => 'required',
        ]);

        $month = Carbon::parse($request->date)->format('F');
        $year = Carbon::parse($request->date)->format('Y');
        $today = now()->format('d');

        // financial year
        $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
        $end_month = explode(',', $financial_year)[1];
        $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
        $end_day = $start_day + 5;

        // if login as this user dont check the financial year
        if (false) {
            if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
            }
        }

        $currentTime = Carbon::now();

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $journal = Journal::find($id);
        $journal->date = Carbon::createFromFormat(get_date_format(), $journal->date)->format('Y-m-d');
        $journal->transaction_currency = $request->trans_currency;
        $journal->currency_rate = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
        $journal->transaction_amount      = array_sum($request->journal_entry['debit']);
        $journal->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, array_sum($request->journal_entry['debit']));
        $journal->save();

        $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();
        $pending_transaction = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

        foreach ($transactions as $trans) {
            $trans->delete();
        }

        foreach ($pending_transaction as $trans) {
            $trans->delete();
        }

        for ($i = 0; $i < count($request->journal_entry['account_id']); $i++) {
            DB::beginTransaction();

            $month = Carbon::parse($request->journal_entry['date'][$i])->format('F');
            $year = Carbon::parse($request->journal_entry['date'][$i])->format('Y');
            $today = now()->format('d');

            // financial year
            $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
            $end_month = explode(',', $financial_year)[1];
            $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
            $end_day = $start_day + 5;

            // if login as this user dont check the financial year
            if (false) {
                if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                    return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
                }
            }

            if (has_permission('journals.approve') || (request()->isOwner == true && $journal->status == 1)) {
                $transaction                              = new Transaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entry['date'][$i])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entry['account_id'][$i];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entry['debit'][$i] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entry['debit'][$i];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['debit'][$i]);
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entry['credit'][$i];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['credit'][$i]);
                }
                $transaction->description = $request->journal_entry['description'][$i];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->journal_entry['customer_id'][$i] ?? NULL;
                $transaction->vendor_id = $request->journal_entry['vendor_id'][$i] ?? NULL;
                $transaction->save();
            } else {
                $transaction                              = new PendingTransaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entry['date'][$i])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entry['account_id'][$i];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entry['debit'][$i] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entry['debit'][$i];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['debit'][$i]);
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entry['credit'][$i];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entry['credit'][$i]);
                }
                $transaction->description = $request->journal_entry['description'][$i];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->journal_entry['customer_id'][$i] ?? NULL;
                $transaction->vendor_id = $request->journal_entry['vendor_id'][$i] ?? NULL;
                $transaction->save();
            }

            DB::commit();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Journal Entry Updated: ' . $journal->journal_number;
        $audit->save();

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Updated'));
    }

    public function destroy($id)
    {
        $journal = Journal::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Journal Entry Deleted: ' . $journal->journal_number;
        $audit->save();

        $transaction = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

        foreach ($transaction as $trans) {
            $trans->delete();
        }

        $journal->delete();

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Deleted'));
    }

    public function import_journal(Request $request)
    {
        $request->validate([
            'journal_file' => 'required|mimes:xls,xlsx',
            'date' => 'required',
            'trans_currency' => 'required',
        ]);

        $file = $request->file('journal_file');
        try {
            Excel::import(new JournalImport($request->trans_currency, $request->date), $file);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Journal Entry Imported';
        $audit->save();

        // if session has not error then journal imported successfully
        if (session()->has('error')) {
            return redirect()->route('journals.index');
        } else {
            return redirect()->route('journals.index')->with('success', _lang('Journal Entry Imported'));
        }
    }

    public function show($id)
    {
        $journal = Journal::where('id', $id)->with('created_user', 'approved_user')->first();
        $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();
        $pending_transactions = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

        return view('backend.user.journal.view', compact('journal', 'transactions', 'id', 'pending_transactions'));
    }

    public function export_journal($id)
    {
        return Excel::download(new JournalExport($id), 'journal ' .  now()->format('d m Y')  .  '.xlsx');
    }

    public function journals_all(Request $request)
    {
        if ($request->journals == null) {
            return redirect()->route('journals.index')->with('error', _lang('Please Select Journal'));
        }

        $journals = Journal::whereIn('id', $request->journals)->get();

        if ($request->type == 'delete') {
            foreach ($journals as $journal) {

                $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

                foreach ($transactions as $trans) {
                    $trans->delete();
                }

                $journal->delete();
            }
        } else if ($request->type == 'approve') {
            foreach ($journals as $journal) {
                $this->approve_journal($journal->id);
            }
        } else if ($request->type == 'reject') {
            foreach ($journals as $journal) {
                $this->reject_journal($journal->id);
            }
        } else {
            return redirect()->route('journals.index')->with('error', _lang('Invalid Action'));
        }

        return redirect()->route('journals.index')->with('success', _lang('Action Performed Successfully'));
    }

    public function approve_journal($id)
    {
        $journal = Journal::find($id);
        $journal->status = 1;
        $journal->approved_by = auth()->user()->id;
        $journal->save();

        // transactions from pending to transaction
        $pending_transactions = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

        $currentTime = Carbon::now();

        if ($pending_transactions->count() > 0) {
            foreach ($pending_transactions as $transaction) {
                // Create a new Transaction instance and replicate data from pending
                $new_transaction = $transaction->replicate();
                $new_transaction->setTable('transactions'); // Change the table to 'transactions'
                $new_transaction->save();

                // Delete the pending transaction
                $transaction->delete();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Journal Entry Approved ' . $journal->journal_number;
        $audit->save();

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Approved'));
    }

    public function reject_journal($id)
    {
        $journal = Journal::find($id);

        if ($journal->status == 1) {
            // transactions from transactions to pending
            $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

            $currentTime = Carbon::now();

            if ($transactions->count() > 0) {
                foreach ($transactions as $transaction) {
                    // Create a new PendingTransaction instance and replicate data from transaction
                    $new_transaction = $transaction->replicate();
                    $new_transaction->setTable('pending_transactions'); // Change the table to 'pending_transactions'
                    $new_transaction->save();

                    // Delete the transaction
                    $transaction->delete();
                }
            }
        }

        $journal->status = 2;
        $journal->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Journal Entry Rejected ' . $journal->journal_number;
        $audit->save();

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Rejected'));
    }
}
