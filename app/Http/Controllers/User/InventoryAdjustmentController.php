<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InventoryAdjustmentImport;
use App\Jobs\InventoryAdjustmentJob;

class InventoryAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = InventoryAdjustment::all();
        return view('backend.user.inventory_adjustment.list', compact('adjustments'));
    }

    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.inventory_adjustment.modal.create');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'adjustment_date' => 'required',
            'account_id' => 'required',
            'product_id' => 'required',
            'quantity_on_hand' => 'required',
            'adjusted_quantity' => 'required',
            'new_quantity' => 'required',
            'description' => 'nullable',
        ]);

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
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->format('Y-m-d H:i');
            $transaction->account_id  = $request->account_id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();

            // invetory account transaction
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Inventory')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();
        } else {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = $request->account_id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();

            // invetory account transaction
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Inventory')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Inventory Adjustment Created For' . Product::find($request->product_id)->name . ' Quantity Adjusted: ' . $request->adjusted_quantity;
        $audit->save();

        return redirect()->route('inventory_adjustments.index')->with('success', 'Inventory adjustment created successfully');
    }

    public function show($id)
    {
        $adjustment = InventoryAdjustment::find($id);
        return view('backend.user.inventory_adjustment.modal.show', compact('adjustment'));
    }

    public function edit($id)
    {
        $adjustment = InventoryAdjustment::find($id);
        return view('backend.user.inventory_adjustment.modal.edit', compact('adjustment', 'id'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'adjustment_date' => 'required',
            'account_id' => 'required',
            'product_id' => 'required',
            'quantity_on_hand' => 'required',
            'adjusted_quantity' => 'required',
            'new_quantity' => 'required',
            'description' => 'nullable',
        ]);

        $adjustment = InventoryAdjustment::find($id);
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
        Transaction::where('ref_id', $request->product_id)->where('ref_type', 'product adjustment')->delete();

        if ($adjustment->adjustment_type == 'adds') {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->format('Y-m-d H:i');
            $transaction->account_id  = $request->account_id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();

            // invetory account transaction
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Inventory')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();
        } else {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = $request->account_id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();

            // invetory account transaction
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->adjustment_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Inventory')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->transaction_amount      = abs($request->adjusted_quantity) * Product::find($request->product_id)->purchase_cost;
            $transaction->description = Product::find($request->product_id)->name . ' Inventory Adjustment #' . $request->adjusted_quantity;
            $transaction->ref_id      =  $request->product_id;
            $transaction->ref_type    = 'product adjustment';
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Inventory Adjustment Updated For' . Product::find($request->product_id)->name . ' Quantity Adjusted: ' . $request->adjusted_quantity;
        $audit->save();

        return redirect()->route('inventory_adjustments.index')->with('success', 'Inventory adjustment updated successfully');

    }

    public function destroy($id)
    {
        $adjustment = InventoryAdjustment::find($id);

        $product = Product::find($adjustment->product_id);
        $product->stock = $adjustment->quantity_on_hand;
        $product->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Inventory Adjustment Deleted For' . Product::find($adjustment->product_id)->name . ' Quantity Adjusted: ' . $adjustment->adjusted_quantity;
        $audit->save();

        // delete previous transactions
        Transaction::where('ref_id', $adjustment->product_id)->where('ref_type', 'product adjustment')->delete();

        $adjustment->delete();
        return redirect()->route('inventory_adjustments.index')->with('success', 'Inventory adjustment deleted successfully');
    }

    /**
     * Show the import form
     *
     * @return \Illuminate\View\View
     */
    public function import()
    {
        return view('backend.user.inventory_adjustment.import');
    }

    /**
     * Handle the initial file upload
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_file' => 'required|mimes:xlsx,xls|max:2048',
            'heading' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
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

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Inventory Adjustment Import Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => _lang('An error occurred while uploading the file')]);
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
            return redirect()->route('inventory_adjustment.import')
                ->with('error', _lang('No import file found. Please upload a file first.'));
        }

        return view('backend.user.inventory_adjustment.import_progress');
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
            return response()->json(['success' => false, 'message' => _lang('No import file found')]);
        }

        try {
            // Get the authenticated user's business ID
            $businessId = $request->activeBusiness->id;
            if (!$businessId) {
                return response()->json(['success' => false, 'message' => _lang('No active business found')]);
            }

            // Validate the file first
            $import = new InventoryAdjustmentImport();
            $filePath = storage_path('app/imports/' . session('import_file'));
            
            // Validate the Excel file structure
            try {
                $rows = Excel::toCollection($import, $filePath)->first();
                if ($rows->isEmpty()) {
                    return response()->json(['success' => false, 'message' => _lang('The file is empty')]);
                }
            } catch (Exception $e) {
                Log::error('Excel Import Error: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => _lang('Invalid file format')]);
            }

            // Dispatch the batch with the job
            $batch = Bus::batch([
                new InventoryAdjustmentJob(
                    $filePath,
                    auth()->id(),
                    $businessId
                )
            ])
            ->name('inventory-adjustment')
            ->onQueue('inventory-adjustment')
            ->dispatch();

            // Clear the import session data
            session()->forget(['import_file', 'has_heading']);

            return redirect()->route('inventory_adjustments.index')->with('success', _lang('Inventory adjustments imported successfully'));
        } catch (Exception $e) {
            Log::error('Inventory Adjustment Import Process Error: ' . $e->getMessage());
            return redirect()->route('inventory_adjustments.index')->with('error', _lang('An error occurred while processing the file'));
        }
    }

    public function importProcessProgress($batchId)
    {
        $batch = Bus::findBatch($batchId);
        
        return response()->json([
            'total' => $batch->totalJobs,
            'processed' => $batch->processedJobs(),
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'failed' => $batch->failedJobs
        ]);
    }
}
