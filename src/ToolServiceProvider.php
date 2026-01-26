<?php

namespace A2ZWeb\NovaRoleManager;

use A2ZWeb\NovaRoleManager\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class ToolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $this->routes();
            $this->registerAuditResource();
        });

        $this->publishes([
            __DIR__.'/../config/role-manager.php' => config_path('role-manager.php'),
        ], 'role-manager-config');

        Nova::serving(function (ServingNova $event) {
            //
        });
    }

    /**
     * Register the audit log resource if enabled.
     */
    protected function registerAuditResource(): void
    {
        if (config('role-manager.enable_audit_logs', false)) {
            $auditResourceClass = config('role-manager.audit_resource');

            if ($auditResourceClass && class_exists($auditResourceClass)) {
                Nova::resources([
                    $auditResourceClass,
                ]);
            }
        }
    }

    /**
     * Register the tool's routes.
     */
    protected function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Nova::router(['nova', 'nova.auth', Authorize::class], 'role-manager')
            ->group(__DIR__.'/../routes/inertia.php');

        Route::middleware(['nova', 'nova.auth', Authorize::class])
            ->prefix('nova-vendor/role-manager')
            ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/role-manager.php',
            'role-manager'
        );
    }
}
