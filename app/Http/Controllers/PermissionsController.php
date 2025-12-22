<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class PermissionsController extends Controller
{
    /**
     * Display permissions listing page.
     */
    public function index()
    {
        // Get all permissions grouped by prefix
        $allPermissions = Permission::where('guard_name', 'web')
            ->with('roles')
            ->get();

        $permissionGroups = [];
        
        foreach ($allPermissions as $permission) {
            $parts = explode('.', $permission->name);
            $group = $parts[0];
            
            if (!isset($permissionGroups[$group])) {
                $permissionGroups[$group] = [];
            }
            
            $permissionGroups[$group][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'roles' => $permission->roles->pluck('name')->toArray(),
                'roles_count' => $permission->roles->count(),
            ];
        }

        // Convert to array format expected by frontend
        $permissions = [];
        foreach ($permissionGroups as $group => $perms) {
            $permissions[] = [
                'group' => $group,
                'permissions' => $perms,
            ];
        }

        // Get all roles for reference
        $roles = Role::where('guard_name', 'web')->get(['id', 'name']);

        return Inertia::render('Backend/User/Business/permissions', [
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }
}
