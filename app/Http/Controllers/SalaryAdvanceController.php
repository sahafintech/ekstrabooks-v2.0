<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\PendingTransaction;
use App\Models\SalaryAdvance;
use App\Models\Transaction;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class SalaryAdvanceController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('salary_advances.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = SalaryAdvance::with('employee');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('amount', 'like', "%{$search}%")
                    ->orWhere('date', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Get customers with pagination
        $salaryAdvances = $query->paginate($per_page)->withQueryString();

        $years = range(date('Y') - 5, date('Y') + 1);

        // Return Inertia view
        return Inertia::render('Backend/User/SalaryAdvance/List', [
            'salaryAdvances' => $salaryAdvances->items(),
            'meta' => [
                'current_page' => $salaryAdvances->currentPage(),
                'from' => $salaryAdvances->firstItem(),
                'last_page' => $salaryAdvances->lastPage(),
                'links' => $salaryAdvances->linkCollection(),
                'path' => $salaryAdvances->path(),
                'per_page' => $salaryAdvances->perPage(),
                'to' => $salaryAdvances->lastItem(),
                'total' => $salaryAdvances->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
            'trashed_salaryAdvances' => SalaryAdvance::onlyTrashed()->count(),
            'employees' => Employee::select('id', 'name')->get(),
            'accounts' => Account::select('id', 'account_name')->get(),
            'years' => $years,
            'currencies' => Currency::select('name', 'description', 'exchange_rate')->get(),
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('salary_advances.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = SalaryAdvance::onlyTrashed()->with('employee');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('amount', 'like', "%{$search}%")
                    ->orWhere('date', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Get customers with pagination
        $salaryAdvances = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/SalaryAdvance/Trash', [
            'salaryAdvances' => $salaryAdvances->items(),
            'meta' => [
                'current_page' => $salaryAdvances->currentPage(),
                'from' => $salaryAdvances->firstItem(),
                'last_page' => $salaryAdvances->lastPage(),
                'links' => $salaryAdvances->linkCollection(),
                'path' => $salaryAdvances->path(),
                'per_page' => $salaryAdvances->perPage(),
                'to' => $salaryAdvances->lastItem(),
                'total' => $salaryAdvances->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('salary_advances.create');
        $request->validate([
            'employee_id' => 'required',
            'amount' => 'required',
            'date' => 'required',
            'payment_account_id' => 'required',
            'advance_account_id' => 'required',
            'payroll_month' => 'required',
            'payroll_year' => 'required',
            'notes' => 'nullable',
        ]);

        DB::beginTransaction();

        $salaryAdvance = new SalaryAdvance();
        $salaryAdvance->employee_id = $request->employee_id;
        $salaryAdvance->date = $request->date;
        $salaryAdvance->amount = $request->amount;
        $salaryAdvance->currency = $request->currency;
        $salaryAdvance->exchange_rate = Currency::where('name', $request->currency)->first()->exchange_rate;
        $salaryAdvance->payment_account_id = $request->payment_account_id;
        $salaryAdvance->advance_account_id = $request->advance_account_id;
        $salaryAdvance->payroll_month = $request->payroll_month;
        $salaryAdvance->payroll_year = $request->payroll_year;
        $salaryAdvance->notes = $request->notes;
        if (has_permission('salary_advances.bulk_approve') || request()->isOwner) {
            $salaryAdvance->approval_status = 1;
        } else {
            $salaryAdvance->approval_status = 0;
        }
        $salaryAdvance->save();

        $currentTime = Carbon::now();

        if (has_permission('bill_invoices.bulk_approve') || request()->isOwner) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->payment_account_id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance payment';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->advance_account_id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();
        } else {
            $transaction              = new PendingTransaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->payment_account_id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance payment';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();

            $transaction              = new PendingTransaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->advance_account_id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Salary Advance ' . $salaryAdvance->id;
        $audit->save();

        return redirect()->route('salary_advances.index')->with('success', 'Salary advance created successfully.');
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('salary_advances.update');
        $request->validate([
            'employee_id' => 'required',
            'amount' => 'required',
            'date' => 'required',
            'payment_account_id' => 'required',
            'advance_account_id' => 'required',
            'payroll_month' => 'required',
            'payroll_year' => 'required',
            'notes' => 'nullable',
        ]);

        DB::beginTransaction();

        $salaryAdvance = SalaryAdvance::find($id);
        $salaryAdvance->employee_id = $request->employee_id;
        $salaryAdvance->date = $request->date;
        $salaryAdvance->amount = $request->amount;
        $salaryAdvance->currency = $request->currency;
        $salaryAdvance->exchange_rate = Currency::where('name', $request->currency)->first()->exchange_rate;
        $salaryAdvance->payment_account_id = $request->payment_account_id;
        $salaryAdvance->advance_account_id = $request->advance_account_id;
        $salaryAdvance->payroll_month = $request->payroll_month;
        $salaryAdvance->payroll_year = $request->payroll_year;
        $salaryAdvance->notes = $request->notes;
        if (has_permission('salary_advances.bulk_approve') || request()->isOwner) {
            $salaryAdvance->approval_status = 1;
        } else {
            $salaryAdvance->approval_status = 0;
        }
        $salaryAdvance->save();

        $currentTime = Carbon::now();

        // delete existing transactions
        Transaction::where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->delete();
        PendingTransaction::where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->delete();

        if (has_permission('bill_invoices.bulk_approve') || request()->isOwner) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->payment_account_id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance payment';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->advance_account_id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();
        } else {
            $transaction              = new PendingTransaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->payment_account_id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance payment';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();

            $transaction              = new PendingTransaction();
            $transaction->trans_date  = Carbon::parse($request->input('date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->advance_account_id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $salaryAdvance->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $request->amount));
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $request->amount);
            $transaction->description = _lang('Salary Advance') . ' #' . $salaryAdvance->id;
            $transaction->ref_id      = $salaryAdvance->id;
            $transaction->ref_type    = 'salary advance';
            $transaction->employee_id = $salaryAdvance->employee_id;
            $transaction->save();
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Salary Advance ' . $salaryAdvance->id;
        $audit->save();

        return redirect()->route('salary_advances.index')->with('success', 'Salary advance updated successfully.');
    }

    public function destroy($id)
    {
        Gate::authorize('salary_advances.delete');
        $salaryAdvance = SalaryAdvance::find($id);
        $salaryAdvance->delete();

        // delete existing transactions
        Transaction::where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->delete();
        PendingTransaction::where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Salary Advance ' . $salaryAdvance->id;
        $audit->save();

        return redirect()->route('salary_advances.index')->with('success', 'Salary advance deleted successfully.');
    }

    public function bulk_destroy(Request $request)
    {
        Gate::authorize('salary_advances.delete');
        foreach ($request->ids as $id) {
            $salaryAdvance = SalaryAdvance::find($id);
            $salaryAdvance->delete();

            // delete existing transactions
            Transaction::where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->delete();
            PendingTransaction::where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->delete();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Salary Advance ' . $salaryAdvance->id;
            $audit->save();
        }

        return redirect()->route('salary_advances.index')->with('success', 'Salary advances deleted successfully.');
    }

    public function bulk_approve(Request $request)
    {
        Gate::authorize('salary_advances.approve');
        foreach ($request->ids as $id) {
            $salaryAdvance = SalaryAdvance::find($id);
            $salaryAdvance->approval_status = 1;
            $salaryAdvance->save();

            // replicate transactions
            $transactions = PendingTransaction::where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->get();

            foreach ($transactions as $transaction) {
                $new_transaction = $transaction->replicate();
                $new_transaction->setTable('transactions');
                $new_transaction->save();

                $transaction->forceDelete();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Approved Salary Advance ' . $salaryAdvance->id;
            $audit->save();
        }

        return redirect()->route('salary_advances.index')->with('success', 'Salary advances approved successfully.');
    }

    public function bulk_reject(Request $request)
    {
        Gate::authorize('salary_advances.reject');
        foreach ($request->ids as $id) {
            $salaryAdvance = SalaryAdvance::find($id);
            $salaryAdvance->approval_status = 2;
            $salaryAdvance->save();

            // select from pending transactions and insert into transactions
            $transactions = Transaction::where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->get();

            foreach ($transactions as $transaction) {
                $new_transaction = $transaction->replicate();
                $new_transaction->setTable('pending_transactions');
                $new_transaction->save();

                $transaction->forceDelete();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Rejected Salary Advance ' . $salaryAdvance->id;
            $audit->save();
        }

        return redirect()->route('salary_advances.index')->with('success', 'Salary advances rejected successfully.');
    }

    public function restore($id)
    {
        Gate::authorize('salary_advances.restore');
        $salaryAdvance = SalaryAdvance::onlyTrashed()->find($id);
        $salaryAdvance->restore();

        // delete existing transactions
        Transaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->restore();
        PendingTransaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->restore();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Salary Advance ' . $salaryAdvance->id;
        $audit->save();

        return redirect()->route('salary_advances.trash')->with('success', 'Salary advance restored successfully.');
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('salary_advances.restore');
        foreach ($request->ids as $id) {
            $salaryAdvance = SalaryAdvance::onlyTrashed()->find($id);
            $salaryAdvance->restore();

            // delete existing transactions
            Transaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->restore();
            PendingTransaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->restore();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Salary Advance ' . $salaryAdvance->id;
            $audit->save();
        }

        return redirect()->route('salary_advances.trash')->with('success', 'Salary advances restored successfully.');
    }

    public function permanent_destroy($id)
    {
        Gate::authorize('salary_advances.delete');
        $salaryAdvance = SalaryAdvance::onlyTrashed()->find($id);
        $salaryAdvance->forceDelete();

        // delete existing transactions
        Transaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->forceDelete();
        PendingTransaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
            ->where('ref_type', 'salary advance')
            ->orWhere('ref_type', 'salary advance payment')
            ->forceDelete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Salary Advance ' . $salaryAdvance->id;
        $audit->save();

        return redirect()->route('salary_advances.trash')->with('success', 'Salary advance permanently deleted successfully.');
    }

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('salary_advances.delete');
        foreach ($request->ids as $id) {
            $salaryAdvance = SalaryAdvance::onlyTrashed()->find($id);
            $salaryAdvance->forceDelete();

            // delete existing transactions
            Transaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->forceDelete();
            PendingTransaction::onlyTrashed()->where('ref_id', $salaryAdvance->id)
                ->where('ref_type', 'salary advance')
                ->orWhere('ref_type', 'salary advance payment')
                ->forceDelete();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Salary Advance ' . $salaryAdvance->id;
            $audit->save();
        }

        return redirect()->route('salary_advances.trash')->with('success', 'Salary advances permanently deleted successfully.');
    }
}
