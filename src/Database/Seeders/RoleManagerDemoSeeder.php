<?php

declare(strict_types=1);

namespace A2ZWeb\NovaRoleManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagerDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createAdminGuard();
        $this->createWebGuard();
    }

    /**
     * Create admin guard roles and permissions.
     */
    protected function createAdminGuard(): void
    {
        $guard = 'admin';

        // Create Permissions - Basic CRUD operations
        $permissions = [
            // Posts (nested group example)
            ['name' => 'viewPosts', 'group' => 'Blog / Posts', 'guard_name' => $guard],
            ['name' => 'createPosts', 'group' => 'Blog / Posts', 'guard_name' => $guard],
            ['name' => 'editPosts', 'group' => 'Blog / Posts', 'guard_name' => $guard],
            ['name' => 'deletePosts', 'group' => 'Blog / Posts', 'guard_name' => $guard],

            // Comments (nested group example)
            ['name' => 'viewComments', 'group' => 'Blog / Comments', 'guard_name' => $guard],
            ['name' => 'createComments', 'group' => 'Blog / Comments', 'guard_name' => $guard],
            ['name' => 'editComments', 'group' => 'Blog / Comments', 'guard_name' => $guard],
            ['name' => 'deleteComments', 'group' => 'Blog / Comments', 'guard_name' => $guard],

            // Users (nested group example)
            ['name' => 'viewUsers', 'group' => 'Management / Users', 'guard_name' => $guard],
            ['name' => 'createUsers', 'group' => 'Management / Users', 'guard_name' => $guard],
            ['name' => 'editUsers', 'group' => 'Management / Users', 'guard_name' => $guard],
            ['name' => 'deleteUsers', 'group' => 'Management / Users', 'guard_name' => $guard],

            // Settings (deeply nested group example)
            ['name' => 'viewSettings', 'group' => 'Management / Settings / General', 'guard_name' => $guard],
            ['name' => 'editSettings', 'group' => 'Management / Settings / General', 'guard_name' => $guard],

            // Ungrouped examples (will show under "Other")
            ['name' => 'viewReports', 'group' => 'Reports', 'guard_name' => $guard],
            ['name' => 'exportData', 'group' => 'System Tools', 'guard_name' => $guard],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                ['group' => $permission['group']]
            );
        }

        // Create Roles
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $editor = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => $guard]);
        $author = Role::firstOrCreate(['name' => 'Author', 'guard_name' => $guard]);

        // Assign Permissions to Roles
        $admin->syncPermissions(Permission::where('guard_name', $guard)->pluck('name'));

        $editor->syncPermissions([
            'viewPosts', 'createPosts', 'editPosts', 'deletePosts',
            'viewComments', 'editComments', 'deleteComments',
            'viewUsers',
            'viewReports',
        ]);

        $author->syncPermissions([
            'viewPosts', 'createPosts', 'editPosts',
            'viewComments', 'createComments',
        ]);
    }

    /**
     * Create web guard roles and permissions.
     */
    protected function createWebGuard(): void
    {
        $guard = 'web';

        // Create Permissions - Simple user-facing operations
        $permissions = [
            // Basic user operations
            ['name' => 'viewContent', 'group' => 'Content / Reading', 'guard_name' => $guard],
            ['name' => 'createContent', 'group' => 'Content / Writing', 'guard_name' => $guard],
            ['name' => 'editOwnContent', 'group' => 'Content / Writing', 'guard_name' => $guard],
            ['name' => 'deleteOwnContent', 'group' => 'Content / Writing', 'guard_name' => $guard],

            // Profile
            ['name' => 'editProfile', 'group' => 'Account / Profile', 'guard_name' => $guard],
            ['name' => 'uploadAvatar', 'group' => 'Account / Profile', 'guard_name' => $guard],

            // Ungrouped example
            ['name' => 'accessBetaFeatures', 'group' => 'Beta Features', 'guard_name' => $guard],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                ['group' => $permission['group']]
            );
        }

        // Create Roles
        $member = Role::firstOrCreate(['name' => 'Member', 'guard_name' => $guard]);
        $contributor = Role::firstOrCreate(['name' => 'Contributor', 'guard_name' => $guard]);

        // Assign Permissions
        $contributor->syncPermissions(Permission::where('guard_name', $guard)->pluck('name'));

        $member->syncPermissions([
            'viewContent',
            'editProfile', 'uploadAvatar',
        ]);
    }
}
