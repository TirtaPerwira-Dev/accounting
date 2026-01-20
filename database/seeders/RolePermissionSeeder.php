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

        // Permissions untuk kabag
        $kabagRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',

            // CRUD chart of accounts
            'view_any_kelompok',
            'view_kelompok',
            'create_kelompok',
            'update_kelompok',
            'delete_kelompok',
            'view_any_rekening',
            'view_rekening',
            'create_rekening',
            'update_rekening',
            'delete_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',
            'create_nomor::bantu',
            'update_nomor::bantu',
            'delete_nomor::bantu',

            // All journals - view and approval
            'view_any_jurnal::pembelian',
            'view_jurnal::pembelian',
            'create_jurnal::pembelian',
            'update_jurnal::pembelian',
            'delete_jurnal::pembelian',
            'view_any_jurnal::rekening::air',
            'view_jurnal::rekening::air',
            'create_jurnal::rekening::air',
            'update_jurnal::rekening::air',
            'delete_jurnal::rekening::air',
            'view_any_jurnal::penerimaan::kas',
            'view_jurnal::penerimaan::kas',
            'create_jurnal::penerimaan::kas',
            'update_jurnal::penerimaan::kas',
            'delete_jurnal::penerimaan::kas',
            'view_any_jurnal::bayar::kas::bank',
            'view_jurnal::bayar::kas::bank',
            'create_jurnal::bayar::kas::bank',
            'update_jurnal::bayar::kas::bank',
            'delete_jurnal::bayar::kas::bank',
            'view_any_jurnal::memorial',
            'view_jurnal::memorial',
            'create_jurnal::memorial',
            'update_jurnal::memorial',
            'delete_jurnal::memorial',
            'view_any_jurnal::pemakaian::bahan',
            'view_jurnal::pemakaian::bahan',
            'create_jurnal::pemakaian::bahan',
            'update_jurnal::pemakaian::bahan',
            'delete_jurnal::pemakaian::bahan',

            // Input saldo awal
            'view_any_saldo::awal::rekening',
            'view_saldo::awal::rekening',
            'create_saldo::awal::rekening',
            'update_saldo::awal::rekening',
            'delete_saldo::awal::rekening',
            'view_any_saldo::awal::jurnal',
            'view_saldo::awal::jurnal',
            'create_saldo::awal::jurnal',
            'update_saldo::awal::jurnal',
            'delete_saldo::awal::jurnal',

            // Company settings
            'view_any_company',
            'view_company',
            'create_company',
            'update_company',

            // Role management (no super admin level access)
            'view_any_role',
            'view_role',
            'update_role',

            // Laporan keuangan
            'page_FinancialReports',
        ]);

        // Permissions untuk kasub verifikasi pembukuan
        $kasubVerifikasiPembukuanRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',
            'view_any_user',
            'view_user',

            // CRUD chart of accounts
            'view_any_kelompok',
            'view_kelompok',
            'create_kelompok',
            'update_kelompok',
            'delete_kelompok',
            'view_any_rekening',
            'view_rekening',
            'create_rekening',
            'update_rekening',
            'delete_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',
            'create_nomor::bantu',
            'update_nomor::bantu',
            'delete_nomor::bantu',

            // Lihat jurnal bayar kas bank & memorial (approving post)
            'view_any_jurnal::bayar::kas::bank',
            'view_jurnal::bayar::kas::bank',
            'create_jurnal::bayar::kas::bank',
            'update_jurnal::bayar::kas::bank',
            'view_any_jurnal::memorial',
            'view_jurnal::memorial',
            'create_jurnal::memorial',
            'update_jurnal::memorial',

            // Input saldo awal
            'view_any_saldo::awal::rekening',
            'view_saldo::awal::rekening',
            'create_saldo::awal::rekening',
            'update_saldo::awal::rekening',

            // Laporan keuangan
            'page_FinancialReports',
        ]);

        // Permissions untuk kasub anggaran pendapatan
        $kasubAnggaranPendapatanbRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',
            'view_any_user',
            'view_user',

            // CRUD chart of accounts
            'view_any_kelompok',
            'view_kelompok',
            'create_kelompok',
            'update_kelompok',
            'delete_kelompok',
            'view_any_rekening',
            'view_rekening',
            'create_rekening',
            'update_rekening',
            'delete_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',
            'create_nomor::bantu',
            'update_nomor::bantu',
            'delete_nomor::bantu',

            // Lihat jurnal penerimaan kas & rekening air (approving post)
            'view_any_jurnal::penerimaan::kas',
            'view_jurnal::penerimaan::kas',
            'create_jurnal::penerimaan::kas',
            'update_jurnal::penerimaan::kas',
            'view_any_jurnal::rekening::air',
            'view_jurnal::rekening::air',
            'create_jurnal::rekening::air',
            'update_jurnal::rekening::air',

            // Input saldo awal
            'view_any_saldo::awal::rekening',
            'view_saldo::awal::rekening',
            'create_saldo::awal::rekening',
            'update_saldo::awal::rekening',

            // Laporan keuangan
            'page_FinancialReports',
        ]);

        // Permissions untuk staff verifikasi pembukuan
        $staffVerifikasiPembukuanRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',

            // Hanya lihat chart of accounts
            'view_any_kelompok',
            'view_kelompok',
            'view_any_rekening',
            'view_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',

            // Input dan edit jurnal bayar kas bank & memorial (draft only)
            'view_any_jurnal::bayar::kas::bank',
            'view_jurnal::bayar::kas::bank',
            'create_jurnal::bayar::kas::bank',
            'update_jurnal::bayar::kas::bank',
            'view_any_jurnal::memorial',
            'view_jurnal::memorial',
            'create_jurnal::memorial',
            'update_jurnal::memorial',
        ]);

        // Permissions untuk staff anggaran pendapatan
        $staffAnggaranPendapatanRole->syncPermissions([
            // Hanya lihat chart of accounts
            'view_any_kelompok',
            'view_kelompok',
            'view_any_rekening',
            'view_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',

            // Input dan edit jurnal penerimaan kas & rekening air (draft only)
            'view_any_jurnal::penerimaan::kas',
            'view_jurnal::penerimaan::kas',
            'create_jurnal::penerimaan::kas',
            'update_jurnal::penerimaan::kas',
            'view_any_jurnal::rekening::air',
            'view_jurnal::rekening::air',
            'create_jurnal::rekening::air',
            'update_jurnal::rekening::air',
            // Hanya lihat chart of accounts
            'view_any_kelompok',
            'view_kelompok',
            'view_any_rekening',
            'view_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',

            // Input dan edit jurnal pembelian & pemakaian bahan (draft only)
            'view_any_jurnal::pembelian',
            'view_jurnal::pembelian',
            'create_jurnal::pembelian',
            'update_jurnal::pembelian',
            'view_any_jurnal::pemakaian::bahan',
            'view_jurnal::pemakaian::bahan',
            'create_jurnal::pemakaian::bahan',
            'update_jurnal::pemakaian::bahan',
        ]);

        // Permissions untuk staff umum
        $staffRole->syncPermissions([
            'view_dashboard',
            'page_MyProfilePage',

            // Hanya lihat chart of accounts
            'view_any_kelompok',
            'view_kelompok',
            'view_any_rekening',
            'view_rekening',
            'view_any_nomor::bantu',
            'view_nomor::bantu',

            // Input dan edit semua jenis jurnal (draft only)
            'view_any_jurnal::pembelian',
            'view_jurnal::pembelian',
            'create_jurnal::pembelian',
            'update_jurnal::pembelian',
            'view_any_jurnal::rekening::air',
            'view_jurnal::rekening::air',
            'create_jurnal::rekening::air',
            'update_jurnal::rekening::air',
            'view_any_jurnal::penerimaan::kas',
            'view_jurnal::penerimaan::kas',
            'create_jurnal::penerimaan::kas',
            'update_jurnal::penerimaan::kas',
            'view_any_jurnal::bayar::kas::bank',
            'view_jurnal::bayar::kas::bank',
            'create_jurnal::bayar::kas::bank',
            'update_jurnal::bayar::kas::bank',
            'view_any_jurnal::memorial',
            'view_jurnal::memorial',
            'create_jurnal::memorial',
            'update_jurnal::memorial',
            'view_any_jurnal::pemakaian::bahan',
            'view_jurnal::pemakaian::bahan',
            'create_jurnal::pemakaian::bahan',
            'update_jurnal::pemakaian::bahan',
        ]);

        // Super Admin full access
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('✅ Roles & Permissions seeded!');
    }
}
