<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create default roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);

        // Create default permissions
        $permissions = [
            // User Management
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',

            // Role Management
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',

            // Authentication Logs
            'view_any_authentication::log',
            'view_authentication::log',
            'delete_authentication::log',
            'delete_any_authentication::log',

            // Activity Logs
            'view_any_activity::log',
            'view_activity::log',
            'delete_activity::log',
            'delete_any_activity::log',

            // Pages
            'page_MyProfilePage',

            // Dashboard access
            'view_dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super_admin
        $superAdminRole->syncPermissions(Permission::all());

        // Assign limited permissions to admin
        $adminPermissions = [
            'view_any_user', 'view_user', 'create_user', 'update_user',
            'view_any_role', 'view_role',
            'view_any_authentication::log', 'view_authentication::log',
            'view_any_activity::log', 'view_activity::log',
            'page_MyProfilePage',
            'view_dashboard',
        ];
        $adminRole->syncPermissions(Permission::whereIn('name', $adminPermissions)->get());

        // Assign basic permissions to editor
        $editorPermissions = [
            'view_any_user', 'view_user',
            'view_any_authentication::log', 'view_authentication::log',
            'view_any_activity::log', 'view_activity::log',
            'page_MyProfilePage',
            'view_dashboard',
        ];
        $editorRole->syncPermissions(Permission::whereIn('name', $editorPermissions)->get());

        // Assign minimal permissions to viewer
        $viewerPermissions = [
            'view_any_user', 'view_user',
            'page_MyProfilePage',
            'view_dashboard',
        ];
        $viewerRole->syncPermissions(Permission::whereIn('name', $viewerPermissions)->get());

        // Create default super admin user if not exists
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@crm.com'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
