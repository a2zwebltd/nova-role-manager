# Changelog

## [1.2.1] - 2026-09-23

### Security
- `protected_roles` was enforced only by the save endpoint. `POST /nova-vendor/role-manager/role-permissions/toggle` and `/bulk-toggle` skipped it, so anyone with access to the tool could grant or revoke permissions on a protected role (e.g. `SUPER_ADMIN`) with a direct request. All three write endpoints now go through one shared guard that checks `edit_permission` and `protected_roles`.

### Fixed
- `role_id` validation hard-coded `exists:roles,id`. It now validates against the configured `role-manager.role_model`, so a renamed Spatie roles table (`permission.table_names.roles`) or a custom role model works.

### Added
- Pest + Orchestra Testbench test suite for the role permission endpoints.

## [1.2.0] - 2026-08-03

### Added
- spatie/laravel-permission v7 and v8 support

## [1.1.1] - 2026-06-04

### Changed
- Tool name moved to config (`role-manager.tool_name`)

## [1.1.0] - 2026-03-28

### Added
- Laravel 13 support

### Changed
- Minimum PHP version raised to 8.2

## [1.0.0] - 2026-01-25

### Added
- Initial release
- Visual role and permission management for Laravel Nova
- Multi-guard support
- Permission grouping and categorization
- Optional audit logging via owen-it/laravel-auditing
- Protected role management
- Custom permission repository support
