<?php

namespace App\Http\Controllers\User;

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
use Illuminate\Support\Facades\Validator;

class InventoryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryAdjustment::query()
            ->with('product')
            ->orderBy("id", "desc");

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

    /**
     * Show the import form
     *
     * @return \Illuminate\View\View
     */
    public function import()
    {
        return Inertia::render('Backend/User/InventoryAdjustment/Import');
    }

    /**
     * Store the uploaded file
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'heading' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $file = $request->file('import_file');
            $fileName = 'inventory_adjustment_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('imports', $fileName);

            // Store import session data
            session([
                'import_file' => $fileName,
                'has_heading' => $request->boolean('heading', true)
            ]);

            return redirect()->route('inventory_adjustments.import.progress');
        } catch (\Exception $e) {
            // return response()->json(['error' => $e->getMessage()]);
            return redirect()->back()->with('error', _lang('An error occurred while uploading the file'));
        }
    }

    /**
     * Show the import progress page
     *
     * @return \Illuminate\View\View
     */
    public function importProgress()
    {
        if (!session('import_file')) {
            return redirect()->route('inventory_adjustments.import')
                ->with('error', _lang('No import file found. Please upload a file first.'));
        }

        return Inertia::render('Backend/User/InventoryAdjustment/ImportProgress');
    }

    /**
     * Get the current status of the import process
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function importStatus()
    {
        // Get progress from session
        $progress = session('import_progress');

        if (!$progress) {
            return response()->json([
                'status' => 'waiting',
                'message' => 'No active import found. Please start the import process.',
                'progress' => 0,
                'info' => [
                    'total' => 0,
                    'processed' => 0,
                    'succeeded' => 0,
                    'failed' => 0
                ]
            ]);
        }

        // Calculate progress percentage
        $progressPercent = 0;
        if ($progress['total'] > 0) {
            $progressPercent = round(($progress['processed'] / $progress['total']) * 100);
        }

        // Determine status
        $status = 'processing';
        $message = 'Processing your inventory adjustments...';

        if ($progress['processed'] >= $progress['total']) {
            $status = 'completed';
            $message = 'Import completed successfully.';

            if ($progress['failed'] > 0) {
                $message = 'Import completed with ' . $progress['failed'] . ' failed items.';
            }
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'progress' => $progressPercent,
            'info' => [
                'total' => $progress['total'],
                'processed' => $progress['processed'],
                'succeeded' => $progress['succeeded'],
                'failed' => $progress['failed']
            ],
            'errors' => $progress['errors'] ?? []
        ]);
    }

    /**
     * Process the uploaded file
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importProcess(Request $request)
    {
        if (!session('import_file')) {
            return response()->json([
                'success' => false,
                'message' => _lang('No import file found. Please upload a file first.')
            ]);
        }

        try {
            // Get file name from session
            $importFile = session('import_file');
            $filePath = storage_path('app/imports/' . $importFile);

            // Check if file exists
            if (!file_exists($filePath)) {
                \Log::error('Import file not found at: ' . $filePath);
                return response()->json([
                    'success' => false,
                    'message' => _lang('Import file not found on server. Please upload the file again.')
                ]);
            }

            $businessId = $request->activeBusiness->id ?? null;
            if (!$businessId) {
                \Log::error('No active business found for user: ' . auth()->id());
                return response()->json([
                    'success' => false,
                    'message' => _lang('No active business found')
                ]);
            }

            // Validate the file first
            $hasHeading = session('has_heading', true);
            \Log::info('Processing import file: ' . $filePath . ' with heading: ' . ($hasHeading ? 'Yes' : 'No'));

            $import = new InventoryAdjustmentImport($businessId, $hasHeading);

            try {
                $rows = Excel::toCollection($import, $filePath)->first();
                if ($rows->isEmpty()) {
                    \Log::warning('Import file is empty: ' . $filePath);
                    return response()->json([
                        'success' => false,
                        'message' => _lang('The file is empty')
                    ]);
                }

                \Log::info('Successfully loaded file with ' . count($rows) . ' rows');
            } catch (Exception $e) {
                \Log::error('Error reading import file: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => _lang('Invalid file format: ') . $e->getMessage()
                ]);
            }

            // Process each row directly
            $totalRows = count($rows) - ($hasHeading ? 1 : 0);
            $processed = 0;
            $succeeded = 0;
            $failed = 0;
            $errors = [];

            // Create a progress tracking session
            session([
                'import_progress' => [
                    'total' => $totalRows,
                    'processed' => 0,
                    'succeeded' => 0,
                    'failed' => 0,
                    'errors' => []
                ]
            ]);

            // Process rows directly
            foreach ($rows as $index => $row) {
                // Skip the header row if the file has headings
                if ($hasHeading && $index === 0) {
                    continue;
                }

                try {
                    $rowData = $row->toArray();
                    \Log::debug('Processing row ' . ($index + 1) . ': ' . json_encode($rowData));

                    // Extract data from the row
                    $productCode = $rowData[0] ?? null;
                    $adjustmentType = strtolower($rowData[1] ?? '');
                    $quantity = (float)($rowData[2] ?? 0);
                    $dateStr = $rowData[3] ?? null;
                    $reference = $rowData[4] ?? null;
                    $accountName = $rowData[5] ?? null;
                    $currencyCode = strtoupper($rowData[6] ?? 'USD');
                    $notes = $rowData[7] ?? null;

                    // Validate required fields
                    if (!$productCode || !$adjustmentType || !$quantity || !$dateStr || !$accountName) {
                        throw new \Exception("Missing required fields in import row: Product Code, Adjustment Type, Quantity, Date, and Account are required");
                    }

                    // Find the product by code
                    $product = Product::where('business_id', $businessId)
                        ->where('code', $productCode)
                        ->first();

                    if (!$product) {
                        throw new \Exception("Product not found with code: {$productCode}");
                    }

                    // Find the account
                    $account = Account::where('business_id', $businessId)
                        ->where('name', $accountName)
                        ->first();

                    if (!$account) {
                        throw new \Exception("Account not found with name: {$accountName}");
                    }

                    // Process date
                    try {
                        $date = Carbon::createFromFormat('Y-m-d', $dateStr);
                    } catch (\Exception $e) {
                        try {
                            $date = Carbon::createFromFormat('d/m/Y', $dateStr);
                        } catch (\Exception $e) {
                            $date = Carbon::now();
                            \Log::warning("Could not parse date format: {$dateStr}, using current date");
                        }
                    }

                    // Get the currency - ensure ISO 4217 compliance
                    $currency = Currency::where('code', $currencyCode)
                        ->where('business_id', $businessId)
                        ->first();

                    if (!$currency) {
                        // Try to find the default currency (base currency with exchange_rate = 1.000000)
                        \Log::warning("Currency not found with code: {$currencyCode}, trying to use base currency");
                        $currency = Currency::where('business_id', $businessId)
                            ->where('exchange_rate', 1.0)
                            ->first();

                        if (!$currency) {
                            throw new \Exception("Currency not found with code: {$currencyCode} and no base currency available");
                        }
                    }

                    // Validate that currency follows ISO 4217 standards
                    if (strlen($currency->code) !== 3) {
                        throw new \Exception("Currency code not ISO 4217 compliant (must be 3 characters): {$currency->code}");
                    }

                    // Set the adjustment type
                    $type = 'addition';
                    if (in_array($adjustmentType, ['subtraction', 'deduction', 'removal', 'minus', 'negative', 'deduct'])) {
                        $type = 'subtraction';
                        // Ensure quantity is positive for proper calculations
                        $quantity = abs($quantity);
                    }

                    // Get the default warehouse
                    $warehouse = Warehouse::where('business_id', $businessId)
                        ->where('is_default', 1)
                        ->first();

                    if (!$warehouse) {
                        // Get any warehouse if default not found
                        $warehouse = Warehouse::where('business_id', $businessId)->first();

                        if (!$warehouse) {
                            throw new \Exception("No warehouse found for your business");
                        }
                    }

                    DB::beginTransaction();

                    // Create the inventory adjustment
                    $adjustment = new InventoryAdjustment();
                    $adjustment->business_id = $businessId;
                    $adjustment->product_id = $product->id;
                    $adjustment->warehouse_id = $warehouse->id;
                    $adjustment->account_id = $account->id;
                    $adjustment->adjustment_type = $type;
                    $adjustment->quantity = $quantity;
                    $adjustment->date = $date;
                    $adjustment->reference = $reference;
                    $adjustment->currency_id = $currency->id;
                    $adjustment->notes = $notes;
                    $adjustment->save();

                    // Update product stock
                    if ($type === 'addition') {
                        $product->increment('stock', $quantity);
                    } else {
                        $product->decrement('stock', $quantity);
                    }

                    DB::commit();
                    $succeeded++;
                    \Log::info("Successfully processed row " . ($index + 1) . " for product: {$productCode}");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $errorMsg = "Row " . ($index + 1) . ": " . $e->getMessage();
                    $errors[] = $errorMsg;
                    \Log::error($errorMsg);
                }

                $processed++;

                // Update progress in session
                session([
                    'import_progress' => [
                        'total' => $totalRows,
                        'processed' => $processed,
                        'succeeded' => $succeeded,
                        'failed' => $failed,
                        'errors' => $errors,
                        'progress' => round(($processed / $totalRows) * 100)
                    ]
                ]);
            }

            // Return success response
            return response()->json([
                'success' => true,
                'message' => _lang('Import completed: ' . $succeeded . ' succeeded, ' . $failed . ' failed'),
                'stats' => [
                    'total' => $totalRows,
                    'processed' => $processed,
                    'succeeded' => $succeeded,
                    'failed' => $failed
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in import process: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => _lang('An error occurred while processing the file: ') . $e->getMessage()
            ]);
        }
    }

    /**
     * Export inventory adjustments to Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $businessId = $request->activeBusiness->id;

        // Get filtered inventory adjustments
        $query = InventoryAdjustment::with(['product', 'account'])
            ->where('business_id', $businessId);

        // Apply filters if provided
        if ($request->has('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date = $dates[1];
                $query->whereBetween('adjustment_date', [$start_date, $end_date]);
            }
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $adjustments = $query->get();

        $exportData = [];
        foreach ($adjustments as $adjustment) {
            $exportData[] = [
                'adjustment_date' => $adjustment->adjustment_date,
                'product_name' => $adjustment->product->name,
                'account_name' => $adjustment->account->name,
                'quantity_on_hand' => $adjustment->quantity_on_hand,
                'adjusted_quantity' => $adjustment->adjusted_quantity,
                'description' => $adjustment->description,
            ];
        }

        // Create Excel export with proper currency handling using ISO 4217 codes
        return Excel::download(new \Maatwebsite\Excel\Sheets\Sheet(
            'Inventory Adjustments',
            collect($exportData)
        ), 'inventory_adjustments_' . date('Y-m-d') . '.xlsx');
    }
}
