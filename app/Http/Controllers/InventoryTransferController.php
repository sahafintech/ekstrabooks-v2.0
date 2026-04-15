<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Product;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class InventoryTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of inventory transfers
     */
    public function index(Request $request)
    {
        Gate::authorize('inventory_transfers.view');

        $activeBusiness = $request->activeBusiness;
        
        $query = InventoryTransfer::with([
            'fromEntity:id,name',
            'toEntity:id,name',
            'items:inventory_transfer_id,product_id,requested_quantity,counted_quantity',
            'items.product:id,name'
        ])->forBusiness($activeBusiness->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by direction (incoming/outgoing)
        if ($request->filled('direction')) {
            if ($request->direction === 'outgoing') {
                $query->outgoing($activeBusiness->id);
            } elseif ($request->direction === 'incoming') {
                $query->incoming($activeBusiness->id);
            }
        }

        // Search by transfer number or entity names
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhereHas('fromEntity', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('toEntity', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        
        if ($sortColumn === 'from_entity.name') {
            $query->join('business as from_business', 'inventory_transfers.from_entity_id', '=', 'from_business.id')
                ->orderBy('from_business.name', $sortDirection)
                ->select('inventory_transfers.*');
        } elseif ($sortColumn === 'to_entity.name') {
            $query->join('business as to_business', 'inventory_transfers.to_entity_id', '=', 'to_business.id')
                ->orderBy('to_business.name', $sortDirection)
                ->select('inventory_transfers.*');
        } else {
            $query->orderBy('inventory_transfers.' . $sortColumn, $sortDirection);
        }

        // Filter by business
        if ($request->filled('business_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_entity_id', $request->business_id)
                  ->orWhere('to_entity_id', $request->business_id);
            });
        }

        // Filter by date range
        if ($request->filled('date_range') && is_array($request->date_range)) {
            $dateRange = $request->date_range;
            if (!empty($dateRange[0])) {
                $query->whereDate('transfer_date', '>=', $dateRange[0]);
            }
            if (!empty($dateRange[1])) {
                $query->whereDate('transfer_date', '<=', $dateRange[1]);
            }
        }

        // Handle pagination
        $perPage = $request->get('per_page', 50);
        $transfers = $query->paginate($perPage);

        // Get summary statistics
        $summaryQuery = InventoryTransfer::forBusiness($activeBusiness->id);
        
        // Apply same filters for summary
        if ($request->filled('status')) {
            $summaryQuery->where('status', $request->status);
        }
        if ($request->filled('direction')) {
            if ($request->direction === 'outgoing') {
                $summaryQuery->outgoing($activeBusiness->id);
            } elseif ($request->direction === 'incoming') {
                $summaryQuery->incoming($activeBusiness->id);
            }
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $summaryQuery->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhereHas('fromEntity', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('toEntity', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('business_id')) {
            $summaryQuery->where(function ($q) use ($request) {
                $q->where('from_entity_id', $request->business_id)
                  ->orWhere('to_entity_id', $request->business_id);
            });
        }
        if ($request->filled('date_range') && is_array($request->date_range)) {
            $dateRange = $request->date_range;
            if (!empty($dateRange[0])) {
                $summaryQuery->whereDate('transfer_date', '>=', $dateRange[0]);
            }
            if (!empty($dateRange[1])) {
                $summaryQuery->whereDate('transfer_date', '<=', $dateRange[1]);
            }
        }

        $allTransfers = $summaryQuery->get();
        $summary = [
            'total_transfers' => $allTransfers->count(),
            'outgoing_transfers' => $allTransfers->where('from_entity_id', $activeBusiness->id)->count(),
            'incoming_transfers' => $allTransfers->where('to_entity_id', $activeBusiness->id)->count(),
            'pending_transfers' => $allTransfers->whereIn('status', ['draft', 'sent'])->count(),
        ];

        // Get all businesses for filters
        $businesses = Business::where('id', '!=', $activeBusiness->id)
            ->where('status', 1)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Backend/User/InventoryTransfer/List', [
            'transfers' => $transfers->items(),
            'meta' => [
                'total' => $transfers->total(),
                'per_page' => $transfers->perPage(),
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'from' => $transfers->firstItem(),
                'to' => $transfers->lastItem(),
            ],
            'filters' => array_merge($request->only(['status', 'direction', 'search', 'business_id', 'date_range']), [
                'sorting' => $request->get('sorting', ['column' => 'id', 'direction' => 'desc']),
            ]),
            'businesses' => $businesses,
            'summary' => $summary,
            'trashed_transfers' => InventoryTransfer::onlyTrashed()->forBusiness($activeBusiness->id)->count(),
        ]);
    }

    /**
     * Show the form for creating a new inventory transfer
     */
    public function create(Request $request)
    {
        Gate::authorize('inventory_transfers.create');

        $activeBusiness = $request->activeBusiness;

        // Get available destination businesses (exclude current business)
        $businesses = Business::where('id', '!=', $activeBusiness->id)
            ->where('status', 1)
            ->select('id', 'name')
            ->get();

        // Get products from current business with stock management enabled
        $products = Product::where('business_id', $activeBusiness->id)
            ->where('stock_management', 1)
            ->where('status', 1)
            ->with('product_unit:id,unit')
            ->select('id', 'name', 'stock', 'product_unit_id', 'purchase_cost')
            ->get();

        return Inertia::render('Backend/User/InventoryTransfer/Create', [
            'businesses' => $businesses,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created inventory transfer
     */
    public function store(Request $request)
    {
        $activeBusiness = $request->activeBusiness;

        $request->validate([
            'transfer_date' => 'required|date',
            'to_entity_id' => [
                'required',
                'exists:business,id',
                Rule::notIn([$activeBusiness->id]) // Cannot transfer to self
            ],
            'remarks' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Validate products belong to current business
            $productIds = collect($request->items)->pluck('product_id');
            $validProducts = Product::where('business_id', $activeBusiness->id)
                ->whereIn('id', $productIds)
                ->count();

            if ($validProducts !== $productIds->count()) {
                throw new \Exception('One or more products do not belong to your business');
            }

            // Create the transfer
            $transfer = InventoryTransfer::create([
                'transfer_date' => Carbon::parse($request->transfer_date)->format('Y-m-d'),
                'from_entity_id' => $activeBusiness->id,
                'to_entity_id' => $request->to_entity_id,
                'remarks' => $request->remarks,
                'status' => 'draft',
                'created_user_id' => Auth::id(),
            ]);

            // Create transfer items
            foreach ($request->items as $item) {
                InventoryTransferItem::create([
                    'inventory_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'requested_quantity' => $item['requested_quantity'],
                    'notes' => $item['notes'] ?? null,
                    'created_user_id' => Auth::id(),
                ]);
            }

            DB::commit();

            return redirect()->route('inventory_transfers.show', $transfer->id)
                ->with('success', 'Inventory transfer created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified inventory transfer
     */
    public function show(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.view');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::with([
            'fromEntity:id,name',
            'toEntity:id,name',
            'items.product:id,name,stock',
            'items.product.product_unit:id,unit',
            'comments.createdUser:id,name',
            'createdUser:id,name',
            'sentUser:id,name',
            'receivedUser:id,name',
            'rejectedUser:id,name',
            'cancelledUser:id,name'
        ])->forBusiness($activeBusiness->id)
          ->findOrFail($id);

        // Check if user can view this transfer
        if (!$this->canViewTransfer($transfer, $activeBusiness->id)) {
            abort(403, 'Unauthorized to view this transfer');
        }

        return Inertia::render('Backend/User/InventoryTransfer/View', [
            'transfer' => $transfer,
            'canEdit' => $this->canEditTransfer($transfer, $activeBusiness->id),
            'canSend' => $this->canSendTransfer($transfer, $activeBusiness->id),
            'canReceive' => $this->canReceiveTransfer($transfer, $activeBusiness->id),
            'canReject' => $this->canRejectTransfer($transfer, $activeBusiness->id),
            'canCancel' => $this->canCancelTransfer($transfer, $activeBusiness->id),
        ]);
    }

    /**
     * Show the form for editing the specified inventory transfer
     */
    public function edit(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.update');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::with([
            'items.product:id,name,stock',
            'items.product.product_unit:id,unit'
        ])->forBusiness($activeBusiness->id)
          ->findOrFail($id);

        // Check if user can edit this transfer
        if (!$this->canEditTransfer($transfer, $activeBusiness->id)) {
            abort(403, 'Cannot edit this transfer');
        }

        // Get available destination businesses (exclude current business)
        $businesses = Business::where('id', '!=', $activeBusiness->id)
            ->where('status', 1)
            ->select('id', 'name')
            ->get();

        // Get products from current business with stock management enabled
        $products = Product::where('business_id', $activeBusiness->id)
            ->where('stock_management', 1)
            ->where('status', 1)
            ->with('product_unit:id,unit')
            ->select('id', 'name', 'stock', 'product_unit_id', 'purchase_cost')
            ->get();

        return Inertia::render('Backend/User/InventoryTransfer/Edit', [
            'transfer' => $transfer,
            'businesses' => $businesses,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified inventory transfer
     */
    public function update(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.update');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::forBusiness($activeBusiness->id)->findOrFail($id);

        // Check if user can edit this transfer
        if (!$this->canEditTransfer($transfer, $activeBusiness->id)) {
            abort(403, 'Cannot edit this transfer');
        }

        $request->validate([
            'transfer_date' => 'required|date',
            'to_entity_id' => [
                'required',
                'exists:business,id',
                Rule::notIn([$activeBusiness->id])
            ],
            'remarks' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Validate products belong to current business
            $productIds = collect($request->items)->pluck('product_id');
            $validProducts = Product::where('business_id', $activeBusiness->id)
                ->whereIn('id', $productIds)
                ->count();

            if ($validProducts !== $productIds->count()) {
                throw new \Exception('One or more products do not belong to your business');
            }

            // Update the transfer
            $transfer->update([
                'transfer_date' => Carbon::parse($request->transfer_date)->format('Y-m-d'),
                'to_entity_id' => $request->to_entity_id,
                'remarks' => $request->remarks,
                'updated_user_id' => Auth::id(),
            ]);

            // Delete existing items and create new ones
            $transfer->items()->delete();

            foreach ($request->items as $item) {
                InventoryTransferItem::create([
                    'inventory_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'requested_quantity' => $item['requested_quantity'],
                    'notes' => $item['notes'] ?? null,
                    'created_user_id' => Auth::id(),
                ]);
            }

            DB::commit();

            return redirect()->route('inventory_transfers.show', $transfer->id)
                ->with('success', 'Inventory transfer updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Send the inventory transfer
     */
    public function send(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.send');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::forBusiness($activeBusiness->id)->findOrFail($id);

        if (!$this->canSendTransfer($transfer, $activeBusiness->id)) {
            abort(403, 'Cannot send this transfer');
        }

        try {
            $transfer->send(Auth::id());

            return back()->with('success', 'Transfer sent successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Receive the inventory transfer
     */
    public function receive(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.receive');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::forBusiness($activeBusiness->id)->findOrFail($id);

        if (!$this->canReceiveTransfer($transfer, $activeBusiness->id)) {
            abort(403, 'Cannot receive this transfer');
        }

        $request->validate([
            'counted_quantities' => 'required|array',
            'counted_quantities.*' => 'required|numeric|min:0',
        ]);

        try {
            $transfer->receive(Auth::id(), $request->counted_quantities);

            return back()->with('success', 'Transfer received successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Reject the inventory transfer
     */
    public function reject(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.reject');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::forBusiness($activeBusiness->id)->findOrFail($id);

        if (!$this->canRejectTransfer($transfer, $activeBusiness->id)) {
            abort(403, 'Cannot reject this transfer');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $transfer->reject($request->comment, Auth::id());

            return back()->with('success', 'Transfer rejected successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel the inventory transfer
     */
    public function cancel(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.cancel');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::forBusiness($activeBusiness->id)->findOrFail($id);

        if (!$this->canCancelTransfer($transfer, $activeBusiness->id)) {
            abort(403, 'Cannot cancel this transfer');
        }

        try {
            $transfer->cancel(Auth::id());

            return back()->with('success', 'Transfer cancelled successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified inventory transfer (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.delete');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::forBusiness($activeBusiness->id)->findOrFail($id);

        // Only allow deletion of draft transfers by the creating business
        if (!$transfer->isDraft() || $transfer->from_entity_id !== $activeBusiness->id) {
            abort(403, 'Cannot delete this transfer');
        }

        $transfer->update(['deleted_user_id' => Auth::id()]);
        $transfer->delete();

        return redirect()->route('inventory_transfers.index')
            ->with('success', 'Transfer deleted successfully');
    }

    /**
     * Bulk delete transfers
     */
    public function bulk_destroy(Request $request)
    {
        Gate::authorize('inventory_transfers.delete');

        $activeBusiness = $request->activeBusiness;
        
        foreach ($request->ids as $id) {
            $transfer = InventoryTransfer::forBusiness($activeBusiness->id)->findOrFail($id);
            
            // Only allow deletion of draft transfers by the creating business
            if ($transfer->isDraft() && $transfer->from_entity_id === $activeBusiness->id) {
                $transfer->update(['deleted_user_id' => Auth::id()]);
                $transfer->delete();
            }
        }

        return redirect()->route('inventory_transfers.index')
            ->with('success', 'Selected transfers deleted successfully');
    }

    /**
     * Show trashed transfers
     */
    public function trash(Request $request)
    {
        Gate::authorize('inventory_transfers.view');

        $activeBusiness = $request->activeBusiness;
        
        $query = InventoryTransfer::onlyTrashed()->with([
            'fromEntity:id,name',
            'toEntity:id,name',
            'items:inventory_transfer_id,product_id,requested_quantity,counted_quantity',
            'items.product:id,name'
        ])->forBusiness($activeBusiness->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by direction (incoming/outgoing)
        if ($request->filled('direction')) {
            if ($request->direction === 'outgoing') {
                $query->outgoing($activeBusiness->id);
            } elseif ($request->direction === 'incoming') {
                $query->incoming($activeBusiness->id);
            }
        }

        // Search by transfer number or entity names
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhereHas('fromEntity', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('toEntity', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        // Handle pagination
        $perPage = $request->get('per_page', 50);
        $transfers = $query->paginate($perPage);

        // Get all businesses for filters
        $businesses = Business::where('id', '!=', $activeBusiness->id)
            ->where('status', 1)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Backend/User/InventoryTransfer/Trash', [
            'transfers' => $transfers->items(),
            'meta' => [
                'total' => $transfers->total(),
                'per_page' => $transfers->perPage(),
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'from' => $transfers->firstItem(),
                'to' => $transfers->lastItem(),
            ],
            'filters' => array_merge($request->only(['status', 'direction', 'search', 'business_id', 'date_range']), [
                'sorting' => $sorting,
            ]),
            'businesses' => $businesses,
        ]);
    }

    /**
     * Permanently delete a transfer
     */
    public function permanent_destroy(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.delete');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::onlyTrashed()->forBusiness($activeBusiness->id)->findOrFail($id);

        // Only allow permanent deletion by the creating business
        if ($transfer->from_entity_id !== $activeBusiness->id) {
            abort(403, 'Cannot permanently delete this transfer');
        }

        $transfer->forceDelete();

        return redirect()->route('inventory_transfers.trash')
            ->with('success', 'Transfer permanently deleted successfully');
    }

    /**
     * Bulk permanently delete transfers
     */
    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('inventory_transfers.delete');

        $activeBusiness = $request->activeBusiness;
        
        foreach ($request->ids as $id) {
            $transfer = InventoryTransfer::onlyTrashed()->forBusiness($activeBusiness->id)->findOrFail($id);
            
            // Only allow permanent deletion by the creating business
            if ($transfer->from_entity_id === $activeBusiness->id) {
                $transfer->forceDelete();
            }
        }

        return redirect()->route('inventory_transfers.trash')
            ->with('success', 'Selected transfers permanently deleted successfully');
    }

    /**
     * Restore a transfer
     */
    public function restore(Request $request, $id)
    {
        Gate::authorize('inventory_transfers.restore');

        $activeBusiness = $request->activeBusiness;

        $transfer = InventoryTransfer::onlyTrashed()->forBusiness($activeBusiness->id)->findOrFail($id);

        // Only allow restoration by the creating business
        if ($transfer->from_entity_id !== $activeBusiness->id) {
            abort(403, 'Cannot restore this transfer');
        }

        $transfer->restore();

        return redirect()->route('inventory_transfers.trash')
            ->with('success', 'Transfer restored successfully');
    }

    /**
     * Bulk restore transfers
     */
    public function bulk_restore(Request $request)
    {
        Gate::authorize('inventory_transfers.restore');

        $activeBusiness = $request->activeBusiness;
        
        foreach ($request->ids as $id) {
            $transfer = InventoryTransfer::onlyTrashed()->forBusiness($activeBusiness->id)->findOrFail($id);
            
            // Only allow restoration by the creating business
            if ($transfer->from_entity_id === $activeBusiness->id) {
                $transfer->restore();
            }
        }

        return redirect()->route('inventory_transfers.trash')
            ->with('success', 'Selected transfers restored successfully');
    }

    /**
     * Export transfers
     */
    public function export_transfers()
    {
        Gate::authorize('inventory_transfers.csv.export');

        // Implementation for export would go here
        // For now, just redirect back
        return redirect()->back()->with('success', 'Export functionality to be implemented');
    }

    /**
     * Import transfers
     */
    public function import_transfers(Request $request)
    {
        Gate::authorize('inventory_transfers.csv.import');

        // Implementation for import would go here
        // For now, just redirect back
        return redirect()->back()->with('success', 'Import functionality to be implemented');
    }

    // Authorization helper methods
    private function canViewTransfer($transfer, $businessId): bool
    {
        return $transfer->from_entity_id === $businessId || $transfer->to_entity_id === $businessId;
    }

    private function canEditTransfer($transfer, $businessId): bool
    {
        return $transfer->from_entity_id === $businessId && $transfer->canEdit();
    }

    private function canSendTransfer($transfer, $businessId): bool
    {
        return $transfer->from_entity_id === $businessId && $transfer->canSend();
    }

    private function canReceiveTransfer($transfer, $businessId): bool
    {
        return $transfer->to_entity_id === $businessId && $transfer->canReceive();
    }

    private function canRejectTransfer($transfer, $businessId): bool
    {
        return $transfer->to_entity_id === $businessId && $transfer->canReject();
    }

    private function canCancelTransfer($transfer, $businessId): bool
    {
        return $transfer->from_entity_id === $businessId && $transfer->canCancel();
    }
}
