<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MainCategory;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MainCategoryController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = MainCategory::orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $categories = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/MainCategory/List', [
            'categories' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'from' => $categories->firstItem(),
                'last_page' => $categories->lastPage(),
                'links' => $categories->linkCollection(),
                'path' => $categories->path(),
                'per_page' => $categories->perPage(),
                'to' => $categories->lastItem(),
                'total' => $categories->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
            'trashed_categories' => MainCategory::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = MainCategory::onlyTrashed()->orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $categories = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/MainCategory/Trash', [
            'categories' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'from' => $categories->firstItem(),
                'last_page' => $categories->lastPage(),
                'links' => $categories->linkCollection(),
                'path' => $categories->path(),
                'per_page' => $categories->perPage(),
                'to' => $categories->lastItem(),
                'total' => $categories->total(),
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/media/'), $image_name);
            $data['image'] = $image_name;
        }

        $category = new MainCategory();
        $category->name = $data['name'];
        $category->image = $data['image'] ?? 'default.png';
        $category->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Category Created: ' . $category->name;
        $audit->save();

        return redirect()->route('main_categories.index')->with('success', _lang('Main Category Created'));
    }

    public function destroy($id)
    {
        $category = MainCategory::find($id);

        $category->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Category Deleted: ' . $category->name;
        $audit->save();

        return redirect()->route('main_categories.index')->with('success', _lang('Main Category Deleted'));
    }

    public function permanent_destroy($id)
    {
        $category = MainCategory::onlyTrashed()->find($id);

        // delete image if exists and not default
        $image_path = public_path() . "/uploads/media/" . $category->image;
        if (file_exists($image_path) && $category->image !== 'default.png') {
            unlink($image_path);
        }

        $category->forceDelete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Category Permanently Deleted: ' . $category->name;
        $audit->save();

        return redirect()->route('main_categories.trash')->with('success', _lang('Main Category Permanently Deleted'));
    }

    public function restore($id)
    {
        $category = MainCategory::onlyTrashed()->find($id);

        $category->restore();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Category Restored: ' . $category->name;
        $audit->save();

        return redirect()->route('main_categories.trash')->with('success', _lang('Main Category Restored'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            '_method' => 'sometimes|required|in:PUT,PATCH',
        ]);

        $category = MainCategory::find($id);
        $category->name = $request->name;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/media/'), $image_name);

            // delete old image
            $image_path = public_path() . "/uploads/media/" . $category->image;
            if (file_exists($image_path) && $category->image !== 'default.png') {
                unlink($image_path);
            }

            $category->image = $image_name;
        }

        $category->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Main Category Updated: ' . $category->name;
        $audit->save();

        return redirect()->route('main_categories.index')->with('success', _lang('Main Category Updated'));
    }

    public function bulk_destroy(Request $request)
    {
        $ids = $request->ids;

        $deleted = [];

        foreach ($ids as $id) {
            $category = MainCategory::find($id);

            if ($category) {

                $category->delete();
                $deleted[] = $id;

                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Category Deleted: ' . $category->name;
                $audit->save();
            }
        }

        return redirect()->route('main_categories.index')->with('success', _lang('Main Categories Deleted'));
    }
    
    public function bulk_permanent_destroy(Request $request)
    {
        $ids = $request->ids;

        $deleted = [];

        foreach ($ids as $id) {
            $category = MainCategory::onlyTrashed()->find($id);

            if ($category) {
                // delete image if exists and not default
                $image_path = public_path() . "/uploads/media/" . $category->image;
                if (file_exists($image_path) && $category->image !== 'default.png') {
                    unlink($image_path);
                }

                $category->forceDelete();
                $deleted[] = $id;

                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Category Permanently Deleted: ' . $category->name;
                $audit->save();
            }
        }

        return redirect()->route('main_categories.trash')->with('success', _lang('Main Categories Permanently Deleted'));
    }

    public function bulk_restore(Request $request)
    {
        $ids = $request->ids;

        $deleted = [];

        foreach ($ids as $id) {
            $category = MainCategory::onlyTrashed()->find($id);

            if ($category) {
                $category->restore();
                $deleted[] = $id;

                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = auth()->user()->id;
                $audit->event = 'Category Restored: ' . $category->name;
                $audit->save();
            }
        }

        return redirect()->route('main_categories.trash')->with('success', _lang('Main Categories Restored'));
    }

    public function getSubCategories(Request $request)
    {
        $subCategories = SubCategory::where('main_category_id', $request->main_category_id)->get();

        return response()->json($subCategories);
    }
}
