<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionsController
{
    public function getRolePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $roleModel = config('role-manager.role_model');
        $role = $roleModel::find($request->role_id);

        if (! $role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $permissions = $role->permissions()
            ->pluck('id')
            ->toArray();

        return response()->json([
            'permissions' => $permissions,
        ]);
    }

    public function getAllRolePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'guard_name' => 'required|string',
        ]);

        $roleModel = config('role-manager.role_model');
        $roles = $roleModel::where('guard_name', $request->guard_name)->get();

        $result = [];

        // Use a single query to get all role-permission relationships
        $roleHasPermissionsTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $rolesTable = config('permission.table_names.roles', 'roles');

        $rolePermissions = DB::table($roleHasPermissionsTable)
            ->join($rolesTable, "{$roleHasPermissionsTable}.role_id", '=', "{$rolesTable}.id")
            ->where("{$rolesTable}.guard_name", $request->guard_name)
            ->select("{$roleHasPermissionsTable}.role_id", "{$roleHasPermissionsTable}.permission_id")
            ->get();

        // Group permissions by role_id
        foreach ($rolePermissions as $rp) {
            if (! isset($result[$rp->role_id])) {
                $result[$rp->role_id] = [];
            }
            $result[$rp->role_id][] = $rp->permission_id;
        }

        // Ensure all roles are in the result (even if they have no permissions)
        foreach ($roles as $role) {
            if (! isset($result[$role->id])) {
                $result[$role->id] = [];
            }
        }

        return response()->json([
            'role_permissions' => $result,
        ]);
    }

    public function togglePermission(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'permission_id' => 'required|integer',
            'assign' => 'required|boolean',
        ]);

        $user = $request->user();

        // Check if edit permission is required
        $editPermission = config('role-manager.edit_permission');
        if ($editPermission !== null && (! $user || ! $user->can($editPermission))) {
            return response()->json(['error' => 'You do not have permission to edit roles'], 403);
        }

        $roleModel = config('role-manager.role_model');
        $permissionModel = config('role-manager.permission_model');

        $role = $roleModel::find($request->role_id);

        if (! $role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $permission = $permissionModel::where('id', $request->permission_id)
            ->where('guard_name', $role->guard_name)
            ->first();

        if (! $permission) {
            return response()->json(['error' => 'Permission not found'], 404);
        }

        return $this->togglePermissionsForRole($role, collect([$permission]), $request->assign, 'Failed to toggle permission');
    }

    public function bulkTogglePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'required|integer',
            'assign' => 'required|boolean',
        ]);

        $user = $request->user();

        // Check if edit permission is required
        $editPermission = config('role-manager.edit_permission');
        if ($editPermission !== null && (! $user || ! $user->can($editPermission))) {
            return response()->json(['error' => 'You do not have permission to edit roles'], 403);
        }

        $roleModel = config('role-manager.role_model');
        $permissionModel = config('role-manager.permission_model');

        $role = $roleModel::find($request->role_id);

        if (! $role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $permissions = $permissionModel::whereIn('id', $request->permission_ids)
            ->where('guard_name', $role->guard_name)
            ->get();

        if ($permissions->count() !== count($request->permission_ids)) {
            return response()->json(['error' => 'Some permissions not found'], 404);
        }

        $response = $this->togglePermissionsForRole($role, $permissions, $request->assign, 'Failed to bulk toggle permissions');

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getContent(), true);
            $data['count'] = $permissions->count();

            return response()->json($data);
        }

        return $response;
    }

    public function saveRolePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'permission_ids_to_add' => 'array',
            'permission_ids_to_add.*' => 'required|integer',
            'permission_ids_to_remove' => 'array',
            'permission_ids_to_remove.*' => 'required|integer',
        ]);

        $user = $request->user();

        // Check if edit permission is required
        $editPermission = config('role-manager.edit_permission');
        if ($editPermission !== null && (! $user || ! $user->can($editPermission))) {
            return response()->json(['error' => 'You do not have permission to edit roles'], 403);
        }

        $roleModel = config('role-manager.role_model');
        $permissionModel = config('role-manager.permission_model');

        $permissionIdsToAdd = $request->permission_ids_to_add;
        $permissionIdsToRemove = $request->permission_ids_to_remove;

        $role = $roleModel::find($request->role_id);

        if (! $role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        // Prevent editing protected roles
        $protectedRoles = array_map('strtoupper', config('role-manager.protected_roles', []));
        if (in_array(strtoupper($role->name), $protectedRoles, true)) {
            return response()->json(['error' => 'This role is protected and cannot be edited'], 403);
        }

        try {
            // Add permissions
            if (! empty($permissionIdsToAdd)) {
                $permissionsToAdd = $permissionModel::whereIn('id', $permissionIdsToAdd)
                    ->where('guard_name', $role->guard_name)
                    ->get();

                if ($permissionsToAdd->count() !== count($permissionIdsToAdd)) {
                    return response()->json(['error' => 'Some permissions to add not found'], 404);
                }

                $role->givePermissionTo($permissionsToAdd);
            }

            // Remove permissions
            if (! empty($permissionIdsToRemove)) {
                $permissionsToRemove = $permissionModel::whereIn('id', $permissionIdsToRemove)
                    ->where('guard_name', $role->guard_name)
                    ->get();

                if ($permissionsToRemove->count() !== count($permissionIdsToRemove)) {
                    return response()->json(['error' => 'Some permissions to remove not found'], 404);
                }

                $role->revokePermissionTo($permissionsToRemove);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'added_count' => count($permissionIdsToAdd),
                'removed_count' => count($permissionIdsToRemove),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to save role permissions: '.$e->getMessage(),
            ], 500);
        }
    }

    private function togglePermissionsForRole($role, \Illuminate\Support\Collection $permissions, bool $assign, string $errorMessage): JsonResponse
    {
        try {
            if ($assign) {
                $role->givePermissionTo($permissions);
            } else {
                $role->revokePermissionTo($permissions);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'assigned' => $assign,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $errorMessage.': '.$e->getMessage(),
            ], 500);
        }
    }
}
