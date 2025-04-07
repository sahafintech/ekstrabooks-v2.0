<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Validator;

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
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');

        $query = Role::orderBy("id", "desc");

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $roles = $query->paginate($per_page)->withQueryString();
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
                'search' => $search
            ]
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

        $role->save();

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
        $role->delete();
        return redirect()->route('roles.index')->with('success', _lang('Deleted Sucessfully'));
    }
}
