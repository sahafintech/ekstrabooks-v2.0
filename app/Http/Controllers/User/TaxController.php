<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaxController extends Controller
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
        Gate::authorize('taxes.view');
        $query = Tax::with('account');

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        if ($sortColumn === 'account.account_name') {
            $query->join('accounts', 'taxes.account_id', '=', 'accounts.id')
                  ->select('taxes.*')
                  ->orderBy('accounts.account_name', $sortDirection);
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $taxs = $query->paginate($request->get('per_page', 50));

        $accounts = Account::all();

        return Inertia::render('Backend/User/Tax/List', [
            'taxs' => $taxs->items(),
            'accounts' => $accounts,
            'meta' => [
                'current_page' => $taxs->currentPage(),
                'per_page' => $taxs->perPage(),
                'from' => $taxs->firstItem(),
                'to' => $taxs->lastItem(),
                'total' => $taxs->total(),
                'last_page' => $taxs->lastPage()
            ],
            'filters' => [
                'search' => $request->search,
                'sorting' => $sorting
            ],
            'trashed_taxes' => Tax::onlyTrashed()->count(),
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
        Gate::authorize('taxes.create');
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'rate' => 'required|numeric',
            'account_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('taxes.create')
                ->withErrors($validator)
                ->withInput();
        }

        $tax             = new Tax();
        $tax->name       = $request->input('name');
        $tax->rate       = $request->input('rate');
        $tax->tax_number = $request->input('tax_number');
        $tax->account_id = $request->input('account_id');

        $tax->save();
        $tax->rate = $tax->rate . ' %';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Tax ' . $tax->name;
        $audit->save();

        return redirect()->route('taxes.index')->with('success', _lang('Saved Successfully'));
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
        Gate::authorize('taxes.update');
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'rate' => 'required|numeric',
            'account_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('taxes.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $tax             = Tax::find($id);
        $tax->name       = $request->input('name');
        $tax->rate       = $request->input('rate');
        $tax->tax_number = $request->input('tax_number');
        $tax->account_id = $request->input('account_id');

        $tax->save();
        $tax->rate = $tax->rate . ' %';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Tax ' . $tax->name;
        $audit->save();

        return redirect()->route('taxes.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Gate::authorize('taxes.delete');
        $tax = Tax::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Tax ' . $tax->name;
        $audit->save();

        $tax->delete();
        return redirect()->route('taxes.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        Gate::authorize('taxes.delete');
        foreach ($request->ids as $id) {
            $tax = Tax::find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Tax ' . $tax->name;
            $audit->save();

            $tax->delete();
        }
        return redirect()->route('taxes.index')->with('success', _lang('Deleted Successfully'));
    }

    /**
     * Display a listing of trashed taxes.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request) {
        Gate::authorize('taxes.view');
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = Tax::onlyTrashed()->with('account');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('tax_number', 'like', "%$search%");
            });
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];
            if ($column === 'account.account_name') {
                $query->join('accounts', 'taxes.account_id', '=', 'accounts.id')
                      ->select('taxes.*')
                      ->orderBy('accounts.account_name', $direction);
            } else {
                $query->orderBy($column, $direction);
            }
        }

        $taxes = $query->paginate($per_page);

        return Inertia::render('Backend/User/Tax/Trash', [
            'taxes' => $taxes->items(),
            'meta' => [
                'current_page' => $taxes->currentPage(),
                'per_page' => $taxes->perPage(),
                'from' => $taxes->firstItem(),
                'to' => $taxes->lastItem(),
                'total' => $taxes->total(),
                'last_page' => $taxes->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
        ]);
    }

    /**
     * Restore the specified tax from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id)
    {
        Gate::authorize('taxes.restore');
        $tax = Tax::onlyTrashed()->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Tax ' . $tax->name;
        $audit->save();

        $tax->restore();

        return redirect()->route('taxes.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Bulk restore selected taxes from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {
        Gate::authorize('taxes.restore');
        foreach ($request->ids as $id) {
            $tax = Tax::onlyTrashed()->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Tax ' . $tax->name;
            $audit->save();

            $tax->restore();
        }

        return redirect()->route('taxes.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Permanently delete the specified tax from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanent_destroy(Request $request, $id)
    {
        Gate::authorize('taxes.delete');
        $tax = Tax::onlyTrashed()->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Tax ' . $tax->name;
        $audit->save();

        $tax->forceDelete();

        return redirect()->route('taxes.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    /**
     * Bulk permanently delete selected taxes from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('taxes.delete');
        foreach ($request->ids as $id) {
            $tax = Tax::onlyTrashed()->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Tax ' . $tax->name;
            $audit->save();

            $tax->forceDelete();
        }

        return redirect()->route('taxes.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }
}
