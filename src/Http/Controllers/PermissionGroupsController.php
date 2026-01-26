<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionGroupsController
{
    public function __construct() {}

    public function __invoke(Request $request): JsonResponse
    {
        $roleModel = config('role-manager.role_model');
        $permissionModel = config('role-manager.permission_model');

        // Get all unique guards from non-deleted roles only
        $guards = $roleModel::query()
            ->when(method_exists($roleModel, 'whereNull'), fn ($q) => $q->whereNull('deleted_at'))
            ->distinct()
            ->pluck('guard_name')
            ->filter()
            ->all();

        // Get the desired order from config
        $guardOrder = config('role-manager.guard_order', []);

        // Sort guards according to the defined order
        $sortedGuards = [];
        foreach ($guardOrder as $orderedGuard) {
            if (in_array($orderedGuard, $guards, true)) {
                $sortedGuards[] = $orderedGuard;
            }
        }

        // Add any remaining guards that weren't in the order list (shouldn't happen, but just in case)
        foreach ($guards as $guardName) {
            if (! in_array($guardName, $sortedGuards, true)) {
                $sortedGuards[] = $guardName;
            }
        }

        $result = [];

        $user = $request->user();

        // Check configurable permissions
        $viewAuditLogsPermission = config('role-manager.view_audit_logs_permission');
        $editPermission = config('role-manager.edit_permission');

        // If permission is null, allow access. Otherwise check if user has permission.
        $canViewAuditLogs = $viewAuditLogsPermission === null ? true : ($user && $user->can($viewAuditLogsPermission));
        $canEditRoles = $editPermission === null ? true : ($user && $user->can($editPermission));

        foreach ($sortedGuards as $guardName) {
            // Get all permissions for this guard
            $permissions = $this->getPermissionsForGuard($guardName, $permissionModel);

            // Skip guards with no permissions
            if ($permissions->isEmpty()) {
                continue;
            }

            // Get all non-deleted roles for this guard with id, name, and guard_name
            $roles = $roleModel::query()
                ->where('guard_name', $guardName)
                ->when(method_exists($roleModel, 'whereNull'), fn ($q) => $q->whereNull('deleted_at'))
                ->withCount('permissions')
                ->orderBy('permissions_count')
                ->orderBy('name')
                ->get(['id', 'name', 'guard_name'])
                ->map(function ($role) use ($canViewAuditLogs, $canEditRoles) {
                    $auditLogUrl = null;
                    $canShowAuditLogs = false;

                    // Check if audit logs are enabled in config
                    if (config('role-manager.enable_audit_logs', false)) {
                        $auditResourceClass = config('role-manager.audit_resource');

                        if ($auditResourceClass && class_exists($auditResourceClass)) {
                            $auditFilterClass = config('role-manager.audit_filter');

                            if ($auditFilterClass && class_exists($auditFilterClass)) {
                                // Generate Nova Audit resource URL with role filter applied
                                $filterData = [
                                    [$auditFilterClass => $role->id],
                                ];
                                $filtersParam = base64_encode(json_encode($filterData));
                                $auditResourceKey = $auditResourceClass::uriKey();
                                $auditLogUrl = "/nova/resources/{$auditResourceKey}?role-audit-log_filter={$filtersParam}";
                            } else {
                                // No filter, just link to audit resource
                                $auditResourceKey = $auditResourceClass::uriKey();
                                $auditLogUrl = "/nova/resources/{$auditResourceKey}";
                            }

                            $canShowAuditLogs = $canViewAuditLogs && $auditLogUrl !== null;
                        }
                    }

                    $protectedRoles = array_map('strtoupper', config('role-manager.protected_roles', []));
                    $isProtected = in_array(strtoupper($role->name), $protectedRoles, true);

                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'is_editable' => ! $isProtected && $canEditRoles,
                        'is_protected' => $isProtected,
                        'audit_log_url' => $auditLogUrl,
                        'can_view_audit_logs' => $canShowAuditLogs,
                    ];
                })
                ->all();

            // Sort roles: non-protected first, then protected last
            $protectedRoles = [];
            $nonProtectedRoles = [];

            foreach ($roles as $role) {
                if ($role['is_protected']) {
                    $protectedRoles[] = $role;
                } else {
                    $nonProtectedRoles[] = $role;
                }
            }

            $roles = array_merge($nonProtectedRoles, $protectedRoles);

            // Build nested categories structure
            $categories = [];

            foreach ($permissions as $permission) {
                $group = $permission->group ?? ''; // Handle null as empty string

                // Split group by "/" to create nested categories
                $categoryParts = explode('/', $group);
                $categoryParts = array_map('trim', $categoryParts);
                $categoryParts = array_filter($categoryParts, fn ($part) => $part !== '');
                $categoryParts = array_values($categoryParts);

                // Handle ungrouped permissions (no slash or empty group)
                if (count($categoryParts) <= 1) {
                    $ungroupedCategory = config('role-manager.ungrouped_permissions_category');

                    // If null, skip ungrouped permissions
                    if ($ungroupedCategory === null) {
                        continue;
                    }

                    // Put ungrouped permissions under the configured category
                    // If group has a name (e.g., "Admin"), nest it: Other / Admin
                    // If group is empty, just use: Other
                    if (count($categoryParts) === 1) {
                        $categoryParts = [$ungroupedCategory, $categoryParts[0]];
                    } else {
                        $categoryParts = [$ungroupedCategory];
                    }
                }

                // Build nested structure
                $current = &$categories;
                foreach ($categoryParts as $index => $part) {
                    $isLast = $index === count($categoryParts) - 1;
                    $humanizedPart = $this->humanizeName($part);

                    // If this is the last part, we need to ensure it has 'permissions' array
                    if ($isLast) {
                        if (! isset($current[$humanizedPart])) {
                            $current[$humanizedPart] = ['permissions' => []];
                        } elseif (! isset($current[$humanizedPart]['permissions'])) {
                            // If it exists but doesn't have permissions key, add it
                            // This handles case where category was created as intermediate level
                            $current[$humanizedPart]['permissions'] = [];
                        }

                        $current[$humanizedPart]['permissions'][] = [
                            'name' => $permission->name,
                            'label' => $this->humanizeName((string) $permission->name),
                            'type' => $this->permissionType((string) $permission->name),
                            'id' => $permission->id,
                        ];
                    } else {
                        // Intermediate category level
                        if (! isset($current[$humanizedPart])) {
                            $current[$humanizedPart] = [];
                        }
                        $current = &$current[$humanizedPart];
                    }
                }
                unset($current);
            }

            $result[$guardName] = [
                'name' => $guardName,
                'roles' => $roles,
                'categories' => $categories,
            ];
        }

        return response()->json($result);
    }

    private function getPermissionsForGuard(string $guardName, string $permissionModel): \Illuminate\Support\Collection
    {
        $repository = config('role-manager.permission_repository');

        if ($repository && class_exists($repository)) {
            return app($repository)->allForGuard($guardName);
        }

        // Fallback to direct model query
        return $permissionModel::query()
            ->where('guard_name', $guardName)
            ->orderByRaw('COALESCE("group", \'\') ASC')
            ->orderBy('id')
            ->get();
    }

    private function permissionType(string $name): string
    {
        $keywords = config('role-manager.permission_keywords', []);

        $dangerPrefixes = $keywords['danger']['prefixes'] ?? ['delete', 'destroy'];
        foreach ($dangerPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return 'danger';
            }
        }

        $dangerContains = $keywords['danger']['contains'] ?? ['purge', 'withdraw', 'block', 'ban', 'destructive'];
        foreach ($dangerContains as $needle) {
            if (str_contains($name, $needle)) {
                return 'danger';
            }
        }

        $warningContains = $keywords['warning']['contains'] ?? ['approve', 'disable', 'manage', 'import', 'lock', 'deposit'];
        foreach ($warningContains as $needle) {
            if (str_contains($name, $needle)) {
                return 'warning';
            }
        }

        return 'safe';
    }

    private function humanizeName(string $name): string
    {
        $spaced = preg_replace('/(?<=\\p{Ll})(?=\\p{Lu})/u', ' ', $name) ?? $name;
        $spaced = preg_replace('/(?<=\\p{L})(?=\\d)/u', ' ', $spaced) ?? $spaced;
        $spaced = str_replace('_', ' ', $spaced);

        $normalized = trim(preg_replace('/\\s+/', ' ', $spaced) ?? $spaced);

        // Split into words and remove consecutive duplicates (case-insensitive)
        $words = explode(' ', $normalized);
        $result = [];
        $previousWord = null;

        foreach ($words as $word) {
            $wordLower = mb_strtolower($word, 'UTF-8');
            if ($wordLower !== $previousWord) {
                $result[] = $word;
                $previousWord = $wordLower;
            }
        }

        // Capitalize each word
        $result = array_map([$this, 'ucfirstUtf8'], $result);

        return implode(' ', $result);

        return $name;
    }

    private function ucfirstUtf8(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = mb_substr($value, 0, 1, 'UTF-8');
        $rest = mb_substr($value, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8').$rest;
    }
}
