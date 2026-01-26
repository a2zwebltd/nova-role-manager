<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Models;

use A2ZWeb\AuditableRelations\Traits\AuditsRelationships;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A2ZWeb\NovaRoleManager\Models\AuditableRole
 *
 * Extends Spatie Role model with auditing capabilities for both
 * model changes and relationship changes (attach, detach, sync).
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditableRole withoutPermission($permissions)
 *
 * @mixin \Eloquent
 */
class AuditableRole extends SpatieRole implements Auditable
{
    use AuditableTrait;
    use AuditsRelationships;

    /**
     * Override the permissions relationship to enable auditing for
     * attach, detach, and sync operations.
     */
    public function permissions(): BelongsToMany
    {
        $permissionModel = config('role-manager.permission_model', \Spatie\Permission\Models\Permission::class);

        /** @var BelongsToMany */
        return $this->auditableRelation(
            $this->belongsToMany(
                $permissionModel,
                config('permission.table_names.role_has_permissions'),
                'role_id',
                'permission_id'
            )
        );
    }
}
