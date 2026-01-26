<?php

namespace A2ZWeb\NovaRoleManager;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class RoleManager extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     */
    public function boot(): void
    {
        Nova::script('role-manager', __DIR__.'/../dist/js/tool.js');
        Nova::style('role-manager', __DIR__.'/../dist/css/tool.css');
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): ?MenuSection
    {
        // Menu is handled in NovaServiceProvider
        return null;
    }
}
