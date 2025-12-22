<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

final class PermissionController extends Controller
{
    public function index()
    {
        Gate::authorize('roles.view');

        $permissions = Permission::all()
            ->groupBy(static function (Permission $permission): string {
                $parts = explode('.', $permission->name);

                return $parts[0] ?? 'other';
            })
            ->map(static function ($group, $groupName): array {
                return [
                    'group' => $groupName,
                    'permissions' => $group->map(static function (Permission $permission): array {
                        $roles = Role::whereHas('permissions', static function ($query) use ($permission): void {
                            $query->where('permissions.id', $permission->id);
                        })->pluck('name')->toArray();

                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'roles' => $roles,
                            'roles_count' => count($roles),
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $roles = Role::all()
            ->map(static function (Role $role): array {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            });

        return Inertia::render('backend/user/business/permissions', [
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }
}
