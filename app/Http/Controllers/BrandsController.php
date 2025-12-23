<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Brands;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class BrandsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('brands.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = Brands::orderBy($sortColumn, $sortDirection);

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
            'trashed_brands' => Brands::onlyTrashed()->count()
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('brands.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = Brands::onlyTrashed()->orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $brands = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/ProductBrand/Trash', [
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
        Gate::authorize('brands.create');
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
        Gate::authorize('brands.delete');
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

    public function permanent_destroy($id)
    {
        Gate::authorize('brands.delete');
        $brand = Brands::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Permanently Deleted: ' . $brand->name;
        $audit->save();

        $brand->forceDelete();

        return redirect()->route('brands.trash')->with('success', _lang('Brand Permanently Deleted'));
    }

    public function restore($id)
    {
        Gate::authorize('brands.restore');
        $brand = Brands::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Brand Restored: ' . $brand->name;
        $audit->save();

        $brand->restore();

        return redirect()->route('brands.trash')->with('success', _lang('Brand Restored'));
    }

    public function bulk_destroy(Request $request)
    {
        Gate::authorize('brands.delete');
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

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('brands.delete');
        if ($request->has('ids')) {
            $ids = $request->ids;
            $brands = Brands::whereIn('id', $ids)->get();

            foreach ($brands as $brand) {
                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Brand Permanently Deleted: ' . $brand->name;
                $audit->save();

                $brand->forceDelete();
            }

            return redirect()->route('brands.trash')->with('success', _lang('Brands Permanently Deleted'));
        }

        return redirect()->route('brands.trash')->with('error', _lang('No brands selected'));
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('brands.restore');
        if ($request->has('ids')) {
            $ids = $request->ids;
            $brands = Brands::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($brands as $brand) {
                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Brand Restored: ' . $brand->name;
                $audit->save();

                $brand->restore();
            }

            return redirect()->route('brands.trash')->with('success', _lang('Brands Restored'));
        }

        return redirect()->route('brands.trash')->with('error', _lang('No brands selected'));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('brands.update');
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
