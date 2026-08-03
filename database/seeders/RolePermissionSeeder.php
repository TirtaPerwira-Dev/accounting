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
        $staffAnggaranPendapatanRole     = Role::firstOrCreate(['name' => 'staff_anggaran_pendapatan']);
        $staffVerifikasiPembukuanRole     = Role::firstOrCreate(['name' => 'staff_verifikasi_pembukuan']);
        $kasubRole     = Role::firstOrCreate(['name' => 'kepala_sub_bagian']);
        $kasubAnggaranPendapatanbRole     = Role::firstOrCreate(['name' => 'kepala_sub_bagian_anggaran_pendapatan']);
        $kasubVerifikasiPembukuanRole     = Role::firstOrCreate(['name' => 'kepala_sub_bagian_verifikasi_pembukuan']);
        $kabagRole     = Role::firstOrCreate(['name' => 'kepala_bagian']);
        $dirUmumRole   = Role::firstOrCreate(['name' => 'direktur_umum']);
        $dirUtamaRole  = Role::firstOrCreate(['name' => 'direktur_utama']);
        $superAdmin    = Role::firstOrCreate(['name' => 'super_admin']);

        // Permissions list
        $permissions = [
            'view_dashboard',
            'page_MyProfilePage',
            'page_FinancialReports',

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

            // Journal posting permissions
            'post_jurnal::pembelian',
            'post_jurnal::rekening::air',
            'post_jurnal::penerimaan::kas',
            'post_jurnal::bayar::kas::bank',
            'post_jurnal::memorial',
            'post_jurnal::pemakaian::bahan',
            'confirm_jurnal::pembelian',
            'confirm_jurnal::rekening::air',
            'confirm_jurnal::penerimaan::kas',
            'confirm_jurnal::bayar::kas::bank',
            'confirm_jurnal::memorial',
            'confirm_jurnal::pemakaian::bahan',
            'unconfirm_jurnal::pembelian',
            'unconfirm_jurnal::rekening::air',
            'unconfirm_jurnal::penerimaan::kas',
            'unconfirm_jurnal::bayar::kas::bank',
            'unconfirm_jurnal::memorial',
            'unconfirm_jurnal::pemakaian::bahan',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Baseline role model:
        // - super_admin: all
        // - staff*: input only
        // - kepala_sub_bagian*: input + confirm + post
        // - kepala_bagian and direktur*: view only
        $journalKeys = [
            'jurnal::pembelian',
            'jurnal::rekening::air',
            'jurnal::penerimaan::kas',
            'jurnal::bayar::kas::bank',
            'jurnal::memorial',
            'jurnal::pemakaian::bahan',
        ];

        $viewJournalPermissions = [];
        $inputJournalPermissions = [];
        $confirmJournalPermissions = [];
        $unconfirmJournalPermissions = [];
        $postJournalPermissions = [];

        foreach ($journalKeys as $key) {
            $viewJournalPermissions[] = 'view_any_' . $key;
            $viewJournalPermissions[] = 'view_' . $key;

            $inputJournalPermissions[] = 'view_any_' . $key;
            $inputJournalPermissions[] = 'view_' . $key;
            $inputJournalPermissions[] = 'create_' . $key;
            $inputJournalPermissions[] = 'update_' . $key;

            $confirmJournalPermissions[] = 'confirm_' . $key;
            $unconfirmJournalPermissions[] = 'unconfirm_' . $key;
            $postJournalPermissions[] = 'post_' . $key;
        }

        $chartViewPermissions = [
            'view_any_kelompok',
            'view_kelompok',
            'view_any_rekening',
            'view_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',
        ];

        $baseViewPermissions = [
            'view_dashboard',
            'page_MyProfilePage',
            'page_FinancialReports',
        ];

        $staffPermissions = array_values(array_unique(array_merge(
            $baseViewPermissions,
            $chartViewPermissions,
            $inputJournalPermissions
        )));

        $kasubPermissions = array_values(array_unique(array_merge(
            $baseViewPermissions,
            $chartViewPermissions,
            $inputJournalPermissions,
            $confirmJournalPermissions,
            $unconfirmJournalPermissions,
            $postJournalPermissions
        )));

        $viewerPermissions = array_values(array_unique(array_merge(
            $baseViewPermissions,
            $chartViewPermissions,
            $viewJournalPermissions
        )));

        $staffRole->syncPermissions($staffPermissions);
        $staffAnggaranPendapatanRole->syncPermissions($staffPermissions);
        $staffVerifikasiPembukuanRole->syncPermissions($staffPermissions);

        $kasubRole->syncPermissions($kasubPermissions);
        $kasubAnggaranPendapatanbRole->syncPermissions($kasubPermissions);
        $kasubVerifikasiPembukuanRole->syncPermissions($kasubPermissions);

        $kabagRole->syncPermissions($viewerPermissions);
        $dirUmumRole->syncPermissions($viewerPermissions);
        $dirUtamaRole->syncPermissions($viewerPermissions);

        // Super Admin full access
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('✅ Roles & Permissions seeded!');
    }
}
