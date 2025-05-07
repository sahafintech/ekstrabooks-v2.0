<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
        $query = TransactionMethod::query()
            ->where('business_id', request()->activeBusiness->id);
        
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $transaction_methods = $query->paginate($request->get('per_page', 10));
        
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
            'filters' => $request->only('search')
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
        
        $transactionmethod->delete();
        return redirect()->route('transaction_methods.index')->with('success', _lang('Transaction Method Deleted Successfully'));
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
        
        TransactionMethod::whereIn('id', $request->ids)
            ->where('business_id', request()->activeBusiness->id)
            ->delete();
            
        return redirect()->route('transaction_methods.index')->with('success', _lang('Selected Transaction Methods Deleted Successfully'));
    }
}