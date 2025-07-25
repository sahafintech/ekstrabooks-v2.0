<?php

namespace App\Http\Controllers;

use App\Exports\JournalExport;
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
use Inertia\Inertia;
use App\Models\Account;
use App\Models\CostCode;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Vendor;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 50);
        $status = $request->input('status', '');

        $query = Journal::query()
            ->where('business_id', request()->activeBusiness->id)
            ->with('created_user', 'approved_user');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('journal_number', 'like', "%$search%")
                    ->orWhere('transaction_amount', 'like', "%$search%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Get summary statistics for all journals matching filters
        $allJournals = Journal::query()
            ->where('business_id', request()->activeBusiness->id);

        if (!empty($search)) {
            $allJournals->where(function ($q) use ($search) {
                $q->where('journal_number', 'like', "%$search%")
                    ->orWhere('transaction_amount', 'like', "%$search%");
            });
        }

        if ($status) {
            $allJournals->where('status', $status);
        }

        $allJournals = $allJournals->get();

        $summary = [
            'total_journals' => $allJournals->count(),
            'total_approved' => $allJournals->where('status', 1)->count(),
            'total_pending' => $allJournals->where('status', 0)->count(),
            'total_rejected' => $allJournals->where('status', 2)->count(),
            'total_amount' => $allJournals->sum('base_currency_amount'),
        ];

        $journals = $query->paginate($perPage);

        return Inertia::render('Backend/User/Journal/List', [
            'journals' => $journals->items(),
            'currencies' => Currency::all(),
            'meta' => [
                'total' => $journals->total(),
                'per_page' => $journals->perPage(),
                'current_page' => $journals->currentPage(),
                'last_page' => $journals->lastPage(),
                'from' => $journals->firstItem(),
                'to' => $journals->lastItem()
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
                'status' => $status
            ],
            'summary' => $summary
        ]);
    }

    public function create()
    {
        $accounts = Account::all();
        $currencies = Currency::all();
        $customers = Customer::all();
        $vendors = Vendor::all();
        $journal_number = get_business_option('journal_number');
        $projects = Project::orderBy('id', 'desc')
            ->with('tasks')
            ->get();
        $cost_codes = CostCode::orderBy('id', 'desc')
            ->get();

        return Inertia::render('Backend/User/Journal/Create', [
            'accounts' => $accounts,
            'currencies' => $currencies,
            'customers' => $customers,
            'vendors' => $vendors,
            'journal_number' => $journal_number,
            'base_currency' => get_business_option('currency'),
            'projects' => $projects,
            'cost_codes' => $cost_codes,
            'construction_module' => package()->construction_module
        ]);
    }

    public function store(Request $request)
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

        foreach ($request->journal_entries as $entry) {
            if ($entry['account_id'] == null) {
                return redirect()->back()->with('error', _lang('Please select an account'));
            }

            if ($entry['debit'] == null && $entry['credit'] == null) {
                return redirect()->back()->with('error', _lang('Please enter a debit or credit amount'));
            }

            if ($entry['date'] == null) {
                return redirect()->back()->with('error', _lang('Please select a date'));
            }
            
        }

        $currentTime = Carbon::now();

        $journal = new Journal();
        $journal->date = Carbon::parse($request->input('date'))->format('Y-m-d');
        $journal->journal_number = $request->journal_number;
        $journal->transaction_currency = $request->trans_currency;
        $journal->currency_rate = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
        $journal->transaction_amount      = array_sum(array_map(function ($entry) {
            return floatval($entry['debit']);
        }, $request->journal_entries));
        $journal->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $journal->transaction_amount);
        $journal->user_id = auth()->user()->id;
        $journal->business_id = request()->activeBusiness->id;
        $journal->created_by = auth()->user()->id;
        if (has_permission('journals.bulk_approve') || request()->isOwner == true) {
            $journal->status = 1;
        } else {
            $journal->status = 0;
        }
        $journal->save();

        //increment journal number
        BusinessSetting::where('name', 'journal_number')->increment('value');

        for ($i = 0; $i < count($request->journal_entries); $i++) {
            DB::beginTransaction();

            $month = Carbon::parse($request->journal_entries[$i]['date'])->format('F');
            $year = Carbon::parse($request->journal_entries[$i]['date'])->format('Y');
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

            if (has_permission('journals.bulk_approve') || request()->isOwner == true) {
                $transaction                              = new Transaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entries[$i]['date'])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entries[$i]['account_id'];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entries[$i]['debit'] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['debit'];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['debit']);

                    if ($request->journal_entries[$i]['project_id'] && $request->journal_entries[$i]['project_task_id'] && $request->journal_entries[$i]['cost_code_id']) {
                        $projectBudget = ProjectBudget::where('project_id', $request->journal_entries[$i]['project_id'])->where('project_task_id', $request->journal_entries[$i]['project_task_id'])->where('cost_code_id', $request->journal_entries[$i]['cost_code_id'])->first();
                        if ($projectBudget) {
                            $projectBudget->actual_budget_quantity += $request->journal_entries[$i]['quantity'];
                            $projectBudget->actual_budget_amount += $request->journal_entries[$i]['debit'];
                            $projectBudget->save();
                        }
                    }
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['credit'];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['credit']);
                }
                $transaction->description = $request->journal_entries[$i]['description'];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->journal_entries[$i]['customer_id'] ?? NULL;
                $transaction->vendor_id = $request->journal_entries[$i]['vendor_id'] ?? NULL;
                $transaction->project_id = $request->journal_entries[$i]['project_id'] ?? NULL;
                $transaction->project_task_id = $request->journal_entries[$i]['project_task_id'] ?? NULL;
                $transaction->cost_code_id = $request->journal_entries[$i]['cost_code_id'] ?? NULL;
                $transaction->quantity = $request->journal_entries[$i]['quantity'] ?? 0;
                $transaction->save();
            } else {
                $transaction                              = new PendingTransaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entries[$i]['date'])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entries[$i]['account_id'];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entries[$i]['debit'] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['debit'];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['debit']);
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['credit'];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['credit']);
                }
                $transaction->description = $request->journal_entries[$i]['description'];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->journal_entries[$i]['customer_id'] ?? NULL;
                $transaction->vendor_id = $request->journal_entries[$i]['vendor_id'] ?? NULL;
                $transaction->project_id = $request->journal_entries[$i]['project_id'] ?? NULL;
                $transaction->project_task_id = $request->journal_entries[$i]['project_task_id'] ?? NULL;
                $transaction->cost_code_id = $request->journal_entries[$i]['cost_code_id'] ?? NULL;
                $transaction->quantity = $request->journal_entries[$i]['quantity'] ?? 0;
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

    public function edit($id)
    {
        $journal = Journal::find($id);
        if ($journal->status == 1) {
            $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();
        } else {
            $transactions = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();
        }

        $accounts = Account::all();
        $currencies = Currency::all();
        $customers = Customer::all();
        $vendors = Vendor::all();
        $projects = Project::orderBy('id', 'desc')
            ->with('tasks')
            ->get();
        $cost_codes = CostCode::orderBy('id', 'desc')
            ->get();

        return Inertia::render('Backend/User/Journal/Edit', [
            'journal' => $journal,
            'transactions' => $transactions,
            'accounts' => $accounts,
            'currencies' => $currencies,
            'customers' => $customers,
            'vendors' => $vendors,
            'projects' => $projects,
            'cost_codes' => $cost_codes,
            'construction_module' => package()->construction_module
        ]);
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
        $journal->transaction_amount      = array_sum(array_map(function ($entry) {
            return floatval($entry['debit']);
        }, $request->journal_entries));
        $journal->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $journal->transaction_amount);
        $journal->save();

        $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();
        $pending_transaction = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

        foreach ($transactions as $trans) {
            if ($trans->project_id && $trans->project_task_id && $trans->cost_code_id && $trans->dr_cr == 'dr') {
                $projectBudget = ProjectBudget::where('project_id', $trans->project_id)->where('project_task_id', $trans->project_task_id)->where('cost_code_id', $trans->cost_code_id)->first();
                if ($projectBudget) {
                    $projectBudget->actual_budget_quantity -= $trans->quantity;
                    $projectBudget->actual_budget_amount -= $trans->transaction_amount;
                    $projectBudget->save();
                }
            }
            $trans->delete();
        }

        foreach ($pending_transaction as $trans) {
            $trans->delete();
        }

        for ($i = 0; $i < count($request->journal_entries); $i++) {
            DB::beginTransaction();

            $month = Carbon::parse($request->journal_entries[$i]['date'])->format('F');
            $year = Carbon::parse($request->journal_entries[$i]['date'])->format('Y');
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

            if (has_permission('journals.bulk_approve') || (request()->isOwner == true && $journal->status == 1)) {
                $transaction                              = new Transaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entries[$i]['date'])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entries[$i]['account_id'];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entries[$i]['debit'] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['debit'];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['debit']);

                    if ($request->journal_entries[$i]['project_id'] && $request->journal_entries[$i]['project_task_id'] && $request->journal_entries[$i]['cost_code_id']) {
                        $projectBudget = ProjectBudget::where('project_id', $request->journal_entries[$i]['project_id'])->where('project_task_id', $request->journal_entries[$i]['project_task_id'])->where('cost_code_id', $request->journal_entries[$i]['cost_code_id'])->first();
                        if ($projectBudget) {
                            $projectBudget->actual_budget_quantity += $request->journal_entries[$i]['quantity'];
                            $projectBudget->actual_budget_amount += $request->journal_entries[$i]['debit'];
                            $projectBudget->save();
                        }
                    }
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['credit'];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['credit']);
                }
                $transaction->description = $request->journal_entries[$i]['description'];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->journal_entries[$i]['customer_id'] ?? NULL;
                $transaction->vendor_id = $request->journal_entries[$i]['vendor_id'] ?? NULL;
                $transaction->project_id = $request->journal_entries[$i]['project_id'] ?? NULL;
                $transaction->project_task_id = $request->journal_entries[$i]['project_task_id'] ?? NULL;
                $transaction->cost_code_id = $request->journal_entries[$i]['cost_code_id'] ?? NULL;
                $transaction->quantity = $request->journal_entries[$i]['quantity'] ?? 0;
                $transaction->save();
            } else {
                $transaction                              = new PendingTransaction();
                $transaction->trans_date                  = Carbon::parse($request->journal_entries[$i]['date'])->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->journal_entries[$i]['account_id'];
                $transaction->transaction_method          = $request->method;
                $transaction->transaction_currency        = $request->trans_currency;
                $transaction->currency_rate               = Currency::where('name', $request->trans_currency)->first()->exchange_rate;
                if ($request->journal_entries[$i]['debit'] > 0) {
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['debit'];
                    $transaction->base_currency_amount    = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['debit']);
                } else {
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = $request->journal_entries[$i]['credit'];
                    $transaction->base_currency_amount = convert_currency($request->trans_currency, $request->activeBusiness->currency, $request->journal_entries[$i]['credit']);
                }
                $transaction->description = $request->journal_entries[$i]['description'];
                $transaction->ref_id      = $journal->id;
                $transaction->ref_type    = 'journal';
                $transaction->customer_id = $request->journal_entries[$i]['customer_id'] ?? NULL;
                $transaction->vendor_id = $request->journal_entries[$i]['vendor_id'] ?? NULL;
                $transaction->project_id = $request->journal_entries[$i]['project_id'] ?? NULL;
                $transaction->project_task_id = $request->journal_entries[$i]['project_task_id'] ?? NULL;
                $transaction->cost_code_id = $request->journal_entries[$i]['cost_code_id'] ?? NULL;
                $transaction->quantity = $request->journal_entries[$i]['quantity'] ?? 0;
                $transaction->save();

                $journal->status = 0;
                $journal->save();
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

            if ($trans->project_id && $trans->project_task_id && $trans->cost_code_id && $trans->dr_cr == 'dr') {
                $projectBudget = ProjectBudget::where('project_id', $trans->project_id)->where('project_task_id', $trans->project_task_id)->where('cost_code_id', $trans->cost_code_id)->first();
                if ($projectBudget) {
                    $projectBudget->actual_budget_quantity -= $trans->quantity;
                    $projectBudget->actual_budget_amount -= $trans->transaction_amount;
                    $projectBudget->save();
                }
            }
        }

        $journal->delete();

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Deleted'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
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

                if ($trans->project_id && $trans->project_task_id && $trans->cost_code_id && $trans->dr_cr == 'dr') {
                    $projectBudget = ProjectBudget::where('project_id', $trans->project_id)->where('project_task_id', $trans->project_task_id)->where('cost_code_id', $trans->cost_code_id)->first();
                    if ($projectBudget) {
                        $projectBudget->actual_budget_quantity -= $trans->quantity;
                        $projectBudget->actual_budget_amount -= $trans->transaction_amount;
                        $projectBudget->save();
                    }
                }
            }

            $journal->delete();
        }

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
        $journal = Journal::where('id', $id)
            ->where('business_id', request()->activeBusiness->id)
            ->with('created_user', 'approved_user')
            ->first();

        if (!$journal) {
            return redirect()->route('journals.index')->with('error', _lang('Journal not found!'));
        }

        $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->with('account', 'customer', 'vendor', 'project', 'project_task', 'cost_code')->get();
        $pending_transactions = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->with('account', 'customer', 'vendor', 'project', 'project_task', 'cost_code')->get();

        return Inertia::render('Backend/User/Journal/View', [
            'journal' => $journal,
            'transactions' => $transactions,
            'pending_transactions' => $pending_transactions
        ]);
    }

    public function export_journal($id)
    {
        return Excel::download(new JournalExport($id), 'journal ' .  now()->format('d m Y')  .  '.xlsx');
    }

    public function bulk_approve(Request $request)
    {
        foreach ($request->ids as $id) {
            $journal = Journal::find($id);
            $journal->status = 1;
            $journal->approved_by = auth()->user()->id;
            $journal->save();

            // transactions from pending to transaction
            $pending_transactions = PendingTransaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

            if ($pending_transactions->count() > 0) {
                foreach ($pending_transactions as $transaction) {
                    // Create a new Transaction instance and replicate data from pending
                    $new_transaction = $transaction->replicate();
                    $new_transaction->setTable('transactions'); // Change the table to 'transactions'
                    $new_transaction->save();

                    if ($new_transaction->project_id && $new_transaction->project_task_id && $new_transaction->cost_code_id && $new_transaction->dr_cr == 'dr') {
                        $projectBudget = ProjectBudget::where('project_id', $new_transaction->project_id)->where('project_task_id', $new_transaction->project_task_id)->where('cost_code_id', $new_transaction->cost_code_id)->first();
                        if ($projectBudget) {
                            $projectBudget->actual_budget_quantity += $new_transaction->quantity;
                            $projectBudget->actual_budget_amount += $new_transaction->transaction_amount;
                            $projectBudget->save();
                        }
                    }

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
        }

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Approved'));
    }

    public function bulk_reject(Request $request)
    {
        foreach ($request->ids as $id) {
            $journal = Journal::find($id);

            if ($journal->status == 1) {
                // transactions from transactions to pending
                $transactions = Transaction::where('ref_id', $journal->id)->where('ref_type', 'journal')->get();

                if ($transactions->count() > 0) {
                    foreach ($transactions as $transaction) {
                        // Create a new PendingTransaction instance and replicate data from transaction
                        $new_transaction = $transaction->replicate();
                        $new_transaction->setTable('pending_transactions'); // Change the table to 'pending_transactions'
                        $new_transaction->save();

                        if ($transaction->project_id && $transaction->project_task_id && $transaction->cost_code_id && $transaction->dr_cr == 'dr') {
                            $projectBudget = ProjectBudget::where('project_id', $transaction->project_id)->where('project_task_id', $transaction->project_task_id)->where('cost_code_id', $transaction->cost_code_id)->first();
                            if ($projectBudget) {
                                $projectBudget->actual_budget_quantity -= $transaction->quantity;
                                $projectBudget->actual_budget_amount -= $transaction->transaction_amount;
                                $projectBudget->save();
                            }
                        }

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
        }

        return redirect()->route('journals.index')->with('success', _lang('Journal Entry Rejected'));
    }
}
