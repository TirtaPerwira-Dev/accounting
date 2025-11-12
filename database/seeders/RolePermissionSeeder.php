<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $staffRole     = Role::firstOrCreate(['name' => 'staff']);
        $kasubRole     = Role::firstOrCreate(['name' => 'kepala_sub_bagian']);
        $kabagRole     = Role::firstOrCreate(['name' => 'kepala_bagian']);
        $dirUmumRole   = Role::firstOrCreate(['name' => 'direktur_umum']);
        $dirUtamaRole  = Role::firstOrCreate(['name' => 'direktur_utama']);
        $superAdmin    = Role::firstOrCreate(['name' => 'super_admin']);

        // Permissions list
        $permissions = [
            'view_dashboard',
            'page_MyProfilePage',

            // User management
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',

            // Role management
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',

            // Logs
            'view_any_authentication::log',
            'view_authentication::log',
            'delete_authentication::log',
            'delete_any_authentication::log',

            'view_any_activity::log',
            'view_activity::log',
            'delete_activity::log',
            'delete_any_activity::log',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /**
         * Assign Permissions
         */
        $dirUtamaRole->syncPermissions(Permission::all()); // paling tinggi

        $dirUmumRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',
            'view_any_user',
            'view_user',
            'view_any_role',
            'view_role',
            'view_any_activity::log',
            'view_activity::log',
        ]);

        $kabagRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',
            'view_any_user',
            'view_user',
            'view_any_activity::log',
            'view_activity::log',
        ]);

        $kasubRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',
            'view_any_user',
            'view_user',
        ]);

        $staffRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',
        ]);

        // Super Admin full access
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('✅ Roles & Permissions seeded!');
    }
}
