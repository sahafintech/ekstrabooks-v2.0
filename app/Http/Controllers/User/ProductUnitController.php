<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Validator;

class ProductUnitController extends Controller
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
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = ProductUnit::orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('unit', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $units = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/ProductUnit/List', [
            'units' => $units->items(),
            'meta' => [
                'current_page' => $units->currentPage(),
                'from' => $units->firstItem(),
                'last_page' => $units->lastPage(),
                'links' => $units->linkCollection(),
                'path' => $units->path(),
                'per_page' => $units->perPage(),
                'to' => $units->lastItem(),
                'total' => $units->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
            'trashed_units' => ProductUnit::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = ProductUnit::onlyTrashed()->orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('unit', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $units = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/ProductUnit/Trash', [
            'units' => $units->items(),
            'meta' => [
                'current_page' => $units->currentPage(),
                'from' => $units->firstItem(),
                'last_page' => $units->lastPage(),
                'links' => $units->linkCollection(),
                'path' => $units->path(),
                'per_page' => $units->perPage(),
                'to' => $units->lastItem(),
                'total' => $units->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
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
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.product_unit.modal.create');
        }
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
            'unit' => 'required|max:30',
        ]);

        $productunit = new ProductUnit();
        $productunit->unit = $request->input('unit');
        $productunit->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Created' . ' ' . $productunit->unit;
        $audit->save();

        return redirect()->route('product_units.index')->with('success', _lang('Product Unit Created Successfully'));
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
            'unit' => 'required|max:30',
        ]);

        $productunit = ProductUnit::find($id);
        $productunit->unit = $request->input('unit');
        $productunit->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Updated' . ' ' . $productunit->unit;
        $audit->save();

        return redirect()->route('product_units.index')->with('success', _lang('Product Unit Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $productunit = ProductUnit::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Deleted' . ' ' . $productunit->unit;
        $audit->save();

        $productunit->delete();
        return redirect()->route('product_units.index')->with('success', _lang('Product Unit Deleted Successfully'));
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_destroy(Request $request)
    {
        if ($request->has('ids')) {
            $ids = $request->ids;
            $units = ProductUnit::whereIn('id', $ids)->get();

            foreach ($units as $unit) {
                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Product Unit Deleted: ' . $unit->unit;
                $audit->save();

                $unit->delete();
            }

            return redirect()->route('product_units.index')->with('success', _lang('Product Units Deleted'));
        }

        return redirect()->route('product_units.index')->with('error', _lang('No product units selected'));
    }

    public function permanent_destroy($id)
    {
        $productunit = ProductUnit::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Permanently Deleted' . ' ' . $productunit->unit;
        $audit->save();

        $productunit->forceDelete();
        return redirect()->route('product_units.trash')->with('success', _lang('Product Unit Permanently Deleted Successfully'));
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {
        if ($request->has('ids')) {
            $ids = $request->ids;
            $units = ProductUnit::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($units as $unit) {
                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Product Unit Permanently Deleted: ' . $unit->unit;
                $audit->save();

                $unit->forceDelete();
            }

            return redirect()->route('product_units.trash')->with('success', _lang('Product Units Permanently Deleted'));
        }

        return redirect()->route('product_units.trash')->with('error', _lang('No product units selected'));
    }

    public function restore($id)
    {
        $productunit = ProductUnit::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Unit Restored' . ' ' . $productunit->unit;
        $audit->save();

        $productunit->restore();
        return redirect()->route('product_units.trash')->with('success', _lang('Product Unit Restored Successfully'));
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {
        if ($request->has('ids')) {
            $ids = $request->ids;
            $units = ProductUnit::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($units as $unit) {
                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Product Unit Restored: ' . $unit->unit;
                $audit->save();

                $unit->restore();
            }

            return redirect()->route('product_units.trash')->with('success', _lang('Product Units Restored'));
        }

        return redirect()->route('product_units.trash')->with('error', _lang('No product units selected'));
    }
}
