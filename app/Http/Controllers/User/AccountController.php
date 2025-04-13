<?php

namespace App\Http\Controllers\User;

use App\Exports\AccountsExport;
use App\Exports\AccountStatement;
use App\Http\Controllers\Controller;
use App\Imports\AccountsImport;
use App\Imports\BankStatementImport;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class AccountController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');

        $query = Account::orderBy("id", "desc");

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                    ->orWhere('account_code', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate($per_page)->withQueryString();
        return Inertia::render('Backend/User/Account/List', [
            'accounts' => $accounts->items(),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'from' => $accounts->firstItem(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $per_page,
                'to' => $accounts->lastItem(),
                'total' => $accounts->total(),
                'links' => $accounts->linkCollection(),
                'path' => $accounts->path(),
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Get currencies for the dropdown
        $currencies = Currency::all();
        $accountTypes = AccountType::all();
        
        return Inertia::render('Backend/User/Account/Create', [
            'currencies' => $currencies,
            'accountTypes' => $accountTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'account_code'    => 'required|unique:accounts,account_code,NULL,id,business_id,' . $request->activeBusiness->id,
            'account_name'    => 'required|max:255',
            'account_type'    => 'required',
            'opening_date'    => 'required|date',
            'account_number'  => 'nullable|max:50',
            'currency'        => 'nullable',
            'description'     => 'nullable',
            'opening_balance' => 'nullable|numeric|min:0',
        ], [
            'account_code.unique'   => 'Account Code is already taken',
        ]);

        if (($request->account_type == 'Bank' || $request->account_type == 'Cash') && $request->currency == null) {
            return redirect()->back()->withErrors(['currency' => 'Please Select Currency'])->withInput();
        }

        if (($request->account_type !== 'Bank' && $request->account_type !== 'Cash' && $request->account_type !== 'Other Current Asset' && $request->account_type !== 'Fixed Asset') && $request->opening_balance > 0) {
            return redirect()->back()->withErrors(['opening_balance' => 'Only Assets Can Have Opening Balance'])->withInput();
        }

        if (!Account::where('account_name', 'Common Shares')->where('business_id', $request->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '3000';
            $account->account_name    = 'Common Shares';
            $account->account_type    = 'Equity';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = $request->activeBusiness->id;
            $account->user_id         = $request->activeBusiness->user->id;
            $account->dr_cr           = 'cr';
            $account->save();
        }

        DB::beginTransaction();

        $currentTime = Carbon::now();

        $account                  = new Account();
        $account->account_code    = $request->input('account_code');
        $account->account_name    = $request->input('account_name');
        $account->account_type    = $request->input('account_type');
        $account->opening_date    = Carbon::parse($request->input('opening_date'))->format('Y-m-d');
        $account->account_number  = $request->input('account_number');
        $account->currency        = $request->input('currency');
        $account->description     = $request->input('description');
        $account->user_id         = auth()->id();
        $account->business_id     = $request->activeBusiness->id;
        if ($request->account_type == 'Bank' || $request->account_type == 'Cash' || $request->account_type == 'Other Current Asset' || $request->account_type == 'Cost Of Sale' || $request->account_type == 'Fixed Asset' || $request->account_type == 'Direct Expenses' || $request->account_type == 'Other Expenses') {
            $account->dr_cr = 'dr';
        } else {
            $account->dr_cr = 'cr';
        }
        $account->save();

        $account->currency = $account->currency . ' (' . currency_symbol($account->currency) . ')';

        if ($request->opening_balance > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('opening_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Common Shares')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->ref_type    = 'open';
            $transaction->ref_id      = $account->id;
            $transaction->transaction_amount      = $request->opening_balance;
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = $request->opening_balance;
            $transaction->description = $account->account_name . ' Opening Balance';
            $transaction->save();

            if ($account->dr_cr == 'cr') {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('opening_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $account->id;
                $transaction->dr_cr       = 'cr';
                $transaction->ref_type    = 'open';
                $transaction->ref_id      = $account->id;
                $transaction->transaction_amount      = $request->opening_balance;
                $transaction->transaction_currency    = $request->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = $request->opening_balance;
                $transaction->description = _lang('Account Opening Balance');
                $transaction->save();
            } else {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('opening_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $account->id;
                $transaction->dr_cr       = 'dr';
                $transaction->ref_type    = 'open';
                $transaction->ref_id      = $account->id;
                $transaction->transaction_amount      = $request->opening_balance;
                $transaction->transaction_currency    = $request->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = $request->opening_balance;
                $transaction->description = _lang('Account Opening Balance');
                $transaction->save();
            }
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Created Account ' . $account->account_name;
        $audit->save();

        return redirect()->route('accounts.index')->with('success', 'Account created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $account = Account::where('id', $id)->with([
            'transactions' => function ($query) {
                $query->orderBy('trans_date', 'desc');
            }
        ])
            ->find($id);

        $normal_transactions = $account->transactions()
            ->whereNotIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'payslip', 'd invoice income', 'd invoice tax'])
            ->get();

        $get_payment_income_transactions = $account->transactions()
            ->whereIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'd invoice income', 'd invoice tax'])
            ->get(); // Fetch the transactions as a collection

        $payment_income_transactions = $get_payment_income_transactions->groupBy(function ($transaction) {
            // Check if the ref_id contains a comma
            if (strpos($transaction->ref_id, ',') !== false) {
                // Extract the second part of ref_id
                return explode(',', $transaction->ref_id)[1];
            }
            // Otherwise, return the ref_id as is
            return $transaction->ref_id;
        });

        $payslip_transactions = $account->transactions()
            ->where('ref_type', 'payslip')
            ->get()
            ->groupBy('trans_date');

        $combined_transactions = $this->combine($payment_income_transactions, $payslip_transactions, $normal_transactions);

        return Inertia::render('Backend/User/Account/Show', [
            'account' => $account,
            'combined_transactions' => $combined_transactions,
        ]);
    }

    function combine($payment_income_transactions, $payslip_transactions, $normal_transactions)
    {
        // Step 1: Aggregate payment transactions
        $aggregated_payment_income_transactions = $payment_income_transactions->map(function ($group, $key) {
            $totalAmount = $group->sum('base_currency_amount');
            $trans_date  = $group[0]->trans_date;
            $dr_cr  = $group[0]->dr_cr;
            $ref_type = $group[0]->ref_type;
            $ref_id = $group[0]->ref_id;
            $count = $group->count();
            if ($ref_type == 'invoice payment' || $ref_type == 'd invoice payment') {
                $description = $count . ' Invoices Payment';
            } else if ($ref_type == 'bill payment') {
                $description = $count . ' Bills Payment';
            } else if ($ref_type == 'd invoice income') {
                $description = 'Deffered Earnings Income From Invoice #' . Invoice::find($ref_id)->invoice_number;
            } else if ($ref_type == 'd invoice tax') {
                $description = 'Deffered Tax From Invoice #' . Invoice::find($ref_id)?->invoice_number;
            }
            return (object) [
                'trans_date' => $trans_date,
                'description' => "{$description}",
                'base_currency_amount' => $totalAmount,
                'dr_cr' => $dr_cr,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'payee_name' => $group[0]->payee_name,
                'group_key' => $key, // Optional: Keep the group key for reference if needed
            ];
        })->values(); // Flatten the results into a list

        // Step 5: Aggregate payslip transactions
        $aggregated_payslip_transactions = $payslip_transactions->map(function ($group, $key) {
            $totalAmount = $group->sum('base_currency_amount');
            $trans_date  = $group[0]->trans_date;
            $dr_cr  = $group[0]->dr_cr;
            $ref_type = $group[0]->ref_type;
            $ref_id = $group[0]->ref_id;
            $count = $group->count();
            return (object) [
                'trans_date' => $trans_date,
                'description' => "{$count}, Staffs Salary",
                'base_currency_amount' => $totalAmount,
                'dr_cr' => $dr_cr,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'payee_name' => $group[0]->payee_name,
                'group_key' => $key, // Optional: Keep the group key for reference if needed
            ];
        })->values(); // Flatten the results into a list

        // Step 6: Combine all aggregated transactions
        $combined_transactions = collect()
            ->merge($aggregated_payment_income_transactions)
            ->merge($aggregated_payslip_transactions);

        // Step 7: Include normal transactions as is
        $normal_transactions_summary = $normal_transactions->map(function ($transaction) {
            return (object) [
                'trans_date' => $transaction->trans_date,
                'description' => $transaction->description,
                'base_currency_amount' => $transaction->base_currency_amount,
                'ref_type' => $transaction->ref_type,
                'ref_id' => $transaction->ref_id,
                'payee_name' => $transaction->payee_name,
                'currency' => $transaction->transaction_currency,
                'dr_cr' => $transaction->dr_cr,
            ];
        });

        // Step 8: Combine everything into a single collection
        $final_collection = $combined_transactions->merge($normal_transactions_summary);

        // Output or use the $final_collection
        return $final_collection;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $account = Account::find($id);

        $openingBalance = Transaction::where('account_id', $id)
            ->where('ref_type', 'open')
            ->sum('transaction_amount');

        // Get currencies for the dropdown
        $currencies = Currency::all();
        
        return Inertia::render('Backend/User/Account/Edit', [
            'account' => $account,
            'openingBalance' => $openingBalance,
            'currencies' => $currencies,
        ]);
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
        $request->validate([
            'account_code'   => 'required|unique:accounts,account_code,' . $id . ',id,business_id,' . $request->activeBusiness->id,
            'account_name'   => 'required|max:255',
            'opening_date'   => 'required|date',
            'account_number' => 'nullable|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'currency'       => 'nullable',
            'description'    => 'nullable',
        ], [
            'account_code.unique' => 'Account Code is already taken',
        ]);

        if (($request->account_type == 'Bank' || $request->account_type == 'Cash') && $request->currency == null) {
            return redirect()->back()->withErrors(['currency' => 'Please Select Currency'])->withInput();
        }

        if (($request->account_type !== 'Bank' && $request->account_type !== 'Cash' && $request->account_type !== 'Other Current Asset' && $request->account_type !== 'Fixed Asset') && $request->opening_balance > 0) {
            return redirect()->back()->withErrors(['opening_balance' => 'Only Assets Can Have Opening Balance'])->withInput();
        }

        if (!Account::where('account_name', 'Common Shares')->where('business_id', $request->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '3000';
            $account->account_name    = 'Common Shares';
            $account->account_type    = 'Equity';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = $request->activeBusiness->id;
            $account->user_id         = $request->activeBusiness->user->id;
            $account->dr_cr           = 'cr';
            $account->save();
        }

        $account                 = Account::find($id);
        $account->account_code   = $request->input('account_code');
        $account->account_type   = $request->input('account_type');
        $account->account_name   = $request->input('account_name');
        $account->opening_date   = Carbon::parse($request->input('opening_date'))->format('Y-m-d');
        $account->account_number = $request->input('account_number');
        $account->currency       = $request->input('currency');
        $account->description    = $request->input('description');
        if ($request->account_type == 'Bank' || $request->account_type == 'Cash' || $request->account_type == 'Other Current Asset' || $request->account_type == 'Cost Of Sale' || $request->account_type == 'Fixed Asset' || $request->account_type == 'Direct Expenses' || $request->account_type == 'Other Expenses') {
            $account->dr_cr = 'dr';
        } else {
            $account->dr_cr = 'cr';
        }
        $account->save();
        $account->currency = $account->currency . ' (' . currency_symbol($account->currency) . ')';

        $currentTime = Carbon::now();

        $openingBalance = Transaction::where('account_id', $id)
            ->where('ref_type', 'open')
            ->sum('transaction_amount');

        if ($openingBalance == null) {
            if ($request->opening_balance > 0) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('opening_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Common Shares')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->ref_type    = 'open';
                $transaction->ref_id      = $account->id;
                $transaction->transaction_amount      = $request->opening_balance;
                $transaction->transaction_currency    = $request->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = $request->opening_balance;
                $transaction->description = $account->account_name . ' Opening Balance';
                $transaction->save();
                if ($account->dr_cr == 'cr') {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('opening_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $account->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->ref_type    = 'open';
                    $transaction->transaction_amount      = $request->opening_balance;
                    $transaction->transaction_currency    = $request->activeBusiness->currency;
                    $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $request->opening_balance;
                    $transaction->description = _lang('Account Opening Balance');

                    $transaction->save();
                } else {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('opening_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $account->id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->ref_type    = 'open';
                    $transaction->transaction_amount      = $request->opening_balance;
                    $transaction->transaction_currency    = $request->activeBusiness->currency;
                    $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $request->opening_balance;
                    $transaction->description = _lang('Account Opening Balance');

                    $transaction->save();
                }
            }
        } else {
            if ($openingBalance != $request->opening_balance) {
                $transaction = Transaction::where('account_id', $id)
                    ->where('ref_type', 'open')
                    ->first();

                $transaction2 = Transaction::where('account_id', get_account('Common Shares')->id)
                    ->where('ref_id', $id)
                    ->where('ref_type', 'open')
                    ->first();

                $transaction2->transaction_amount         = $request->opening_balance;
                $transaction2->base_currency_amount       = $request->opening_balance;
                $transaction2->save();

                if ($account->dr_cr == 'cr') {
                    $transaction->transaction_amount      = $request->opening_balance;
                    $transaction->base_currency_amount    = $request->opening_balance;
                    $transaction->save();
                } else {
                    $transaction->transaction_amount      = $request->opening_balance;
                    $transaction->base_currency_amount    = $request->opening_balance;
                    $transaction->save();
                }

                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Updated Account ' . $account->account_name;
        $audit->save();

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully');
    }

    public function convert_due_amount(Request $request, $accountId, $amount)
    {
        $account      = Account::find($accountId);
        $rawAmount    = convert_currency($request->activeBusiness->currency, $account->currency, $amount);
        $formatAmount = formatAmount($rawAmount, currency_symbol($account->currency));
        return response()->json(['rawAmount' => $rawAmount, 'formatAmount' => $formatAmount]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $account = Account::find($id);
        if ($account->transactions->count() > 0) {
            return redirect()->back()->withErrors(['delete' => 'This Account has transactions. Please delete them first!']);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Deleted Account ' . $account->account_name;
        $audit->save();

        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully');
    }

    public function destroyMultiple(Request $request)
    {
        $accountIds = $request->accounts;
        $deletedCount = 0;
        $failedCount = 0;
        
        foreach ($accountIds as $id) {
            $account = Account::find($id);
            
            if (!$account) {
                $failedCount++;
                continue;
            }
            
            if ($account->transactions->count() > 0) {
                $failedCount++;
                continue;
            }
            
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->id();
            $audit->event = 'Deleted Account ' . $account->account_name;
            $audit->save();
            
            $account->delete();
            $deletedCount++;
        }
        
        if ($failedCount > 0) {
            return back()->with('warning', $deletedCount . ' accounts deleted successfully. ' . $failedCount . ' accounts could not be deleted because they have transactions.');
        }
        
        return back()->with('success', $deletedCount . ' accounts deleted successfully');
    }

    public function importStatement(Request $request)
    {

        $import = new BankStatementImport($request->id);

        Excel::import($import, $request->statement_file);

        return redirect()->route('accounts.show', $request->id)->with('success', 'Statement Imported Successfully');
    }

    public function import_accounts(Request $request)
    {
        $request->validate([
            'accounts_file' => 'required|mimes:xls,xlsx',
        ]);

        Excel::import(new AccountsImport, $request->file('accounts_file'));

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Imported Accounts';
        $audit->save();

        return redirect()->route('chart_of_accounts.list_chart_of_accounts')->with('success', _lang('Accounts Imported'));
    }

    public function export_accounts()
    {
        return Excel::download(new AccountsExport, 'chart_of_accounts.xlsx');

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->id();
        $audit->event = 'Exported Accounts';
        $audit->save();
    }

    public function account_statement(Request $request, $id)
    {
        if ($request->isMethod('get')) {

            $date1 = $request->has('date1') ? Carbon::parse($request->date1)->format('Y-m-d') : Carbon::now()->subDays(30)->format('Y-m-d');
            $date2 = $request->has('date2') ? Carbon::parse($request->date2)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

            session(['start_date' => $date1]);
            session(['end_date' => $date2]);

            $account = Account::where('id', $id)->first();

            $normal_transactions = $account->transactions()
                ->whereNotIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'payslip', 'd invoice income', 'd invoice tax'])
                ->whereDate('trans_date', '>=', $date1)
                ->whereDate('trans_date', '<=', $date2)
                ->get();

            $get_payment_income_transactions = $account->transactions()
                ->whereIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'd invoice income', 'd invoice tax'])
                ->whereDate('trans_date', '>=', $date1)
                ->whereDate('trans_date', '<=', $date2)
                ->get(); // Fetch the transactions as a collection

            $payment_income_transactions = $get_payment_income_transactions->groupBy(function ($transaction) {
                // Check if the ref_id contains a comma
                if (strpos($transaction->ref_id, ',') !== false) {
                    // Extract the second part of ref_id
                    return explode(',', $transaction->ref_id)[1];
                }
                // Otherwise, return the ref_id as is
                return $transaction->ref_id;
            });

            $payslip_transactions = $account->transactions()
                ->where('ref_type', 'payslip')
                ->whereDate('trans_date', '>=', $date1)
                ->whereDate('trans_date', '<=', $date2)
                ->get()
                ->groupBy('trans_date');

            $combined_transactions = $this->combine($payment_income_transactions, $payslip_transactions, $normal_transactions);
            
            // Sort transactions by date
            $combined_transactions = $combined_transactions->sortByDesc('trans_date');
            
            // Calculate running balance
            $opening_balance = $account->opening_balance;
            $running_balance = [];
            $current_balance = $opening_balance;
            
            foreach ($combined_transactions as $key => $transaction) {
                if ($transaction->dr_cr == 'dr') {
                    $current_balance += $transaction->base_currency_amount;
                } else {
                    $current_balance -= $transaction->base_currency_amount;
                }
                $running_balance[$key] = $current_balance;
            }
            
            $balances = [
                'opening' => $opening_balance,
                'closing' => end($running_balance) ?: $opening_balance,
                'running' => $running_balance
            ];

            $date1Obj = Carbon::parse($date1);
            $date2Obj = Carbon::parse($date2);
            
            $dateRange = [
                'from' => $date1Obj->format('Y-m-d'),
                'to' => $date2Obj->format('Y-m-d')
            ];

            // Return Inertia view with JSON data
            return Inertia::render('Backend/User/Account/AccountStatement', [
                'account' => $account,
                'transactions' => $combined_transactions->values(),
                'balances' => $balances,
                'dateRange' => $dateRange
            ]);
        } else {
            $request->validate([
                'date1' => 'required',
                'date2' => 'required',
            ]);

            $date1 = Carbon::parse($request->date1)->format('Y-m-d');
            $date2 = Carbon::parse($request->date2)->format('Y-m-d');

            session(['start_date' => $date1]);
            session(['end_date' => $date2]);
            
            return redirect()->route('accounts.account_statement', ['id' => $id, 'date1' => $date1, 'date2' => $date2]);
        }
    }

    public function export_account_statement($id)
    {
        $account = Account::find($id);
        return Excel::download(new AccountStatement(session('start_date'), session('end_date'), $id), $account->account_name . '_statement.xlsx');
    }
}
