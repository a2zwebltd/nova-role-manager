<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Line;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use OwenIt\Auditing\Models\Audit as AuditModel;

class RoleAuditLog extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<AuditModel>
     */
    public static string $model = AuditModel::class;

    /**
     * Get the URI key for the resource.
     */
    public static function uriKey(): string
    {
        return 'role-audit-log';
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Role Audit Logs';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Role Audit Log';
    }

    /**
     * Determine if this resource is available for navigation.
     */
    public static function availableForNavigation(Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the user can view any resources.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        $permission = config('role-manager.view_audit_logs_permission');

        if ($permission && $request->user()) {
            return $request->user()->can($permission);
        }

        return true;
    }

    /**
     * Determine if this resource is available for creation.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the given resource is authorized to be updated.
     */
    public function authorizedToUpdate(Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the given resource is authorized to be deleted.
     */
    public function authorizedToDelete(Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the given resource is authorized to be replicated.
     */
    public function authorizedToReplicate(Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the given resource is authorized to be viewed.
     */
    public function authorizedToView(Request $request): bool
    {
        $permission = config('role-manager.view_audit_logs_permission');

        if ($permission && $request->user()) {
            return $request->user()->can($permission);
        }

        return true;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        $roleModel = config('role-manager.role_model');

        return [
            ID::make()->sortable(),

            Stack::make('Role', [
                Line::make('Name', function () use ($roleModel) {
                    if ($this->auditable_type !== $roleModel) {
                        return null;
                    }

                    $role = $roleModel::find($this->auditable_id);
                    if (! $role) {
                        return null;
                    }

                    return $role->name;
                }),
                Line::make('Guard', function () use ($roleModel) {
                    if ($this->auditable_type !== $roleModel) {
                        return null;
                    }

                    $role = $roleModel::find($this->auditable_id);
                    if (! $role) {
                        return null;
                    }

                    $guardName = $role->guard_name;
                    $formattedGuard = str_replace('-', ' ', $guardName);
                    $formattedGuard = strtoupper($formattedGuard);
                    if ($formattedGuard === 'SUPPLIER') {
                        $formattedGuard = 'SUPPLIER DASHBOARD';
                    }

                    return $formattedGuard;
                })->asSmall(),
            ])
                ->onlyOnIndex(),

            Text::make('Editor', function () {
                if (! $this->user_type || ! $this->user_id) {
                    return 'System';
                }

                $userClass = $this->user_type;
                if (! class_exists($userClass)) {
                    return 'System';
                }

                $user = $userClass::find($this->user_id);
                if (! $user) {
                    return 'System';
                }

                // Try to get user name
                $userName = $user->name ?? $user->email ?? 'System';

                // Check if a user resource is configured
                $userResourceClass = config('role-manager.user_resource');

                if ($userResourceClass && class_exists($userResourceClass)) {
                    try {
                        $resourceKey = $userResourceClass::uriKey();
                        $userUrl = Nova::url("/resources/{$resourceKey}/{$user->id}");

                        return "<a href=\"{$userUrl}\" class=\"text-primary-500 hover:text-primary-400\">{$userName}</a>";
                    } catch (\Exception $e) {
                        // Failed to generate link, just return the name
                    }
                }

                return $userName;
            })
                ->asHtml()
                ->sortable()
                ->filterable(),

            Text::make('Permission Changes', function () {
                $oldValues = is_string($this->old_values)
                    ? json_decode($this->old_values ?? '{}', true)
                    : ($this->old_values ?? []);
                $newValues = is_string($this->new_values)
                    ? json_decode($this->new_values ?? '{}', true)
                    : ($this->new_values ?? []);

                $oldPerms = $oldValues['permissions'] ?? [];
                $newPerms = $newValues['permissions'] ?? [];

                if (empty($oldPerms) && empty($newPerms)) {
                    return '<span class="text-gray-400 italic">No changes</span>';
                }

                $oldMap = [];
                foreach ($oldPerms as $perm) {
                    $permId = is_array($perm) ? ($perm['id'] ?? null) : $perm;
                    if ($permId) {
                        $oldMap[$permId] = is_array($perm) ? $perm : ['id' => $perm];
                    }
                }

                $newMap = [];
                foreach ($newPerms as $perm) {
                    $permId = is_array($perm) ? ($perm['id'] ?? null) : $perm;
                    if ($permId) {
                        $newMap[$permId] = is_array($perm) ? $perm : ['id' => $perm];
                    }
                }

                $changes = [];

                if ($this->event === 'attached') {
                    foreach ($newPerms as $perm) {
                        $permId = is_array($perm) ? ($perm['id'] ?? null) : $perm;
                        if ($permId && ! isset($oldMap[$permId])) {
                            $changes[] = ['type' => 'added', 'permission' => is_array($perm) ? $perm : ['id' => $perm]];
                        }
                    }
                } elseif ($this->event === 'detached') {
                    foreach ($oldPerms as $perm) {
                        $permId = is_array($perm) ? ($perm['id'] ?? null) : $perm;
                        if ($permId && ! isset($newMap[$permId])) {
                            $changes[] = ['type' => 'removed', 'permission' => is_array($perm) ? $perm : ['id' => $perm]];
                        }
                    }
                } elseif ($this->event === 'synced') {
                    foreach ($newPerms as $perm) {
                        $permId = is_array($perm) ? ($perm['id'] ?? null) : $perm;
                        if ($permId && ! isset($oldMap[$permId])) {
                            $changes[] = ['type' => 'added', 'permission' => is_array($perm) ? $perm : ['id' => $perm]];
                        }
                    }
                    foreach ($oldPerms as $perm) {
                        $permId = is_array($perm) ? ($perm['id'] ?? null) : $perm;
                        if ($permId && ! isset($newMap[$permId])) {
                            $changes[] = ['type' => 'removed', 'permission' => is_array($perm) ? $perm : ['id' => $perm]];
                        }
                    }
                }

                if (empty($changes)) {
                    return '<span class="text-gray-400 italic">No changes</span>';
                }

                // Group by permission group
                $grouped = [];
                foreach ($changes as $change) {
                    $group = $change['permission']['group'] ?? 'Other';
                    if (! isset($grouped[$group])) {
                        $grouped[$group] = [];
                    }
                    $grouped[$group][] = $change;
                }

                $html = '<div class="space-y-2">';
                foreach ($grouped as $group => $groupChanges) {
                    $humanizedGroup = $this->humanizeName($group);
                    // Use green border for attached/synced with additions, red for detached/removals
                    $hasAdditions = in_array('added', array_column($groupChanges, 'type'), true);
                    $borderColor = $hasAdditions ? 'border-green-500' : 'border-red-500';
                    $html .= "<div class=\"border-l-4 pl-2 {$borderColor}\">";
                    $html .= "<div class=\"text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1\">{$humanizedGroup}</div>";
                    $html .= '<div class="space-y-1">';
                    foreach ($groupChanges as $change) {
                        $icon = $change['type'] === 'added'
                            ? '<svg class="w-3 h-3 text-green-500 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
                            : '<svg class="w-3 h-3 text-red-500 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
                        $permissionName = $change['permission']['name'] ?? 'Unknown';
                        $humanizedName = $this->humanizeName($permissionName);
                        $html .= "<div class=\"text-xs text-gray-600 dark:text-gray-400\">{$icon}{$humanizedName}</div>";
                    }
                    $html .= '</div></div>';
                }
                $html .= '</div>';

                return $html;
            })
                ->asHtml(),

            Text::make('Created At', fn () => $this->created_at?->format('Y-m-d H:i:s'))
                ->sortable(),
        ];
    }

    /**
     * Humanize a name (convert camelCase to Title Case).
     */
    private function humanizeName(string $name): string
    {
        if (empty($name)) {
            return 'Unknown';
        }

        $spaced = preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', $name) ?? $name;
        $spaced = preg_replace('/(?<=[A-Za-z])(?=[0-9])/u', ' ', $spaced) ?? $spaced;
        $spaced = str_replace('_', ' ', $spaced);
        $normalized = trim(preg_replace('/\s+/u', ' ', $spaced) ?? $spaced);

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

        return implode(' ', array_map(fn ($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), $result));
    }

    /**
     * Get the cards available for the resource.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        $filterClass = config('role-manager.audit_filter');

        if ($filterClass && class_exists($filterClass)) {
            return [
                new $filterClass,
            ];
        }

        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
