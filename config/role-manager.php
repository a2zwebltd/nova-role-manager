<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Model
    |--------------------------------------------------------------------------
    |
    | The role model that the package should use.
    |
    */
    'role_model' => \Spatie\Permission\Models\Role::class,
    // 'role_model' => \A2ZWeb\NovaRoleManager\Models\AuditableRole::class, // For audit logging

    /*
    |--------------------------------------------------------------------------
    | Permission Model
    |--------------------------------------------------------------------------
    |
    | The permission model that the package should use.
    |
    */
    'permission_model' => \Spatie\Permission\Models\Permission::class,
    // 'permission_model' => \A2ZWeb\NovaRoleManager\Models\AuditablePermission::class, // For audit logging

    /*
    |--------------------------------------------------------------------------
    | Permission Repository
    |--------------------------------------------------------------------------
    |
    | Optional: A custom repository class for fetching permissions.
    | The repository must have an allForGuard(string $guardName) method.
    | Set to null to use basic Eloquent queries.
    |
    */
    'permission_repository' => \A2ZWeb\NovaRoleManager\Repositories\PermissionRepository::class,

    /*
    |--------------------------------------------------------------------------
    | Required Permissions
    |--------------------------------------------------------------------------
    |
    | Optional: Permissions required to access the role manager.
    | Set to null to disable permission checks (allow all authenticated users).
    |
    */
    'edit_permission' => null,
    'view_audit_logs_permission' => null,

    /*
    |--------------------------------------------------------------------------
    | User/Employee Resource
    |--------------------------------------------------------------------------
    |
    | Optional: The Nova resource for users/employees. If set, the audit log
    | will create links to user profiles. Set to null to disable user links.
    |
    */
    'user_resource' => null,

    /*
    |--------------------------------------------------------------------------
    | Enable Audit Logs
    |--------------------------------------------------------------------------
    |
    | Enable or disable the "View Audit Logs" button in the role manager.
    | Requires owen-it/laravel-auditing package to be installed.
    |
    */
    'enable_audit_logs' => false,

    /*
    |--------------------------------------------------------------------------
    | Audit Log Resource
    |--------------------------------------------------------------------------
    |
    | Nova resource class for viewing audit logs. The default resource
    | is provided by the package and works with owen-it/laravel-auditing.
    | You can override this with your own resource class if needed.
    |
    */
    'audit_resource' => \A2ZWeb\NovaRoleManager\Nova\RoleAuditLog::class,

    /*
    |--------------------------------------------------------------------------
    | Audit Log Filter
    |--------------------------------------------------------------------------
    |
    | Filter class for filtering audit logs by role. The default filter
    | is provided by the package. You can override with your own if needed.
    |
    */
    'audit_filter' => \A2ZWeb\NovaRoleManager\Nova\Filters\RoleAuditFilter::class,

    /*
    |--------------------------------------------------------------------------
    | Ungrouped Permissions Category
    |--------------------------------------------------------------------------
    |
    | Category name for permissions without a group or with no "/" in the group.
    | Set to null to hide ungrouped permissions.
    |
    */
    'ungrouped_permissions_category' => 'Other',

    /*
    |--------------------------------------------------------------------------
    | Guard Order
    |--------------------------------------------------------------------------
    |
    | Define the order in which guards should be displayed in the UI.
    |
    */
    'guard_order' => [
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Roles
    |--------------------------------------------------------------------------
    |
    | List of role names that cannot be edited.
    |
    */
    'protected_roles' => [
    ],
];
