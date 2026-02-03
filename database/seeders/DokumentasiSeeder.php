<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dokumentasi;
use App\Models\User;

class DokumentasiSeederNew extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $dokumentasiData = [
            // KATEGORI 1: PENGGUNAAN MASTER PENOMORAN
            [
                'judul' => 'Panduan Chart of Accounts 3 Level',
                'kategori' => 'Master Penomoran',
                'deskripsi' => 'Penjelasan struktur COA: Kelompok, Rekening, dan Nomor Bantu',
                'konten' => '<h2>Struktur Chart of Accounts (COA) 3 Level</h2><p>Sistem menggunakan struktur COA 3 tingkat sesuai SAKEP</p>',
                'urutan' => 1,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Panduan Kode Proyek',
                'kategori' => 'Master Penomoran',
                'deskripsi' => 'Cara mengelola kode proyek untuk tracking biaya per proyek',
                'konten' => '<h2>Kode Proyek</h2><p>Digunakan untuk tracking biaya per proyek pembangunan/pengembangan.</p>',
                'urutan' => 2,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],

            // KATEGORI 2: JURNAL
            [
                'judul' => 'Tutorial: Input Jurnal Rekening Air',
                'kategori' => 'Jurnal',
                'deskripsi' => 'Panduan mencatat penjualan air dan tagihan pelanggan',
                'konten' => '<h2>Input Jurnal Rekening Air</h2><p>Digunakan untuk mencatat penjualan air dan tagihan bulanan pelanggan.</p>',
                'urutan' => 3,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Tutorial: Input Jurnal Pembelian',
                'kategori' => 'Jurnal',
                'deskripsi' => 'Panduan mencatat pembelian barang/jasa dari supplier',
                'konten' => '<h2>Input Jurnal Pembelian</h2><p>Digunakan untuk mencatat pembelian barang/jasa dari supplier.</p>',
                'urutan' => 4,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Tutorial: Input Jurnal Penerimaan Kas',
                'kategori' => 'Jurnal',
                'deskripsi' => 'Panduan mencatat penerimaan pembayaran dari pelanggan',
                'konten' => '<h2>Input Jurnal Penerimaan Kas</h2><p>Digunakan untuk mencatat penerimaan pembayaran dari pelanggan.</p>',
                'urutan' => 5,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Tutorial: Input Jurnal Bayar Kas/Bank',
                'kategori' => 'Jurnal',
                'deskripsi' => 'Panduan mencatat pembayaran kepada supplier dan biaya operasional',
                'konten' => '<h2>Input Jurnal Bayar Kas/Bank</h2><p>Untuk mencatat pembayaran kepada supplier dan biaya operasional.</p>',
                'urutan' => 6,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Tutorial: Input Jurnal Pemakaian Bahan',
                'kategori' => 'Jurnal',
                'deskripsi' => 'Panduan mencatat pemakaian bahan operasional dari persediaan',
                'konten' => '<h2>Input Jurnal Pemakaian Bahan</h2><p>Untuk mencatat pemakaian bahan operasional dari persediaan.</p>',
                'urutan' => 7,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Tutorial: Input Jurnal Memorial',
                'kategori' => 'Jurnal',
                'deskripsi' => 'Panduan mencatat jurnal penyesuaian dan koreksi',
                'konten' => '<h2>Jurnal Memorial</h2><p>Digunakan untuk jurnal penyesuaian, depresiasi, accrual, deferral, dan koreksi.</p>',
                'urutan' => 8,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],

            // KATEGORI 3: SETUP SALDO
            [
                'judul' => 'Panduan Input Saldo Awal',
                'kategori' => 'Setup Saldo',
                'deskripsi' => 'Cara input saldo awal rekening dan jurnal saat mulai menggunakan sistem',
                'konten' => '<h2>Input Saldo Awal</h2><p>Saat pertama kali menggunakan sistem, input saldo awal untuk semua akun.</p>',
                'urutan' => 9,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],

            // KATEGORI 4: LAPORAN KEUANGAN
            [
                'judul' => 'Panduan Laporan Keuangan',
                'kategori' => 'Laporan Keuangan',
                'deskripsi' => 'Cara membuat dan mengekspor laporan keuangan (Neraca, Laba Rugi, Arus Kas)',
                'konten' => '<h2>Laporan Keuangan</h2><p>Sistem menyediakan berbagai laporan keuangan sesuai SAKEP.</p>',
                'urutan' => 10,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'SOP: Rekonsiliasi Bank Bulanan',
                'kategori' => 'Laporan Keuangan',
                'deskripsi' => 'Prosedur standar untuk melakukan rekonsiliasi bank setiap bulan',
                'konten' => '<h2>SOP Rekonsiliasi Bank</h2><p>Dilakukan setiap akhir bulan untuk memastikan saldo kas/bank di sistem sesuai dengan mutasi bank.</p>',
                'urutan' => 11,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'SOP: Closing Periode Akuntansi',
                'kategori' => 'Laporan Keuangan',
                'deskripsi' => 'Prosedur penutupan buku akhir periode (bulanan/tahunan)',
                'konten' => '<h2>SOP Closing Periode</h2><p>Prosedur penutupan buku akuntansi setiap akhir bulan/tahun.</p>',
                'urutan' => 12,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],

            // KATEGORI 5: BANTUAN
            [
                'judul' => 'FAQ: Pertanyaan yang Sering Diajukan',
                'kategori' => 'Bantuan',
                'deskripsi' => 'Kumpulan pertanyaan dan jawaban terkait penggunaan sistem',
                'konten' => '<h2>FAQ - Pertanyaan yang Sering Diajukan</h2><p>Kumpulan pertanyaan dan jawaban umum.</p>',
                'urutan' => 13,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Panduan Manajemen User dan Permission',
                'kategori' => 'Bantuan',
                'deskripsi' => 'Cara mengelola user, role, dan hak akses dalam sistem',
                'konten' => '<h2>Manajemen User dan Permission</h2><p>Sistem menggunakan role-based access control (RBAC).</p>',
                'urutan' => 14,
                'is_published' => true,
                'is_manual_book' => false,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
        ];

        foreach ($dokumentasiData as $data) {
            Dokumentasi::updateOrCreate(['judul' => $data['judul']], $data);
        }

        $this->command->info('✅ Dokumentasi berhasil di-seed! Total: ' . count($dokumentasiData) . ' dokumentasi.');
    }
}

