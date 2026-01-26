<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * A2ZWeb\NovaRoleManager\Models\AuditablePermission
 *
 * Extends Spatie Permission model with auditing capabilities.
 *
 * @property int $id
 * @property string $name
 * @property string|null $group
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditablePermission withoutRole($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class AuditablePermission extends SpatiePermission implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'name',
        'group',
        'guard_name',
    ];
}
