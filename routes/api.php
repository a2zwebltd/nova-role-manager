<?php

use A2ZWeb\NovaRoleManager\Http\Controllers\PermissionGroupsController;
use A2ZWeb\NovaRoleManager\Http\Controllers\RolePermissionsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

// Route::get('/', function (Request $request) {
//     //
// });

Route::get('/permission-groups', PermissionGroupsController::class);

Route::get('/role-permissions', [RolePermissionsController::class, 'getRolePermissions']);
Route::get('/role-permissions/all', [RolePermissionsController::class, 'getAllRolePermissions']);
Route::post('/role-permissions/toggle', [RolePermissionsController::class, 'togglePermission']);
Route::post('/role-permissions/bulk-toggle', [RolePermissionsController::class, 'bulkTogglePermissions']);
Route::post('/role-permissions/save', [RolePermissionsController::class, 'saveRolePermissions']);
