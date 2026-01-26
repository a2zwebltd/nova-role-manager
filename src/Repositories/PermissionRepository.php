<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Repositories;

use Illuminate\Support\Collection;

/**
 * Default permission repository for fetching permissions.
 *
 * This is a simple implementation that can be extended or replaced
 * by configuring a custom repository in the config file.
 */
class PermissionRepository
{
    /**
     * Get all permissions for a specific guard.
     *
     * @param  string  $guardName  The guard name to filter permissions
     * @return Collection<int, \Spatie\Permission\Models\Permission>
     */
    public function allForGuard(string $guardName = 'web'): Collection
    {
        $permissionModel = config('role-manager.permission_model');

        /** @var Collection<int, \Spatie\Permission\Models\Permission> $permissions */
        $permissions = $permissionModel::query()
            ->where('guard_name', $guardName)
            ->orderBy('group')
            ->orderBy('id')
            ->get();

        return $permissions;
    }
}
