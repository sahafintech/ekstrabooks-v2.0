<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\InviteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class UserManagementController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index(Request $request)
    {
        Gate::authorize('business.user.view');
        $currentUser = auth()->user();
        $perPage = $request->get('per_page', 50);
        
        // Get users who belong to businesses owned by the current user
        $query = User::with('roles', 'permissions')
            ->where('user_type', '!=', 'admin')
            ->whereExists(function ($subQuery) use ($currentUser) {
                $subQuery->select(\DB::raw(1))
                    ->from('business_users')
                    ->whereColumn('business_users.user_id', 'users.id')
                    ->where('business_users.owner_id', $currentUser->id);
            });

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting - handle both array format and individual params
        $sorting = $request->get('sorting', []);
        if (is_array($sorting) && !empty($sorting)) {
            $sortBy = $sorting['column'] ?? 'created_at';
            $sortDirection = $sorting['direction'] ?? 'desc';
        } else {
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
        }
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $paginatedUsers = $query->paginate($perPage);
        
        $users = $paginatedUsers->map(function ($user) use ($currentUser) {
            // Get businesses this user has access to (owned by current user)
            $userBusinesses = \DB::table('business_users')
                ->join('business', 'business_users.business_id', '=', 'business.id')
                ->where('business_users.user_id', $user->id)
                ->where('business_users.owner_id', $currentUser->id)
                ->whereNull('business.deleted_at')
                ->select('business.id', 'business.name')
                ->get();
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'owner' => $user->hasRole('Owner'),
                'roles' => $user->getRoleNames()->toArray(),
                'permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
                'businesses' => $userBusinesses->map(fn($b) => ['id' => $b->id, 'name' => $b->name])->toArray(),
                'created_at' => $user->created_at,
            ];
        });

        // Get roles with their permissions (exclude 'contact' role)
        $roles = Role::where('name', '!=', 'contact')
            ->where('guard_name', 'web')
            ->with('permissions')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
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

        // Get all businesses owned by the current user
        $ownerBusinesses = \DB::table('business')
            ->join('business_users', 'business.id', '=', 'business_users.business_id')
            ->where('business_users.owner_id', $currentUser->id)
            ->whereNull('business.deleted_at')
            ->select('business.id', 'business.name')
            ->distinct()
            ->get()
            ->map(fn($b) => ['id' => $b->id, 'name' => $b->name]);

        // Get pending invitations count
        $pendingInvitationsCount = Invite::where('sender_id', $currentUser->id)
            ->where('status', 1)
            ->count();

        return Inertia::render('Backend/User/Business/user-management', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'ownerBusinesses' => $ownerBusinesses,
            'pendingInvitationsCount' => $pendingInvitationsCount,
            'meta' => [
                'current_page' => $paginatedUsers->currentPage(),
                'last_page' => $paginatedUsers->lastPage(),
                'per_page' => $paginatedUsers->perPage(),
                'total' => $paginatedUsers->total(),
            ],
            'filters' => [
                'search' => $request->search,
                'sorting' => [
                    'column' => $sortBy,
                    'direction' => $sortDirection,
                ],
            ],
        ]);
    }

    /**
     * Send an invitation to a user.
     */
    public function sendInvitation(Request $request)
    {
        Gate::authorize('business.user.create');
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['integer', 'exists:business,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $currentUser = auth()->user();

        // Check if user already exists and is assigned to these businesses
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            // Check if user is already assigned to any of the selected businesses
            $existingAssignment = \DB::table('business_users')
                ->where('user_id', $existingUser->id)
                ->whereIn('business_id', $validated['business_ids'])
                ->where('owner_id', $currentUser->id)
                ->exists();
            
            if ($existingAssignment) {
                return redirect()->back()->with('error', 'User is already assigned to one or more of the selected businesses.');
            }
        }

        // Check if email template exists and is enabled
        $template = EmailTemplate::where('slug', 'INVITE_USER')->where('email_status', 1)->first();
        if (!$template) {
            return redirect()->back()->with('error', 'Email template is disabled. Contact your administrator.');
        }

        // Get the role
        $role = Role::where('name', $validated['role'])->first();

        // Create or update invite
        $invite = Invite::updateOrCreate(
            [
                'email' => $validated['email'],
                'sender_id' => $currentUser->id,
            ],
            [
                'business_id' => $validated['business_ids'],
                'role_id' => $role->id,
                'user_id' => $existingUser?->id,
                'message' => $validated['message'] ?? null,
                'status' => 1,
            ]
        );

        try {
            // Send Email Notification
            Notification::send($invite, new InviteUser($invite));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send invitation email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invitation sent successfully to ' . $validated['email']);
    }

    /**
     * Update user roles and permissions.
     */
    public function updateRolesAndPermissions(Request $request, User $user)
    {
        Gate::authorize('business.user.update');
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // Sync roles (the role selected by user)
        $user->syncRoles($validated['roles'] ?? []);

        // Sync direct permissions (the permissions selected/modified by user)
        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()->back()->with('success', 'Roles and permissions updated successfully.');
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        Gate::authorize('business.user.delete');
        // Prevent deleting the last Owner
        if ($user->hasRole('Owner')) {
            $ownerCount = User::role('Owner')->count();
            if ($ownerCount <= 1) {
                return redirect()->back()->with('error', 'Cannot delete the last Owner user.');
            }
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    /**
     * Bulk delete users.
     */
    public function bulkDestroy(Request $request)
    {
        Gate::authorize('business.user.delete');
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($validated['ids'] as $id) {
            $user = User::find($id);
            if ($user) {
                // Prevent deleting the last Owner
                if ($user->hasRole('Owner')) {
                    $ownerCount = User::role('Owner')->count();
                    if ($ownerCount <= 1) {
                        $skippedCount++;
                        continue;
                    }
                }
                $user->delete();
                $deletedCount++;
            }
        }

        $message = "Deleted {$deletedCount} user(s).";
        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} user(s) (last Owner protection).";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Assign businesses to a user.
     */
    public function assignBusinesses(Request $request, User $user)
    {
        Gate::authorize('business.user.assign-businesses');
        $validated = $request->validate([
            'business_ids' => ['array'],
            'business_ids.*' => ['integer', 'exists:business,id'],
        ]);

        $currentUser = auth()->user();
        $businessIds = $validated['business_ids'] ?? [];

        // Get owner's businesses to validate
        $ownerBusinessIds = \DB::table('business_users')
            ->where('owner_id', $currentUser->id)
            ->pluck('business_id')
            ->unique()
            ->toArray();

        // Only process businesses that belong to the current owner
        $validBusinessIds = array_intersect($businessIds, $ownerBusinessIds);

        // Remove the user from all businesses owned by current user
        \DB::table('business_users')
            ->where('user_id', $user->id)
            ->where('owner_id', $currentUser->id)
            ->delete();

        // Add user to selected businesses
        foreach ($validBusinessIds as $businessId) {
            \DB::table('business_users')->insert([
                'user_id' => $user->id,
                'business_id' => $businessId,
                'owner_id' => $currentUser->id,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Businesses assigned successfully.');
    }

    /**
     * Display invitations list.
     */
    public function invitations(Request $request)
    {
        Gate::authorize('business.invitations.view');
        $currentUser = auth()->user();
        $perPage = $request->get('per_page', 50);

        $query = Invite::where('sender_id', $currentUser->id);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('email', 'like', "%{$search}%");
        }

        // Sorting
        $sorting = $request->get('sorting', []);
        if (is_array($sorting) && !empty($sorting)) {
            $sortBy = $sorting['column'] ?? 'created_at';
            $sortDirection = $sorting['direction'] ?? 'desc';
        } else {
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
        }
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $paginatedInvitations = $query->paginate($perPage);

        $invitations = $paginatedInvitations->map(function ($invite) {
            // Get businesses for this invite
            $businessIds = is_array($invite->business_id) ? $invite->business_id : [$invite->business_id];
            $businesses = \DB::table('business')
                ->whereIn('id', $businessIds)
                ->whereNull('deleted_at')
                ->select('id', 'name')
                ->get()
                ->map(fn($b) => ['id' => $b->id, 'name' => $b->name])
                ->toArray();

            // Get role name
            $role = Role::find($invite->role_id);

            return [
                'id' => $invite->id,
                'email' => $invite->email,
                'status' => $invite->status,
                'status_label' => $invite->status == 1 ? 'Pending' : 'Accepted',
                'businesses' => $businesses,
                'role' => $role ? $role->name : 'Unknown',
                'message' => $invite->message,
                'created_at' => $invite->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Backend/User/Business/invitations', [
            'invitations' => $invitations,
            'meta' => [
                'current_page' => $paginatedInvitations->currentPage(),
                'last_page' => $paginatedInvitations->lastPage(),
                'per_page' => $paginatedInvitations->perPage(),
                'total' => $paginatedInvitations->total(),
            ],
            'filters' => [
                'search' => $request->search,
                'sorting' => [
                    'column' => $sortBy,
                    'direction' => $sortDirection,
                ],
            ],
        ]);
    }

    /**
     * Resend an invitation.
     */
    public function resendInvitation(Invite $invitation)
    {
        Gate::authorize('business.invitations.resend');
        $currentUser = auth()->user();

        // Verify ownership
        if ($invitation->sender_id !== $currentUser->id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Check if invitation is still pending
        if ($invitation->status !== 1) {
            return redirect()->back()->with('error', 'This invitation has already been accepted.');
        }

        // Check if email template exists
        $template = EmailTemplate::where('slug', 'INVITE_USER')->where('email_status', 1)->first();
        if (!$template) {
            return redirect()->back()->with('error', 'Email template is disabled. Contact your administrator.');
        }

        try {
            Notification::send($invitation, new InviteUser($invitation));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to resend invitation: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invitation resent successfully.');
    }

    /**
     * Cancel an invitation.
     */
    public function cancelInvitation(Invite $invitation)
    {
        Gate::authorize('business.invitations.cancel');
        $currentUser = auth()->user();

        // Verify ownership
        if ($invitation->sender_id !== $currentUser->id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $invitation->delete();

        return redirect()->back()->with('success', 'Invitation cancelled successfully.');
    }
}
