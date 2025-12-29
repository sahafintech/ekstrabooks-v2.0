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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InventoryAdjustmentImport;
use Exception;
use Illuminate\Support\Facades\Gate;

class InventoryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('inventory_adjustments.view');
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
        Gate::authorize('inventory_adjustments.view');
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
        Gate::authorize('inventory_adjustments.create');
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
        Gate::authorize('inventory_adjustments.create');
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
        Gate::authorize('inventory_adjustments.update');
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
        Gate::authorize('inventory_adjustments.update');
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
        Gate::authorize('inventory_adjustments.delete');
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
        Gate::authorize('inventory_adjustments.delete');
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
        Gate::authorize('inventory_adjustments.delete');
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
        Gate::authorize('inventory_adjustments.delete');
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
        Gate::authorize('inventory_adjustments.restore');
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
        Gate::authorize('inventory_adjustments.restore');
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

    public function import()
    {
        Gate::authorize('inventory_adjustments.import');
        
        return Inertia::render('Backend/User/InventoryAdjustment/Import');
    }

    public function uploadImportFile(Request $request)
    {
        Gate::authorize('inventory_adjustments.import');
        
        // If this is a GET request (page refresh), redirect to step 1
        if ($request->isMethod('get')) {
            return redirect()->route('inventory_adjustments.import.page');
        }
        
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            // Create a unique temporary directory
            $sessionId = session()->getId();
            $tempDir = storage_path("app/imports/temp/{$sessionId}");

            // Ensure directory exists with proper permissions
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate unique filename
            $fileName = uniqid() . '_' . $request->file('file')->getClientOriginalName();
            $fullPath = $tempDir . '/' . $fileName;

            // Move uploaded file
            $request->file('file')->move($tempDir, $fileName);

            // Verify file exists
            if (!file_exists($fullPath)) {
                throw new \Exception('Failed to store uploaded file');
            }

            // Store relative path for later use
            $relativePath = "imports/temp/{$sessionId}/{$fileName}";

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $headers = [];

            foreach ($worksheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $value = $cell->getValue();
                    if ($value) {
                        $headers[] = (string) $value;
                    }
                }
            }

            // Store in session with explicit save
            session()->put('adjustment_import_file_path', $relativePath);
            session()->put('adjustment_import_full_path', $fullPath);
            session()->put('adjustment_import_file_name', $request->file('file')->getClientOriginalName());
            session()->put('adjustment_import_headers', $headers);
            session()->save();

            return Inertia::render('Backend/User/InventoryAdjustment/Import', [
                'previewData' => [
                    'headers' => $headers,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process file: ' . $e->getMessage());
        }
    }

    public function previewImport(Request $request)
    {
        Gate::authorize('inventory_adjustments.import');
        
        // If this is a GET request (page refresh), redirect to step 1
        if ($request->isMethod('get')) {
            return redirect()->route('inventory_adjustments.import.page');
        }
        
        $mappings = $request->input('mappings', []);
        $fullPath = session('adjustment_import_full_path');
        $headers = session('adjustment_import_headers', []);

        if (!$fullPath || !file_exists($fullPath)) {
            return back()->with('error', 'Import session expired or file not found. Please upload your file again.');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = session('adjustment_import_headers', []);
            $previewRecords = [];
            $validCount = 0;
            $errorCount = 0;
            $warningCount = 0;
            $totalRows = 0;

            // Process rows (skip header row)
            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowIndex = $row->getRowIndex();
                $totalRows++;

                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                $cellIndex = 0;

                foreach ($cellIterator as $cell) {
                    if ($cellIndex < count($headers)) {
                        $header = $headers[$cellIndex];
                        $systemField = $mappings[$header] ?? null;

                        if ($systemField && $systemField !== 'skip') {
                            $rowData[$systemField] = $cell->getValue();
                        }
                    }
                    $cellIndex++;
                }

                // Validate row
                $errors = [];

                // Validate product exists
                $hasProduct = false;
                if (!empty($rowData['product_id'])) {
                    $product = Product::find($rowData['product_id']);
                    if (!$product) {
                        $errors[] = 'Product with ID "' . $rowData['product_id'] . '" not found';
                    } else {
                        $hasProduct = true;
                    }
                } elseif (!empty($rowData['product_name'])) {
                    $product = Product::where('name', $rowData['product_name'])->first();
                    if (!$product) {
                        $errors[] = 'Product "' . $rowData['product_name'] . '" not found';
                    } else {
                        $hasProduct = true;
                    }
                } else {
                    $errors[] = 'Product ID or Product Name is required';
                }

                // Validate account exists
                $hasAccount = false;
                if (!empty($rowData['account_id'])) {
                    $account = Account::find($rowData['account_id']);
                    if (!$account) {
                        $errors[] = 'Account with ID "' . $rowData['account_id'] . '" not found';
                    } else {
                        $hasAccount = true;
                    }
                } elseif (!empty($rowData['account_name'])) {
                    $account = Account::where('account_name', $rowData['account_name'])->first();
                    if (!$account) {
                        $errors[] = 'Account "' . $rowData['account_name'] . '" not found';
                    } else {
                        $hasAccount = true;
                    }
                } else {
                    $errors[] = 'Account ID or Account Name is required';
                }

                // Validate adjusted quantity
                if (empty($rowData['adjusted_quantity']) || $rowData['adjusted_quantity'] == 0) {
                    $errors[] = 'Adjusted quantity is required and must not be zero';
                }

                $status = count($errors) > 0 ? 'error' : 'valid';
                if ($status === 'error') {
                    $errorCount++;
                } else {
                    $validCount++;
                }

                // Only add rows with errors to preview_records
                if ($status === 'error') {
                    $previewRecords[] = [
                        'row' => $rowIndex,
                        'data' => $rowData,
                        'status' => $status,
                        'errors' => $errors,
                    ];
                }
            }

            session()->put('adjustment_import_mappings', $mappings);
            session()->save();

            return Inertia::render('Backend/User/InventoryAdjustment/Import', [
                'previewData' => [
                    'headers' => $headers,
                    'total_rows' => $totalRows,
                    'preview_records' => $previewRecords,
                    'valid_count' => $validCount,
                    'error_count' => $errorCount,
                    'warning_count' => $warningCount,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to preview import: ' . $e->getMessage());
        }
    }

    public function executeImport(Request $request)
    {
        Gate::authorize('inventory_adjustments.import');
        
        // If this is a GET request (page refresh), redirect to step 1
        if ($request->isMethod('get')) {
            return redirect()->route('inventory_adjustments.import.page');
        }
        
        $mappings = session('adjustment_import_mappings', []);
        $fullPath = session('adjustment_import_full_path');
        $headers = session('adjustment_import_headers', []);

        if (!$fullPath || !file_exists($fullPath)) {
            return redirect()
                ->route('inventory_adjustments.index')
                ->with('error', 'Import session expired or file not found. Please start over.');
        }

        try {
            Excel::import(new InventoryAdjustmentImport($mappings), $fullPath);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Imported Inventory Adjustments - ' . session('adjustment_import_file_name');
            $audit->save();

            // Clean up
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            session()->forget(['adjustment_import_file_path', 'adjustment_import_full_path', 'adjustment_import_file_name', 'adjustment_import_headers', 'adjustment_import_mappings']);

            return redirect()
                ->route('inventory_adjustments.index')
                ->with('success', 'Inventory adjustments imported successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('inventory_adjustments.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
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
        Gate::authorize('inventory_adjustments.export');
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
        Gate::authorize('inventory_adjustments.view');
        $adjustment = InventoryAdjustment::with('product', 'account')->findOrFail($id);

        return Inertia::render('Backend/User/InventoryAdjustment/View', [
            'adjustment' => $adjustment
        ]);
    }
}
