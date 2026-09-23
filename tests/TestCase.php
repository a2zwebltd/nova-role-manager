<?php

namespace A2ZWeb\NovaRoleManager\Tests;

use A2ZWeb\NovaRoleManager\Http\Controllers\RolePermissionsController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Exercises the API controller directly. The Nova tool provider (routes,
 * Authorize middleware) is not booted, so no Nova runtime is needed here.
 */
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        // A renamed roles table, so role_id validation must not hard-code `roles`.
        $app['config']->set('permission.table_names.roles', 'acl_roles');

        $app['config']->set('role-manager', require __DIR__.'/../config/role-manager.php');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        (require __DIR__.'/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub')->up();
    }

    protected function defineRoutes($router): void
    {
        $router->prefix('nova-vendor/role-manager')->group(function ($router) {
            $router->get('/role-permissions', [RolePermissionsController::class, 'getRolePermissions']);
            $router->post('/role-permissions/toggle', [RolePermissionsController::class, 'togglePermission']);
            $router->post('/role-permissions/bulk-toggle', [RolePermissionsController::class, 'bulkTogglePermissions']);
            $router->post('/role-permissions/save', [RolePermissionsController::class, 'saveRolePermissions']);
        });
    }
}
