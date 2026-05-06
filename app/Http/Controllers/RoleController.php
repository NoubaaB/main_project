<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List all roles with user counts.
     */
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')->get();
        return response()->json([
            'roles' => $roles,
            'permission_groups' => Role::permissionGroups(),
            'all_permissions' => Role::allPermissionKeys(),
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'is_super_admin' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        // Only super admins can create other super admin roles
        if (!empty($data['is_super_admin']) && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Cannot create super admin role.'], 403);
        }

        $role = Role::create([
            'name' => $data['name'],
            'is_super_admin' => $data['is_super_admin'] ?? false,
            'permissions' => $data['permissions'] ?? [],
        ]);

        $role->loadCount('users');
        $this->broadcastChange('Role', 'created', $role->id, $role->toArray());

        return response()->json(['role' => $role], 201);
    }

    /**
     * Update an existing role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name,' . $role->id,
            'is_super_admin' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        // Prevent removing super_admin from own role
        if ($role->is_super_admin && !($data['is_super_admin'] ?? true)) {
            // Check if this is the last super admin role
            $superAdminCount = Role::where('is_super_admin', true)->count();
            if ($superAdminCount <= 1) {
                return response()->json(['message' => 'Cannot remove super admin from the last super admin role.'], 422);
            }
        }

        $role->update([
            'name' => $data['name'],
            'is_super_admin' => $data['is_super_admin'] ?? false,
            'permissions' => $data['permissions'] ?? [],
        ]);

        $role->loadCount('users');
        $this->broadcastChange('Role', 'updated', $role->id, $role->toArray());

        return response()->json(['role' => $role]);
    }

    /**
     * Delete a role (if no users assigned).
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete a role that has users assigned.'], 422);
        }

        if ($role->is_super_admin) {
            $superAdminCount = Role::where('is_super_admin', true)->count();
            if ($superAdminCount <= 1) {
                return response()->json(['message' => 'Cannot delete the last super admin role.'], 422);
            }
        }

        $id = $role->id;
        $role->delete();
        $this->broadcastChange('Role', 'deleted', $id);

        return response()->json(['message' => 'Role deleted successfully.']);
    }
}
