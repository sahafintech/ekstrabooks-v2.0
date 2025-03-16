<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    public function index()
    {
        $categories = SubCategory::all();
        return view('backend.user.category.list', compact('categories'));
    }

    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.category.create');
        }
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

    public function edit(Request $request, $id)
    {
        $category = SubCategory::find($id);
        return view('backend.user.category.edit', compact('category', 'id'));
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
            if (file_exists($image_path) && $$category->image !== 'default.png') {
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
}
