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
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['super_admin']);

        // Default BUMD accounts
        $users = [
            'direktur_utama'     => ['email' => 'dirut@mail.com',  'name' => 'Direktur Utama', 'username' => 'dirut'],
            'direktur_umum'      => ['email' => 'dirum@mail.com',  'name' => 'Direktur Umum', 'username' => 'dirum'],
            'kepala_bagian'      => ['email' => 'kabag@mail.com',  'name' => 'Kepala Bagian', 'username' => 'kabag'],
            'kepala_sub_bagian'  => ['email' => 'kasubag@mail.com', 'name' => 'Kepala Sub Bagian', 'username' => 'kasubag'],
            'kepala_sub_bagian_anggaran_pendapatan'  => ['email' => 'kasubanggaran@mail.com', 'name' => 'Kepala Sub Bagian Anggaran Pendapatan', 'username' => 'kasubanggaran'],
            'kepala_sub_bagian_verifikasi_pembukuan'  => ['email' => 'kasubverifikasi@mail.com', 'name' => 'Kepala Sub Bagian Verifikasi Pembukuan', 'username' => 'kasubverifikasi'],
            'staff'              => ['email' => 'staff@mail.com',  'name' => 'Staff', 'username' => 'staff'],
            'staff_anggaran_pendapatan'              => ['email' => 'staffanggaran@mail.com',  'name' => 'Staff Anggaran Pendapatan', 'username' => 'staffanggaran'],
            'staff_verifikasi_pembukuan'              => ['email' => 'staffverifikasi@mail.com',  'name' => 'Staff Verifikasi Pembukuan', 'username' => 'staffverifikasi'],
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
                    'username' => $userData['username'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole($role);
        }

        $this->command->info('✅ Users seeded & roles assigned!');
    }
}
