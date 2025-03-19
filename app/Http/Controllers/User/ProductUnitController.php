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
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ProductUnit::query();

        // Search functionality
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where('unit', 'like', "%{$search}%");
        }

        // Column filtering
        if ($request->has('columnFilters') && !empty($request->get('columnFilters'))) {
            $columnFilters = json_decode($request->columnFilters, true);
            foreach ($columnFilters as $filter) {
                $query->where($filter['id'], 'like', "%{$filter['value']}%");
            }
        }

        // Sorting
        if ($request->has('sorting') && !empty($request->get('sorting'))) {
            $sorting = json_decode($request->sorting, true);
            foreach ($sorting as $sort) {
                $query->orderBy($sort['id'], $sort['desc'] ? 'desc' : 'asc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $productUnits = $query->paginate($perPage, ['*'], 'page', $page);

        if ($request->wantsJson()) {
            return response()->json($productUnits);
        }

        return Inertia::render('Backend/User/ProductUnit/List', [
            'product_units' => $productUnits->items(),
            'meta' => [
                'total' => $productUnits->total(),
                'per_page' => $productUnits->perPage(),
                'current_page' => $productUnits->currentPage(),
                'last_page' => $productUnits->lastPage(),
                'links' => $productUnits->linkCollection()->toArray(),
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'columnFilters' => !empty($request->columnFilters) ? json_decode($request->columnFilters, true) : [],
                'sorting' => !empty($request->sorting) ? json_decode($request->sorting, true) : [],
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
    public function destroy_multiple(Request $request)
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
}
