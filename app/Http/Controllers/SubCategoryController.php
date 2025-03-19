<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MainCategory;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubCategoryController extends Controller
{
    public function index(Request $request)
    {
        // Create a query builder for subcategories
        $query = SubCategory::query()->with('mainCategory');

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
                    if ($filter['id'] === 'mainCategory.name') {
                        $query->whereHas('mainCategory', function($q) use ($filter) {
                            $q->where('id', $filter['value']);
                        });
                    } else {
                        $query->where($filter['id'], 'like', "%{$filter['value']}%");
                    }
                }
            }
        }

        // Apply sorting if provided
        if ($request->has('sorting') && !empty($request->get('sorting'))) {
            $sorting = json_decode($request->get('sorting'), true);
            foreach ($sorting as $sort) {
                if ($sort['id'] === 'mainCategory.name') {
                    $query->join('main_categories', 'sub_categories.main_category_id', '=', 'main_categories.id')
                          ->orderBy('main_categories.name', $sort['desc'] ? 'desc' : 'asc')
                          ->select('sub_categories.*');
                } else {
                    $query->orderBy($sort['id'], $sort['desc'] ? 'desc' : 'asc');
                }
            }
        } else {
            // Default sorting
            $query->orderBy('id', 'desc');
        }

        // Paginate the results
        $per_page = $request->get('per_page', 10);
        $subcategories = $query->paginate($per_page);
        
        // Get all main categories for the dropdown
        $mainCategories = MainCategory::all();

        return Inertia::render('Backend/User/SubCategory/List', [
            'subcategories' => $subcategories->items(),
            'mainCategories' => $mainCategories,
            'meta' => [
                'current_page' => $subcategories->currentPage(),
                'per_page' => $subcategories->perPage(),
                'total' => $subcategories->total(),
                'last_page' => $subcategories->lastPage()
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'columnFilters' => json_decode($request->get('columnFilters', '[]')),
                'sorting' => json_decode($request->get('sorting', '[]'))
            ]
        ]);
    }

    public function store()
    {
        $data = request()->validate([
            'name' => 'required',
            'main_category_id' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if (request()->hasFile('image')) {
            $image = request()->file('image');
            $image_name = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/media/'), $image_name);
            $data['image'] = $image_name;
        }

        $category = new SubCategory();
        $category->main_category_id = $data['main_category_id'];
        $category->name = $data['name'];
        $category->image = $data['image'] ?? 'default.png';
        $category->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Category Created: ' . $category->name;
        $audit->save();

        return redirect()->route('sub_categories.index')->with('success', _lang('Category Created'));
    }

    public function destroy(Request $request, $id)
    {
        $category = SubCategory::find($id);

        // delete image
        $image_path = public_path() . "/uploads/media/" . $category->image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        $category->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Category Deleted: ' . $category->name;
        $audit->save();

        return redirect()->route('sub_categories.index')->with('success', _lang('Category Deleted'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'main_category_id' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $category = SubCategory::find($id);
        $category->main_category_id = $request->main_category_id;
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
        $audit->event = 'Category Updated: ' . $category->name;
        $audit->save();

        return redirect()->route('sub_categories.index')->with('success', _lang('Category Updated'));
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
            $category = SubCategory::find($id);
            
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
                $audit->event = 'SubCategory Deleted: ' . $category->name;
                $audit->save();
            }
        }
        
        return redirect()->route('sub_categories.index')->with('success', _lang('SubCategories Deleted'));
    }
}
