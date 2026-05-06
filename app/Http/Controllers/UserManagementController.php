<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List all users with their roles.
     */
    public function index(): JsonResponse
    {
        $users = User::with('role')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json(['users' => $users]);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|string|email|unique:users,email|max:100',
            'password' => 'required|string|min:6|max:250',
            'role_id' => 'required|exists:roles,id',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;

        $user = User::create($data);
        $user->load('role');

        $this->broadcastChange('User', 'created', $user->id, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);

        return response()->json(['user' => $user], 201);
    }

    /**
     * Update user details (role, name, email, optionally password).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:100|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6|max:250',
        ]);

        // Prevent changing own role if it would remove super admin access
        if ($request->user()->id === $user->id) {
            $newRole = Role::find($data['role_id']);
            if ($user->isSuperAdmin() && !$newRole->is_super_admin) {
                // Check if there are other super admin users
                $otherSuperAdmins = User::where('id', '!=', $user->id)
                    ->whereHas('role', fn($q) => $q->where('is_super_admin', true))
                    ->count();
                if ($otherSuperAdmins === 0) {
                    return response()->json([
                        'message' => 'Cannot change role. You are the only super admin.',
                    ], 422);
                }
            }
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        $user->load('role');

        $this->broadcastChange('User', 'updated', $user->id, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);

        return response()->json(['user' => $user]);
    }

    /**
     * Toggle user active/disabled status. Cannot disable self.
     */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        // Cannot disable your own account
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Cannot disable your own account.'], 422);
        }

        $user->update(['is_active' => !$user->is_active]);
        $user->load('role');

        // If user was disabled, revoke all their tokens
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        $this->broadcastChange('User', 'updated', $user->id, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);

        return response()->json([
            'user' => $user,
            'message' => $user->is_active ? 'User enabled successfully.' : 'User disabled successfully.',
        ]);
    }
}

