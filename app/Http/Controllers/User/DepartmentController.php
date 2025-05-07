<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Validator;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(function ($request, $next) {

            if (package()->payroll_module != 1) {
                if (!$request->ajax()) {
                    return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
                } else {
                    return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $search = $request->search;
        $per_page = $request->per_page ?? 50;

        $query = Department::query()->where('business_id', $request->activeBusiness->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('descriptions', 'like', "%$search%");
            });
        }

        $departments = $query->orderByDesc('id')->paginate($per_page);

        return Inertia::render('Backend/User/Department/List', [
            'departments' => $departments->items(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'per_page' => $departments->perPage(),
                'from' => $departments->firstItem(),
                'to' => $departments->lastItem(),
                'total' => $departments->total(),
                'last_page' => $departments->lastPage(),
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.department.modal.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department               = new Department();
        $department->name         = $request->input('name');
        $department->descriptions = $request->input('descriptions');
        $department->business_id  = $request->activeBusiness->id;

        $department->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Department ' . $department->name;
        $audit->save();

        session()->flash('success', 'Department created successfully');
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $department = Department::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.department.modal.view', compact('department', 'id'));
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $department = Department::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.department.modal.edit', compact('department', 'id'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department               = Department::find($id);
        $department->name         = $request->input('name');
        $department->descriptions = $request->input('descriptions');
        $department->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Department ' . $department->name;
        $audit->save();

        session()->flash('success', 'Department updated successfully');
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $department = Department::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Department ' . $department->name;
        $audit->save();

        try {
            $department->delete();
            return redirect()->route('departments.index')->with('success', _lang('Deleted Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('departments.index')->with('error', _lang('This item is already exists in other entity'));
        }
    }

    /**
     * Bulk Delete
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_destroy(Request $request) {
        $ids = $request->ids;
        $departments = Department::whereIn('id', $ids)->get();
        
        foreach($departments as $department) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Department ' . $department->name;
            $audit->save();

            try {
                $department->delete();
            } catch (\Exception $e) {
                // Continue with the next department
            }
        }
        
        return back()->with('success', 'Selected departments deleted successfully');
    }
}