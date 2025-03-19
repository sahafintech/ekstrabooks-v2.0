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
        // Query for main categories
        $query = MainCategory::query();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply column filters if provided
        if ($request->has('columnFilters') && !empty($request->get('columnFilters'))) {
            $columnFilters = $request->get('columnFilters');

            // If the column filters are a JSON string, decode them
            if (is_string($columnFilters)) {
                $columnFilters = json_decode($columnFilters, true);
            }

            if (is_array($columnFilters)) {
                foreach ($columnFilters as $filter) {
                    if (isset($filter['id']) && isset($filter['value'])) {
                        $column = $filter['id'];
                        $value = $filter['value'];
                        $query->where($column, 'like', "%{$value}%");
                    }
                }
            }
        }

        // Apply sorting if provided
        if ($request->has('sorting') && !empty($request->get('sorting'))) {
            $sorting = $request->get('sorting');

            // If the sorting is a JSON string, decode it
            if (is_string($sorting)) {
                $sorting = json_decode($sorting, true);
            }

            if (is_array($sorting)) {
                foreach ($sorting as $sort) {
                    if (isset($sort['id']) && isset($sort['desc'])) {
                        $column = $sort['id'];
                        $direction = $sort['desc'] ? 'desc' : 'asc';
                        $query->orderBy($column, $direction);
                    }
                }
            }
        } else {
            // Default sorting
            $query->orderBy('id', 'desc');
        }

        // Apply pagination
        $perPage = $request->get('per_page', 10);
        $categories = $query->paginate($perPage);

        // If this is an Inertia request, return JSON response
        return Inertia::render('Backend/User/MainCategory/List', [
            'categories' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'last_page' => $categories->lastPage(),
                'total' => $categories->total(),
                'links' => $categories->linkCollection(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'columnFilters' => is_string($request->get('columnFilters'))
                    ? json_decode($request->get('columnFilters'), true)
                    : $request->get('columnFilters', []),
                'sorting' => is_string($request->get('sorting'))
                    ? json_decode($request->get('sorting'), true)
                    : $request->get('sorting', []),
            ]
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

        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return redirect()->route('main_categories.index');
        }

        return redirect()->route('main_categories.index')->with('success', _lang('Main Category Created'));
    }

    public function destroy(Request $request, $id)
    {
        $category = MainCategory::find($id);

        // delete image if exists and not default
        $image_path = public_path() . "/uploads/media/" . $category->image;
        if (file_exists($image_path) && $category->image !== 'default.png') {
            unlink($image_path);
        }

        $category->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Category Deleted: ' . $category->name;
        $audit->save();

        return redirect()->route('main_categories.index')->with('success', _lang('Main Category Deleted'));
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

    public function destroy_multiple(Request $request)
    {
        $ids = $request->ids;
        
        if (!is_array($ids) && !is_null($ids)) {
            // Try to decode JSON if it's a string
            $ids = json_decode($ids, true);
        }
        
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['error' => 'No valid IDs provided', 'received' => $request->all()], 400);
        }
        
        $deleted = [];
        
        foreach ($ids as $id) {
            $category = MainCategory::find($id);
            
            if ($category) {
                // delete image if exists and not default
                $image_path = public_path() . "/uploads/media/" . $category->image;
                if (file_exists($image_path) && $category->image !== 'default.png') {
                    unlink($image_path);
                }
                
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

    public function getSubCategories(Request $request)
    {
        $subCategories = SubCategory::where('main_category_id', $request->main_category_id)->get();

        return response()->json($subCategories);
    }
}
