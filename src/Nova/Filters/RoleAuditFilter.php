<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Nova\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class RoleAuditFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * The displayable name of the filter.
     */
    public function name(): string
    {
        return 'Role';
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function apply(Request $request, $query, $value)
    {
        $roleModel = config('role-manager.role_model');

        return $query->where('auditable_type', $roleModel)
            ->where('auditable_id', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @return array<string, string>
     */
    public function options(Request $request): array
    {
        $roleModel = config('role-manager.role_model');

        $roles = $roleModel::query()
            ->when(method_exists($roleModel, 'whereNull'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('guard_name')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        $options = [];
        foreach ($roles as $role) {
            $guardName = str_replace('-', ' ', $role->guard_name);
            $guardName = strtoupper($guardName);
            if ($guardName === 'SUPPLIER') {
                $guardName = 'SUPPLIER DASHBOARD';
            }

            $label = "{$role->name} ({$guardName})";
            $options[$label] = $role->id;
        }

        return $options;
    }
}
