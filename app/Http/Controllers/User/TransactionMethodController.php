<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TransactionMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TransactionMethodController extends Controller {

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
    public function index(Request $request) {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = TransactionMethod::query()
            ->where('business_id', request()->activeBusiness->id);
        
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];
            $query->orderBy($column, $direction);
        }
        
        $transaction_methods = $query->paginate($per_page);
        
        return Inertia::render('Backend/User/TransactionMethod/List', [
            'transaction_methods' => $transaction_methods->items(),
            'meta' => [
                'current_page' => $transaction_methods->currentPage(),
                'per_page' => $transaction_methods->perPage(),
                'from' => $transaction_methods->firstItem(),
                'to' => $transaction_methods->lastItem(),
                'total' => $transaction_methods->total(),
                'last_page' => $transaction_methods->lastPage()
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
            'trashed_transaction_methods' => TransactionMethod::onlyTrashed()->where('business_id', request()->activeBusiness->id)->count(),
        ]);
    }

    /**
     * Display a listing of trashed transaction methods.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request) {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = TransactionMethod::onlyTrashed()
            ->where('business_id', request()->activeBusiness->id);

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];
            $query->orderBy($column, $direction);
        }

        $transaction_methods = $query->paginate($per_page);

        return Inertia::render('Backend/User/TransactionMethod/Trash', [
            'transaction_methods' => $transaction_methods->items(),
            'meta' => [
                'current_page' => $transaction_methods->currentPage(),
                'per_page' => $transaction_methods->perPage(),
                'from' => $transaction_methods->firstItem(),
                'to' => $transaction_methods->lastItem(),
                'total' => $transaction_methods->total(),
                'last_page' => $transaction_methods->lastPage()
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|max:50',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $transactionmethod = new TransactionMethod();
        $transactionmethod->name = $request->input('name');
        $transactionmethod->status = $request->input('status');
        $transactionmethod->business_id = request()->activeBusiness->id;
        $transactionmethod->user_id = Auth::user()->id;
        $transactionmethod->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Transaction Method ' . $transactionmethod->name;
        $audit->save();

        return redirect()->route('transaction_methods.index')->with('success', _lang('Transaction Method Added Successfully'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|max:50',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $transactionmethod = TransactionMethod::where('id', $id)
            ->where('business_id', request()->activeBusiness->id)
            ->first();
            
        if (!$transactionmethod) {
            return redirect()->route('transaction_methods.index')->with('error', _lang('Transaction Method not found!'));
        }
        
        $transactionmethod->name = $request->input('name');
        $transactionmethod->status = $request->input('status');
        $transactionmethod->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Transaction Method ' . $transactionmethod->name;
        $audit->save();

        return redirect()->route('transaction_methods.index')->with('success', _lang('Transaction Method Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $transactionmethod = TransactionMethod::where('id', $id)
            ->where('business_id', request()->activeBusiness->id)
            ->first();
            
        if (!$transactionmethod) {
            return redirect()->route('transaction_methods.index')->with('error', _lang('Transaction Method not found!'));
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Transaction Method ' . $transactionmethod->name;
        $audit->save();
        
        try {
            $transactionmethod->delete();
            return redirect()->route('transaction_methods.index')->with('success', _lang('Deleted Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('transaction_methods.index')->with('error', _lang('This item is already exists in other entity'));
        }
    }
    
    /**
     * Bulk delete transaction methods
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_destroy(Request $request) {
        if (!$request->ids || !is_array($request->ids)) {
            return redirect()->route('transaction_methods.index')->with('error', _lang('Please select at least one transaction method'));
        }
        
        $transaction_methods = TransactionMethod::whereIn('id', $request->ids)
            ->where('business_id', request()->activeBusiness->id)
            ->get();
        
        foreach($transaction_methods as $transaction_method) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Transaction Method ' . $transaction_method->name;
            $audit->save();

            try {
                $transaction_method->delete();
            } catch (\Exception $e) {
                // Continue with the next transaction method
            }
        }
            
        return redirect()->route('transaction_methods.index')->with('success', _lang('Selected Transaction Methods Deleted Successfully'));
    }

    /**
     * Restore the specified transaction method from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id)
    {
        $transaction_method = TransactionMethod::onlyTrashed()
            ->where('id', $id)
            ->where('business_id', request()->activeBusiness->id)
            ->firstOrFail();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Transaction Method ' . $transaction_method->name;
        $audit->save();

        $transaction_method->restore();

        return redirect()->route('transaction_methods.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Bulk restore selected transaction methods from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {
        foreach ($request->ids as $id) {
            $transaction_method = TransactionMethod::onlyTrashed()
                ->where('id', $id)
                ->where('business_id', request()->activeBusiness->id)
                ->firstOrFail();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Transaction Method ' . $transaction_method->name;
            $audit->save();

            $transaction_method->restore();
        }

        return redirect()->route('transaction_methods.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Permanently delete the specified transaction method from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanent_destroy(Request $request, $id)
    {
        $transaction_method = TransactionMethod::onlyTrashed()
            ->where('id', $id)
            ->where('business_id', request()->activeBusiness->id)
            ->firstOrFail();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Transaction Method ' . $transaction_method->name;
        $audit->save();

        $transaction_method->forceDelete();

        return redirect()->route('transaction_methods.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    /**
     * Bulk permanently delete selected transaction methods from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $transaction_method = TransactionMethod::onlyTrashed()
                ->where('id', $id)
                ->where('business_id', request()->activeBusiness->id)
                ->firstOrFail();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Transaction Method ' . $transaction_method->name;
            $audit->save();

            $transaction_method->forceDelete();
        }

        return redirect()->route('transaction_methods.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }
}