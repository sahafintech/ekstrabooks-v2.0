<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = Role::query();

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Get roles with pagination
        $roles = $query->paginate($per_page);
        return Inertia::render('Backend/User/RoleAndPermission/List', [
            'roles' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'from' => $roles->firstItem(),
                'last_page' => $roles->lastPage(),
                'per_page' => $per_page,
                'to' => $roles->lastItem(),
                'total' => $roles->total(),
                'links' => $roles->linkCollection(),
                'path' => $roles->path(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting
            ],
            'trashed_roles' => Role::onlyTrashed()->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Backend/User/RoleAndPermission/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|max:50',
            'description' => '',
        ]);

        if ($validator->fails()) {
            return redirect()->route('roles.create')
                ->withErrors($validator)
                ->withInput();
        }

        $role              = new Role();
        $role->name        = $request->input('name');
        $role->description = $request->input('description');
        $role->user_id     = Auth::id();

        $role->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Role ' . $role->name;
        $audit->save();

        return redirect()->route('roles.index')->with('success', _lang('Saved Sucessfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::findOrFail($id);
        return Inertia::render('Backend/User/RoleAndPermission/Edit', ['role' => $role]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->route('roles.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $role              = Role::findOrFail($id);
        $role->name        = $request->input('name');
        $role->description = $request->input('description');
        $role->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Role ' . $role->name;
        $audit->save();

        return redirect()->route('roles.index')->with('success', _lang('Updated Sucessfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Role ' . $role->name;
        $audit->save();

        try {
            $role->delete();
            return redirect()->route('roles.index')->with('success', _lang('Deleted Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('roles.index')->with('error', _lang('This item is already exists in other entity'));
        }
    }

    public function bulk_destroy(Request $request)
    {
        $ids = $request->ids;
        $roles = Role::whereIn('id', $ids)->get();
        
        foreach($roles as $role) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Role ' . $role->name;
            $audit->save();

            try {
                $role->delete();
            } catch (\Exception $e) {
                // Continue with the next role
            }
        }
        
        return back()->with('success', 'Selected roles deleted successfully');
    }

    /**
     * Display a listing of trashed roles.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request) {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = Role::onlyTrashed();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];
            $query->orderBy($column, $direction);
        }

        $roles = $query->paginate($per_page);

        return Inertia::render('Backend/User/RoleAndPermission/Trash', [
            'roles' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'per_page' => $roles->perPage(),
                'from' => $roles->firstItem(),
                'to' => $roles->lastItem(),
                'total' => $roles->total(),
                'last_page' => $roles->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
        ]);
    }

    /**
     * Restore the specified role from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id)
    {
        $role = Role::onlyTrashed()->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Role ' . $role->name;
        $audit->save();

        $role->restore();

        return redirect()->route('roles.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Bulk restore selected roles from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {
        foreach ($request->ids as $id) {
            $role = Role::onlyTrashed()->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Role ' . $role->name;
            $audit->save();

            $role->restore();
        }

        return redirect()->route('roles.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Permanently delete the specified role from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanent_destroy(Request $request, $id)
    {
        $role = Role::onlyTrashed()->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Role ' . $role->name;
        $audit->save();

        $role->forceDelete();

        return redirect()->route('roles.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    /**
     * Bulk permanently delete selected roles from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $role = Role::onlyTrashed()->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Role ' . $role->name;
            $audit->save();

            $role->forceDelete();
        }

        return redirect()->route('roles.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }
}
