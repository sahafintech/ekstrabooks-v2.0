<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesController extends Controller
{
    /**
     * Display roles listing page.
     */
    public function index()
    {
        Gate::authorize('roles.view');
        $roles = Role::where('guard_name', 'web')
            ->withCount('users', 'permissions')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                ];
            });

        // Get permissions grouped by prefix
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $permissions = [];
        
        foreach ($allPermissions as $permission) {
            $parts = explode('.', $permission->name);
            $group = $parts[0];
            
            if (!isset($permissions[$group])) {
                $permissions[$group] = [];
            }
            
            $permissions[$group][] = [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        }

        return Inertia::render('Backend/User/Business/roles', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        Gate::authorize('roles.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', _lang('Role created successfully.'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        Gate::authorize('roles.update');

        $role = Role::findOrFail($id);

        // Prevent editing Owner role name
        $rules = [
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];

        if ($role->name !== 'Owner') {
            $rules['name'] = ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id];
        }

        $validated = $request->validate($rules);

        if (isset($validated['name']) && $role->name !== 'Owner') {
            $role->name = $validated['name'];
            $role->save();
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Clear permission cache for all users with this role
        foreach ($role->users()->get() as $user) {
            $user->forgetCachedPermissions();
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', _lang('Role updated successfully.'));
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id)
    {
        Gate::authorize('roles.delete');

        $role = Role::findOrFail($id);

        // Prevent deleting Owner role
        if ($role->name === 'Owner') {
            return redirect()->back()->with('error', _lang('Cannot delete the Owner role.'));
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', _lang('Cannot delete role that is assigned to users.'));
        }

        $role->delete();

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', _lang('Role deleted successfully.'));
    }

    /**
     * Bulk destroy roles.
     */
    public function bulk_destroy(Request $request)
    {
        Gate::authorize('roles.delete');

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $roles = Role::whereIn('id', $validated['ids'])
            ->where('name', '!=', 'Owner')
            ->get();

        foreach ($roles as $role) {
            if ($role->users()->count() === 0) {
                $role->delete();
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', _lang('Roles deleted successfully.'));
    }
}
