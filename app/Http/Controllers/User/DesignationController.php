<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DesignationController extends Controller {

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
        $per_page = $request->per_page ?? 10;

        $query = Designation::query()->where('designations.business_id', $request->activeBusiness->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('designations.name', 'like', "%$search%")
                  ->orWhere('designations.descriptions', 'like', "%$search%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        // Handle relationship sorting
        if (str_contains($sortColumn, '.')) {
            $parts = explode('.', $sortColumn);
            $relation = $parts[0];
            $column = $parts[1];
            $query->join($relation . 's', 'designations.' . $relation . '_id', '=', $relation . 's.id')
                  ->where($relation . 's.business_id', $request->activeBusiness->id)
                  ->orderBy($relation . 's.' . $column, $sortDirection)
                  ->select('designations.*');
        } else {
            $query->orderBy('designations.' . $sortColumn, $sortDirection);
        }

        $designations = $query->with('department')->paginate($per_page);
        $departments = Department::where('business_id', $request->activeBusiness->id)->get();

        return Inertia::render('Backend/User/Designation/List', [
            'designations' => $designations->items(),
            'departments' => $departments,
            'meta' => [
                'current_page' => $designations->currentPage(),
                'per_page' => $designations->perPage(),
                'from' => $designations->firstItem(),
                'to' => $designations->lastItem(),
                'total' => $designations->total(),
                'last_page' => $designations->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
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
            return view('backend.user.designation.modal.create');
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
            'name'          => 'required',
            'department_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $designation                = new Designation();
        $designation->name          = $request->input('name');
        $designation->descriptions  = $request->input('descriptions');
        $designation->department_id = $request->input('department_id');
        $designation->business_id   = $request->activeBusiness->id;

        $designation->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Designation ' . $designation->name;
        $audit->save();

        session()->flash('success', 'Designation created successfully');
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $designation = Designation::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.designation.modal.view', compact('designation', 'id'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $designation = Designation::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.designation.modal.edit', compact('designation', 'id'));
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
            'name'          => 'required',
            'department_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $designation                = Designation::find($id);
        $designation->name          = $request->input('name');
        $designation->descriptions  = $request->input('descriptions');
        $designation->department_id = $request->input('department_id');
        $designation->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Designation ' . $designation->name;
        $audit->save();

        session()->flash('success', 'Designation updated successfully');
        return back();
    }

    public function get_designations(Request $request, $department_id) {
        $designations = Designation::where('department_id', $department_id)
            ->where('business_id', $request->activeBusiness->id)
            ->get();
        return response()->json($designations);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $designation = Designation::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Designation ' . $designation->name;
        $audit->save();

        try {
            $designation->delete();
            return redirect()->route('designations.index')->with('success', _lang('Deleted Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('designations.index')->with('error', _lang('This item is already exists in other entity'));
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
        $designations = Designation::whereIn('id', $ids)->get();
        
        foreach($designations as $designation) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Designation ' . $designation->name;
            $audit->save();

            try {
                $designation->delete();
            } catch (\Exception $e) {
                // Continue with the next designation
            }
        }
        
        return back()->with('success', 'Selected designations deleted successfully');
    }
}