<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

final class RoleController extends Controller
{
    public function index()
    {
        Gate::authorize('roles.view');

        $roles = Role::with('permissions')
            ->get()
            ->map(static function (Role $role): array {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'permissions_count' => $role->permissions->count(),
                    'users_count' => $role->users()->count(),
                ];
            });

        $permissions = Permission::all()
            ->groupBy(static function (Permission $permission): string {
                $parts = explode('.', $permission->name);
                return $parts[0] ?? 'other';
            })
            ->map(static function ($group): array {
                return $group->map(static function (Permission $permission): array {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ];
                })->values()->toArray();
            })
            ->toArray();

            return Inertia::render('Backend/User/Business/roles', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('roles.create');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (isset($validated['permissions']) && is_array($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Clear permission cache globally when role permissions change
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        Gate::authorize('roles.edit');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions']) && is_array($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        } else {
            $role->syncPermissions([]);
        }

        // Clear permission cache for all users with this role and globally
        foreach ($role->users()->get() as $user) {
            $user->forgetCachedPermissions();
        }
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        Gate::authorize('roles.delete');

        // Prevent deleting Owner role
        if ($role->name === 'Owner') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Cannot delete Owner role.');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Cannot delete role that is assigned to users.');
        }

        $role->delete();

        // Clear permission cache globally when role is deleted
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
