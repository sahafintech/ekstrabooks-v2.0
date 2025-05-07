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
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = Brands::orderBy("id", "desc");

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $brands = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/ProductBrand/List', [
            'brands' => $brands->items(),
            'meta' => [
                'current_page' => $brands->currentPage(),
                'from' => $brands->firstItem(),
                'last_page' => $brands->lastPage(),
                'links' => $brands->linkCollection(),
                'path' => $brands->path(),
                'per_page' => $brands->perPage(),
                'to' => $brands->lastItem(),
                'total' => $brands->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
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

    public function bulk_destroy(Request $request)
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
