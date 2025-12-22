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
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('designations.view');
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
            'trashed_designations' => Designation::onlyTrashed()->count(),
        ]);
    }

    /**
     * Display a listing of trashed designations.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request) {
        Gate::authorize('designations.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = Designation::onlyTrashed()->with('department')
            ->where('designations.business_id', $request->activeBusiness->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('designations.name', 'like', "%$search%")
                    ->orWhere('designations.descriptions', 'like', "%$search%");
            });
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];

            // Handle relationship sorting
            if (str_contains($column, '.')) {
                [$relation, $field] = explode('.', $column);
                $query->join($relation . 's', 'designations.' . $relation . '_id', '=', $relation . 's.id')
                    ->where($relation . 's.business_id', $request->activeBusiness->id)
                    ->orderBy($relation . 's.' . $field, $direction)
                    ->select('designations.*');
            } else {
                $query->orderBy('designations.' . $column, $direction);
            }
        }

        $designations = $query->paginate($per_page);

        return Inertia::render('Backend/User/Designation/Trash', [
            'designations' => $designations->items(),
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
        Gate::authorize('designations.create');
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
        Gate::authorize('designations.create');
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
        Gate::authorize('designations.view');
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
        Gate::authorize('designations.update');
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
        Gate::authorize('designations.update');
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
        Gate::authorize('designations.delete');
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
        Gate::authorize('designations.delete');
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

    /**
     * Permanently delete the specified designation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanent_destroy(Request $request, $id) {
        Gate::authorize('designations.delete');
        $designation = Designation::onlyTrashed()->where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Designation ' . $designation->name;
        $audit->save();

        $designation->forceDelete();

        return redirect()->route('designations.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    /**
     * Bulk permanently delete selected designations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request) {
        Gate::authorize('designations.delete');
        foreach ($request->ids as $id) {
            $designation = Designation::onlyTrashed()->where('business_id', $request->activeBusiness->id)
                ->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Designation ' . $designation->name;
            $audit->save();

            $designation->forceDelete();
        }

        return redirect()->route('designations.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    /**
     * Restore the specified designation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id) {
        $designation = Designation::onlyTrashed()->where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Designation ' . $designation->name;
        $audit->save();

        $designation->restore();

        return redirect()->route('designations.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Bulk restore selected designations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request) {
        Gate::authorize('designations.restore');
        foreach ($request->ids as $id) {
            $designation = Designation::onlyTrashed()->where('business_id', $request->activeBusiness->id)
                ->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Designation ' . $designation->name;
            $audit->save();

            $designation->restore();
        }

        return redirect()->route('designations.trash')->with('success', _lang('Restored Successfully'));
    }
}