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
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = SubCategory::with('mainCategory')->orderBy("id", "desc");

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $categories = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/SubCategory/List', [
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
            'mainCategories' => MainCategory::all()
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

    public function destroy($id)
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

    public function bulk_destroy(Request $request)
    {
        $ids = $request->ids;

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
