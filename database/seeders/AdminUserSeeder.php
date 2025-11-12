<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Super admin user tetap ada
        $admin = User::firstOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'System Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['super_admin']);

        // Default BUMD accounts
        $users = [
            'direktur_utama'     => ['email' => 'dirut@mail.com',  'name' => 'Direktur Utama'],
            'direktur_umum'      => ['email' => 'dirum@mail.com',  'name' => 'Direktur Umum'],
            'kepala_bagian'      => ['email' => 'kabag@mail.com',  'name' => 'Kepala Bagian'],
            'kepala_sub_bagian'  => ['email' => 'kasubag@mail.com', 'name' => 'Kepala Sub Bagian'],
            'staff'              => ['email' => 'staff@mail.com',  'name' => 'Staff'],
        ];

        foreach ($users as $role => $userData) {
            $roleModel = Role::where('name', $role)->first();

            if (!$roleModel) {
                $this->command->error("❌ Role '$role' belum ada — jalankan RolePermissionSeeder dulu");
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole($role);
        }

        $this->command->info('✅ Users seeded & roles assigned!');
    }
}
