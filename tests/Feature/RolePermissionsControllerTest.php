<?php

use A2ZWeb\NovaRoleManager\Tests\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $this->editor = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $this->perm = Permission::create(['name' => 'posts.delete', 'guard_name' => 'web']);
    $this->perm2 = Permission::create(['name' => 'posts.publish', 'guard_name' => 'web']);

    config()->set('role-manager.protected_roles', ['admin']);

    $this->actingAs(User::create([
        'name' => 'Staff',
        'email' => 'staff@example.com',
        'password' => 'secret',
    ]));
});

$base = '/nova-vendor/role-manager/role-permissions';

it('blocks toggle on a protected role', function () use ($base) {
    $this->postJson("$base/toggle", [
        'role_id' => $this->admin->id,
        'permission_id' => $this->perm->id,
        'assign' => true,
    ])->assertForbidden()->assertJson(['error' => 'This role is protected and cannot be edited']);

    expect($this->admin->fresh()->hasPermissionTo('posts.delete'))->toBeFalse();
});

it('blocks bulk-toggle on a protected role', function () use ($base) {
    $this->postJson("$base/bulk-toggle", [
        'role_id' => $this->admin->id,
        'permission_ids' => [$this->perm->id, $this->perm2->id],
        'assign' => true,
    ])->assertForbidden();

    expect($this->admin->fresh()->permissions)->toHaveCount(0);
});

it('blocks revoking through bulk-toggle on a protected role', function () use ($base) {
    $this->admin->givePermissionTo($this->perm);

    $this->postJson("$base/bulk-toggle", [
        'role_id' => $this->admin->id,
        'permission_ids' => [$this->perm->id],
        'assign' => false,
    ])->assertForbidden();

    expect($this->admin->fresh()->hasPermissionTo('posts.delete'))->toBeTrue();
});

it('blocks save on a protected role', function () use ($base) {
    $this->postJson("$base/save", [
        'role_id' => $this->admin->id,
        'permission_ids_to_add' => [$this->perm->id],
        'permission_ids_to_remove' => [],
    ])->assertForbidden();

    expect($this->admin->fresh()->permissions)->toHaveCount(0);
});

it('still edits unprotected roles through every endpoint', function () use ($base) {
    $this->postJson("$base/toggle", [
        'role_id' => $this->editor->id,
        'permission_id' => $this->perm->id,
        'assign' => true,
    ])->assertOk()->assertJson(['success' => true, 'assigned' => true]);

    $this->postJson("$base/bulk-toggle", [
        'role_id' => $this->editor->id,
        'permission_ids' => [$this->perm2->id],
        'assign' => true,
    ])->assertOk()->assertJson(['count' => 1]);

    $this->postJson("$base/save", [
        'role_id' => $this->editor->id,
        'permission_ids_to_add' => [],
        'permission_ids_to_remove' => [$this->perm->id],
    ])->assertOk();

    expect($this->editor->fresh()->permissions->pluck('name')->all())->toBe(['posts.publish']);
});

it('enforces edit_permission on toggle and bulk-toggle', function () use ($base) {
    config()->set('role-manager.edit_permission', 'roles.edit');
    Permission::create(['name' => 'roles.edit', 'guard_name' => 'web']);

    $this->postJson("$base/toggle", [
        'role_id' => $this->editor->id,
        'permission_id' => $this->perm->id,
        'assign' => true,
    ])->assertForbidden()->assertJson(['error' => 'You do not have permission to edit roles']);

    $this->postJson("$base/bulk-toggle", [
        'role_id' => $this->editor->id,
        'permission_ids' => [$this->perm->id],
        'assign' => true,
    ])->assertForbidden();

    auth()->user()->givePermissionTo('roles.edit');

    $this->postJson("$base/toggle", [
        'role_id' => $this->editor->id,
        'permission_id' => $this->perm->id,
        'assign' => true,
    ])->assertOk();
});

it('validates role_id against the configured roles table', function () use ($base) {
    expect($this->admin->getTable())->toBe('acl_roles');

    $this->getJson("$base?role_id={$this->editor->id}")->assertOk();

    $this->postJson("$base/toggle", [
        'role_id' => 9999,
        'permission_id' => $this->perm->id,
        'assign' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
});
