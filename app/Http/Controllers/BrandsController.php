<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Brands;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandsController extends Controller
{
    public function index(Request $request)
    {
        // Create a query builder for brands
        $query = Brands::query();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply column filters if provided
        if ($request->has('columnFilters') && !empty($request->get('columnFilters'))) {
            $columnFilters = json_decode($request->get('columnFilters'), true);
            foreach ($columnFilters as $filter) {
                if (isset($filter['id']) && isset($filter['value'])) {
                    $query->where($filter['id'], 'like', "%{$filter['value']}%");
                }
            }
        }

        // Apply sorting if provided
        if ($request->has('sorting') && !empty($request->get('sorting'))) {
            $sorting = json_decode($request->get('sorting'), true);
            foreach ($sorting as $sort) {
                $query->orderBy($sort['id'], $sort['desc'] ? 'desc' : 'asc');
            }
        } else {
            // Default sorting
            $query->orderBy('id', 'desc');
        }

        // Paginate the results
        $per_page = $request->get('per_page', 10);
        $brands = $query->paginate($per_page);

        // If Inertia request, return Inertia view
        return Inertia::render('Backend/User/ProductBrand/List', [
            'brands' => $brands->items(),
            'meta' => [
                'current_page' => $brands->currentPage(),
                'per_page' => $brands->perPage(),
                'total' => $brands->total(),
                'last_page' => $brands->lastPage()
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'columnFilters' => json_decode($request->get('columnFilters', '[]')),
                'sorting' => json_decode($request->get('sorting', '[]'))
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
        ]);

        $brand = new Brands();
        $brand->name = $data['name'];
        $brand->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Created: ' . $brand->name;
        $audit->save();

        return redirect()->route('brands.index')->with('success', _lang('Brand Created'));
    }

    public function destroy($id)
    {
        $brand = Brands::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Deleted: ' . $brand->name;
        $audit->save();

        $brand->delete();

        return redirect()->route('brands.index')->with('success', _lang('Brand Deleted'));
    }

    public function destroy_multiple(Request $request)
    {
        if ($request->has('ids')) {
            $ids = $request->ids;
            $brands = Brands::whereIn('id', $ids)->get();
            
            foreach ($brands as $brand) {
                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Brand Deleted: ' . $brand->name;
                $audit->save();
                
                $brand->delete();
            }
            
            return redirect()->route('brands.index')->with('success', _lang('Brands Deleted'));
        }
        
        return redirect()->route('brands.index')->with('error', _lang('No brands selected'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $brand = Brands::find($id);
        $brand->name = $request->name;
        $brand->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Updated: ' . $brand->name;
        $audit->save();

        return redirect()->route('brands.index')->with('success', _lang('Brand Updated'));
    }
}
