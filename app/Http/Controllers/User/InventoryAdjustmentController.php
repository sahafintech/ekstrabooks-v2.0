<?php

namespace App\Http\Controllers\User;

use App\Exports\InventoryAdjustmentExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InventoryAdjustmentImport;
use Exception;

class InventoryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = InventoryAdjustment::query()->with('product');

        if ($sortColumn === 'product.name') {
            $query->join('products', 'inventory_adjustments.product_id', '=', 'products.id')
                ->orderBy('products.name', $sortDirection)
                ->select('inventory_adjustments.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_date', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });;
            });
        }

        // Get vendors with pagination
        $adjustments = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/InventoryAdjustment/List', [
            'adjustments' => $adjustments->items(),
            'meta' => [
                'current_page' => $adjustments->currentPage(),
                'from' => $adjustments->firstItem(),
                'last_page' => $adjustments->lastPage(),
                'links' => $adjustments->linkCollection(),
                'path' => $adjustments->path(),
                'per_page' => $adjustments->perPage(),
                'to' => $adjustments->lastItem(),
                'total' => $adjustments->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
            'trashed_adjustments' => InventoryAdjustment::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = InventoryAdjustment::onlyTrashed()->with('product');

        if ($sortColumn === 'product.name') {
            $query->join('products', 'inventory_adjustments.product_id', '=', 'products.id')
                ->orderBy('products.name', $sortDirection)
                ->select('inventory_adjustments.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_date', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });;
            });
        }

        // Get vendors with pagination
        $adjustments = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/InventoryAdjustment/Trash', [
            'adjustments' => $adjustments->items(),
            'meta' => [
                'current_page' => $adjustments->currentPage(),
                'from' => $adjustments->firstItem(),
                'last_page' => $adjustments->lastPage(),
                'links' => $adjustments->linkCollection(),
                'path' => $adjustments->path(),
                'per_page' => $adjustments->perPage(),
                'to' => $adjustments->lastItem(),
                'total' => $adjustments->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
        ]);
    }

    /**
     * Show the form for creating a new inventory adjustment.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // Get accounts for dropdown
        $accounts = Account::all(['id', 'account_name']);

        // Get products for dropdown (only those with stock management enabled)
        $products = Product::where('stock_management', 1)
            ->select(['id', 'name', 'stock', 'product_unit_id', 'purchase_cost'])
            ->with('product_unit:id,unit')
            ->get();

        return Inertia::render('Backend/User/InventoryAdjustment/Create', [
            'accounts' => $accounts,
            'products' => $products,
            'defaultDate' => Carbon::now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'adjustment_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'product_id' => 'required|exists:products,id',
            'quantity_on_hand' => 'required|numeric',
            'adjusted_quantity' => 'required|numeric|not_in:0',
            'new_quantity' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $adjustment = new InventoryAdjustment();
            $adjustment->adjustment_date = Carbon::parse($request->adjustment_date)->format('Y-m-d');
            $adjustment->account_id = $request->account_id;
            $adjustment->product_id = $request->product_id;
            $adjustment->quantity_on_hand = $request->quantity_on_hand;
            $adjustment->adjusted_quantity = $request->adjusted_quantity;
            $adjustment->new_quantity_on_hand = $request->new_quantity;
            $adjustment->description = $request->description;
            if ($request->adjusted_quantity > 0) {
                $adjustment->adjustment_type = 'adds';
            } else {
                $adjustment->adjustment_type = 'deducts';
            }
            $adjustment->save();

            $product = Product::find($request->product_id);
            $product->stock = $request->new_quantity;
            $product->save();

            $currentTime = Carbon::now();

            if ($adjustment->adjustment_type == 'adds') {
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->format('Y-m-d H:i');
                $transaction->account_id = $request->account_id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();

                // inventory account transaction
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id = get_account('Inventory')->id;
                $transaction->dr_cr = 'dr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();
            } else {
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id = $request->account_id;
                $transaction->dr_cr = 'dr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();

                // inventory account transaction
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id = get_account('Inventory')->id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Created For ' . $product->name . ' Quantity Adjusted: ' . $request->adjusted_quantity;
            $audit->save();

            DB::commit();

            return redirect()->route('inventory_adjustments.index')
                ->with('success', _lang('Inventory adjustment created successfully'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', _lang('An error occurred: ') . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified inventory adjustment.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $adjustment = InventoryAdjustment::with('product')->findOrFail($id);

        // Get accounts for dropdown
        $accounts = Account::all(['id', 'account_name']);

        // Get products for dropdown (only those with stock management enabled)
        $products = Product::where('stock_management', 1)
            ->select(['id', 'name', 'stock', 'product_unit_id', 'purchase_cost'])
            ->with('product_unit:id,unit')
            ->get();

        return Inertia::render('Backend/User/InventoryAdjustment/Edit', [
            'adjustment' => $adjustment,
            'accounts' => $accounts,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'adjustment_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'product_id' => 'required|exists:products,id',
            'quantity_on_hand' => 'required|numeric',
            'adjusted_quantity' => 'required|numeric|not_in:0',
            'new_quantity' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $adjustment = InventoryAdjustment::findOrFail($id);
            $adjustment->adjustment_date = Carbon::parse($request->adjustment_date)->format('Y-m-d');
            $adjustment->account_id = $request->account_id;
            $adjustment->product_id = $request->product_id;
            $adjustment->quantity_on_hand = $request->quantity_on_hand;
            $adjustment->adjusted_quantity = $request->adjusted_quantity;
            $adjustment->new_quantity_on_hand = $request->new_quantity;
            $adjustment->description = $request->description;
            if ($request->adjusted_quantity > 0) {
                $adjustment->adjustment_type = 'adds';
            } else {
                $adjustment->adjustment_type = 'deducts';
            }
            $adjustment->save();

            $product = Product::find($request->product_id);
            $product->stock = $request->new_quantity;
            $product->save();

            $currentTime = Carbon::now();

            // delete previous transactions
            Transaction::where('ref_id', $request->product_id)
                ->where('ref_type', 'product adjustment')
                ->delete();

            if ($adjustment->adjustment_type == 'adds') {
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->format('Y-m-d H:i');
                $transaction->account_id = $request->account_id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();

                // inventory account transaction
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id = get_account('Inventory')->id;
                $transaction->dr_cr = 'dr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();
            } else {
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id = $request->account_id;
                $transaction->dr_cr = 'dr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();

                // inventory account transaction
                $transaction = new Transaction();
                $transaction->trans_date = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id = get_account('Inventory')->id;
                $transaction->dr_cr = 'cr';
                $transaction->transaction_currency = $request->activeBusiness->currency;
                $transaction->currency_rate = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->transaction_amount = abs($request->adjusted_quantity) * $product->purchase_cost;
                $transaction->description = $product->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
                $transaction->ref_id = $request->product_id;
                $transaction->ref_type = 'product adjustment';
                $transaction->save();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Updated For ' . $product->name . ' Quantity Adjusted: ' . $request->adjusted_quantity;
            $audit->save();

            DB::commit();

            return redirect()->route('inventory_adjustments.index')
                ->with('success', _lang('Inventory adjustment updated successfully'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', _lang('An error occurred: ') . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $adjustment = InventoryAdjustment::findOrFail($id);
            $product = Product::find($adjustment->product_id);

            // Revert stock to original quantity
            $product->stock = $adjustment->quantity_on_hand;
            $product->save();

            // Delete related transactions
            Transaction::where('ref_id', $adjustment->product_id)
                ->where('ref_type', 'product adjustment')
                ->delete();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Deleted For ' . $product->name . ' Quantity Adjusted: ' . $adjustment->adjusted_quantity;
            $audit->save();

            $adjustment->delete();

            DB::commit();

            return redirect()->route('inventory_adjustments.index')
                ->with('success', _lang('Inventory adjustment deleted successfully'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', _lang('An error occurred: ') . $e->getMessage());
        }
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_destroy(Request $request)
    {

        $ids = $request->ids;
        $adjustments = InventoryAdjustment::whereIn('id', $ids)->get();

        foreach ($adjustments as $adjustment) {
            $product = Product::find($adjustment->product_id);

            // Revert stock to original quantity
            $product->stock = $adjustment->quantity_on_hand;
            $product->save();

            // Delete related transactions
            Transaction::where('ref_id', $adjustment->product_id)
                ->where('ref_type', 'product adjustment')
                ->delete();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Deleted For ' . $product->name . ' Quantity Adjusted: ' . $adjustment->adjusted_quantity;
            $audit->save();

            $adjustment->delete();
        }

        return redirect()->route('inventory_adjustments.index')
            ->with('success', _lang('Inventory adjustments deleted successfully'));
    }

    public function permanent_destroy($id)
    {
        DB::beginTransaction();

        try {
            $adjustment = InventoryAdjustment::onlyTrashed()->find($id);
            $product = Product::find($adjustment->product_id);

            // Delete related transactions
            Transaction::onlyTrashed()->where('ref_id', $adjustment->product_id)
                ->where('ref_type', 'product adjustment')
                ->forceDelete();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Permanently Deleted For ' . $product->name . ' Quantity Adjusted: ' . $adjustment->adjusted_quantity;
            $audit->save();

            $adjustment->forceDelete();

            DB::commit();

            return redirect()->route('inventory_adjustments.trash')
                ->with('success', _lang('Inventory adjustment permanently deleted successfully'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', _lang('An error occurred: ') . $e->getMessage());
        }
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {

        $ids = $request->ids;
        $adjustments = InventoryAdjustment::onlyTrashed()->whereIn('id', $ids)->get();

        foreach ($adjustments as $adjustment) {
            $product = Product::find($adjustment->product_id);

            // Delete related transactions
            Transaction::onlyTrashed()->where('ref_id', $adjustment->product_id)
                ->where('ref_type', 'product adjustment')
                ->forceDelete();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Permanently Deleted For ' . $product->name . ' Quantity Adjusted: ' . $adjustment->adjusted_quantity;
            $audit->save();

            $adjustment->forceDelete();
        }

        return redirect()->route('inventory_adjustments.trash')
            ->with('success', _lang('Inventory adjustments permanently deleted successfully'));
    }

    public function restore($id)
    {
        DB::beginTransaction();

        try {
            $adjustment = InventoryAdjustment::onlyTrashed()->findOrFail($id);
            $product = Product::find($adjustment->product_id);

            // add or deduct stock
            $product->stock = $adjustment->adjustment_type == 'adds' ? $product->stock + $adjustment->adjusted_quantity : $product->stock - $adjustment->adjusted_quantity;
            $product->save();

            // Delete related transactions
            Transaction::onlyTrashed()->where('ref_id', $adjustment->product_id)
                ->where('ref_type', 'product adjustment')
                ->restore();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Restored For ' . $product->name . ' Quantity Adjusted: ' . $adjustment->adjusted_quantity;
            $audit->save();

            $adjustment->restore();

            DB::commit();

            return redirect()->route('inventory_adjustments.trash')
                ->with('success', _lang('Inventory adjustment restored successfully'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', _lang('An error occurred: ') . $e->getMessage());
        }
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {

        $ids = $request->ids;
        $adjustments = InventoryAdjustment::onlyTrashed()->whereIn('id', $ids)->get();

        foreach ($adjustments as $adjustment) {
            $product = Product::find($adjustment->product_id);

            // add or deduct stock
            $product->stock = $adjustment->adjustment_type == 'adds' ? $product->stock + $adjustment->adjusted_quantity : $product->stock - $adjustment->adjusted_quantity;
            $product->save();

            // Delete related transactions
            Transaction::onlyTrashed()->where('ref_id', $adjustment->product_id)
                ->where('ref_type', 'product adjustment')
                ->restore();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Inventory Adjustment Restored For ' . $product->name . ' Quantity Adjusted: ' . $adjustment->adjusted_quantity;
            $audit->save();

            $adjustment->restore();
        }

        return redirect()->route('inventory_adjustments.trash')
            ->with('success', _lang('Inventory adjustments restored successfully'));
    }

    public function import_adjustments(Request $request)
    {
        $request->validate([
            'adjustments_file' => 'required|mimes:xls,xlsx,csv',
        ]);

        try {
            Excel::import(new InventoryAdjustmentImport, $request->file('adjustments_file'));

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Imported Inventory Adjustments';
        $audit->save();

            return redirect()->route('inventory_adjustments.index')->with('success', _lang('Inventory Adjustments Imported'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', _lang('An error occurred: ') . $e->getMessage());
        }
    }

    /**
     * Export inventory adjustments to Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export_adjustments()
    {
        return Excel::download(new InventoryAdjustmentExport, 'inventory adjustments export ' . now()->format('d m Y') . '.xlsx');

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Exported Inventory Adjustments';
        $audit->save();
    }

    public function show($id)
    {
        $adjustment = InventoryAdjustment::with('product', 'account')->findOrFail($id);

        return Inertia::render('Backend/User/InventoryAdjustment/View', [
            'adjustment' => $adjustment
        ]);
    }
}
