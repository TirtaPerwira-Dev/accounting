<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dokumentasi;
use App\Models\User;

class DokumentasiSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        
        // Clear existing documentation to avoid duplicates during re-seeding
        // Dokumentasi::truncate(); // Optional: uncomment if you want to wipe clean

        $dokumentasiData = [
            // ==================================================================================
            // BAB 1: PENDAHULUAN
            // ==================================================================================
            [
                'judul' => '1. Pengenalan Sistem Akuntansi',
                'kategori' => 'Pendahuluan',
                'deskripsi' => 'Gambaran umum tentang Sistem Akuntansi Terintegrasi',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Selamat Datang di Sistem Akuntansi</h3>
                        <p>Sistem ini dirancang untuk membantu pengelolaan keuangan perusahaan secara terintegrasi, akurat, dan real-time. Sistem mencakup seluruh siklus akuntansi mulai dari pencatatan transaksi (jurnal) hingga pelaporan keuangan.</p>
                        
                        <h4>Fitur Utama:</h4>
                        <ul>
                            <li><strong>Multi-User & Role Management</strong>: Akses terkontrol sesuai jabatan.</li>
                            <li><strong>Jurnal Terintegrasi</strong>: Pembelian, Penjualan (Rekening Air), Kas/Bank, dan Memorial.</li>
                            <li><strong>Sistem Approval</strong>: Validasi berjenjang (Draft -> Confirm -> Post).</li>
                            <li><strong>Laporan Real-time</strong>: Neraca, Laba Rugi, dan Buku Besar selalu up-to-date setelah posting.</li>
                            <li><strong>Audit Trail</strong>: Mencatat siapa yang melakukan input atau edit data.</li>
                        </ul>
                    </div>
                ',
                'urutan' => 1,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => '2. Login dan Dashboard',
                'kategori' => 'Pendahuluan',
                'deskripsi' => 'Cara masuk ke sistem dan memahami tampilan awal',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Login ke Sistem</h3>
                        <ol>
                            <li>Buka alamat website aplikasi.</li>
                            <li>Masukkan <strong>Email</strong> dan <strong>Password</strong> Anda.</li>
                            <li>Klik tombol <strong>Sign In</strong>.</li>
                        </ol>

                        <h3>Dashboard Utama</h3>
                        <p>Setelah login, Anda akan disambut dengan Dashboard yang berisi ringkasan penting:</p>
                        <ul>
                            <li><strong>Statistik Jurnal</strong>: Jumlah jurnal pending/draft.</li>
                            <li><strong>Grafik Keuangan</strong>: Tren pendapatan dan pengeluaran (jika ada hak akses).</li>
                            <li><strong>Menu Navigasi</strong>: Terletak di sebelah kiri, dikelompokkan berdasarkan fungsi (Master Data, Jurnal, Laporan).</li>
                        </ul>
                    </div>
                ',
                'urutan' => 2,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],

            // ==================================================================================
            // BAB 2: MASTER DATA
            // ==================================================================================
            [
                'judul' => '3. Master Data: Chart of Accounts (COA)',
                'kategori' => 'Master Data',
                'deskripsi' => 'Memahami struktur akun 3 Level (Kelompok, Rekening, Nomor Bantu)',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Struktur Akun 3 Level</h3>
                        <p>Sistem ini menggunakan struktur akun hierarkis:</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-4">
                            <div class="border p-4 rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="text-primary-600 dark:text-primary-400">Level 1: Kelompok Akun</h4>
                                <p>Kategori utama akun. Contoh:</p>
                                <ul>
                                    <li><strong>10</strong>: Aktiva Lancar</li>
                                    <li><strong>20</strong>: Hutang</li>
                                    <li><strong>40</strong>: Pendapatan</li>
                                </ul>
                            </div>
                            <div class="border p-4 rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="text-primary-600 dark:text-primary-400">Level 2: Rekening</h4>
                                <p>Sub-kategori dari Kelompok. Contoh:</p>
                                <ul>
                                    <li><strong>1101</strong>: Kas</li>
                                    <li><strong>1102</strong>: Bank</li>
                                    <li><strong>2101</strong>: Hutang Dagang</li>
                                </ul>
                            </div>
                            <div class="border p-4 rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="text-primary-600 dark:text-primary-400">Level 3: Nomor Bantu</h4>
                                <p>Detail spesifik akun transaksi. Contoh:</p>
                                <ul>
                                    <li><strong>01</strong>: Kas Besar</li>
                                    <li><strong>02</strong>: Kas Kecil</li>
                                    <li><strong>01</strong>: Bank BNI</li>
                                </ul>
                            </div>
                        </div>

                        <p><strong>PENTING:</strong> Transaksi jurnal hanya dapat dilakukan pada akun Level 3 (Nomor Bantu).</p>
                    </div>
                ',
                'urutan' => 3,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => '4. Master Data: Kode Proyek & Lainnya',
                'kategori' => 'Master Data',
                'deskripsi' => 'Penggunaan Kode Proyek untuk alokasi biaya',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Kode Proyek</h3>
                        <p>Digunakan untuk mengalokasikan biaya atau pendapatan ke proyek tertentu. Ini memudahkan pelaporan laba/rugi per proyek.</p>
                        <ul>
                            <li>Menu: <strong>Master Data > Kode Proyek</strong></li>
                            <li>Contoh: <em>01 - Pembangunan Gedung A</em>, <em>02 - Perawatan Pipa Wilayah B</em>.</li>
                        </ul>

                        <h3>Kode Wilayah (Untuk Rekening Air)</h3>
                        <p>Digunakan khusus untuk modul Jurnal Rekening Air guna memisahkan pendapatan berdasarkan wilayah pelanggan.</p>
                    </div>
                ',
                'urutan' => 4,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],

            // ==================================================================================
            // BAB 3: TRANSAKSI JURNAL
            // ==================================================================================
             [
                'judul' => '5. Konsep Dasar Input Jurnal',
                'kategori' => 'Transaksi Jurnal',
                'deskripsi' => 'Alur kerja: Input (Staging) -> Konfirmasi -> Posting',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Alur Kerja Transaksi</h3>
                        <p>Semua modul jurnal mengikuti alur kerja standar berikut:</p>
                        
                        <ol>
                            <li>
                                <strong>Input / Create (Staging Area)</strong>
                                <p>Saat membuat jurnal baru, Anda menggunakan tampilan input sederhana. Data item dimasukkan satu per satu ke dalam tabel sementara.</p>
                            </li>
                            <li>
                                <strong>Draft (Pending Confirmation)</strong>
                                <p>Setelah disimpan, jurnal berstatus <em>Draft</em>. Anda masih bisa mengeditnya secara bebas. Di tahap edit, tampilan berubah menjadi tabel interaktif (Repeater) untuk memudahkan koreksi massal.</p>
                            </li>
                            <li>
                                <strong>Konfirmasi (Confirm)</strong>
                                <p>Klik tombol <strong>Konfirmasi (Checklist Hijau)</strong> untuk mengunci jurnal. Jurnal yang sudah dikonfirmasi <strong>TIDAK BISA DIEDIT</strong> lagi, kecuali pembatalan konfirmasi dilakukan oleh Supervisor.</p>
                            </li>
                            <li>
                                <strong>Posting (Post to Ledger)</strong>
                                <p>Langkah terakhir adalah Posting ke Buku Besar. Jurnal yang sudah diposting akan masuk ke laporan keuangan dan tidak bisa diubah/dihapus sama sekali.</p>
                            </li>
                        </ol>
                    </div>
                ',
                'urutan' => 5,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => '6. Jurnal Pembelian Barang',
                'kategori' => 'Transaksi Jurnal',
                'deskripsi' => 'Mencatat pembelian, hutang supplier, dan pengadaan aset',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Fungsi</h3>
                        <p>Digunakan untuk mencatat pembelian (stok/aset) secara kredit maupun tunai yang menimbulkan hutang atau pengeluaran langsung.</p>

                        <h3>Langkah Input:</h3>
                        <ol>
                            <li>Masuk menu <strong>Jurnal > Jurnal Pembelian</strong>.</li>
                            <li>Klik <strong>Buat Jurnal Pembelian Baru</strong>.</li>
                            <li>Isi <strong>Header</strong>: Tanggal, Supplier (Pilih Akun Hutang/Kas jika tunai), dan Keterangan Umum.</li>
                            <li>Isi <strong>Detail Item</strong>:
                                <ul>
                                    <li>Kode Proyek (Opsional)</li>
                                    <li>Akun Debit (Misal: Persediaan Barang, Perlengkapan Kantor)</li>
                                    <li>Jumlah (Rp)</li>
                                </ul>
                            </li>
                            <li>Klik <strong>Tambah Item</strong> untuk memasukkan ke daftar. Ulangi untuk item lain.</li>
                            <li>Klik <strong>Simpan</strong>.</li>
                        </ol>
                        
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-3 border-l-4 border-yellow-400 dark:border-yellow-600">
                            <strong>Tips:</strong> Pastikan memilih akun Kredit (Header) yang benar. Biasanya adalah Akun Hutang Dagang (Kewajiban).
                        </div>
                    </div>
                ',
                'urutan' => 6,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => '7. Jurnal Penerimaan Kas',
                'kategori' => 'Transaksi Jurnal',
                'deskripsi' => 'Mencatat uang masuk ke Kas/Bank',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Fungsi</h3>
                        <p>Mencatat segala bentuk penerimaan uang tunai atau transfer bank. Contoh: Penerimaan Piutang, Penjualan Tunai, Pendapatan Bunga.</p>

                        <h3>Langkah Input:</h3>
                        <ol>
                            <li>Masuk menu <strong>Jurnal > Jurnal Penerimaan Kas</strong>.</li>
                            <li>Klik <strong>Buat Jurnal Penerimaan Kas Baru</strong>.</li>
                            <li><strong>Bagian Debit (Tujuan Uang)</strong>: Pilih Akun Kas atau Bank tempat uang diterima.</li>
                            <li><strong>Bagian Kredit (Sumber Uang)</strong>:
                                <ul>
                                    <li>Pilih Akun Sumber (Misal: Piutang Dagang, Pendapatan Lain-lain).</li>
                                    <li>Masukkan Jumlah dan Keterangan.</li>
                                </ul>
                            </li>
                            <li>Simpan transaksi.</li>
                        </ol>
                    </div>
                ',
                'urutan' => 7,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => '8. Jurnal Pembayaran (Keluar Kas/Bank)',
                'kategori' => 'Transaksi Jurnal',
                'deskripsi' => 'Mencatat pengeluaran uang untuk biaya operasional atau bayar hutang',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Fungsi</h3>
                        <p>Mencatat pengeluaran uang. Contoh: Bayar Listrik, Bayar Gaji, Pelunasan Hutang Supplier.</p>

                        <h3>Langkah Input:</h3>
                        <ol>
                            <li>Masuk menu <strong>Jurnal > Jurnal Bayar Kas/Bank</strong>.</li>
                            <li>Klik <strong>Buat Jurnal Bayar Baru</strong>.</li>
                            <li><strong>Bagian Kredit (Sumber Dana)</strong>: Pilih Akun Kas atau Bank yang digunakan membayar.</li>
                            <li><strong>Bagian Debit (Alokasi Biaya/Hutang)</strong>:
                                <ul>
                                    <li>Pilih Akun Beban (Misal: Biaya Listrik) atau Hutang.</li>
                                    <li>Masukkan Jumlah dan Keterangan.</li>
                                </ul>
                            </li>
                            <li>Simpan transaksi.</li>
                        </ol>
                    </div>
                ',
                'urutan' => 8,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => '9. Jurnal Memorial (Umum)',
                'kategori' => 'Transaksi Jurnal',
                'deskripsi' => 'Jurnal penyesuaian, koreksi, dan penyusutan',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Fungsi</h3>
                        <p>Jurnal serbaguna untuk transaksi non-kas atau penyesuaian akhir bulan. Contoh: Penyusutan Aset, Amortisasi, Koreksi Kesalahan Pencatatan.</p>

                        <h3>Penting:</h3>
                        <p>Berbeda dengan jurnal lain, Jurnal Memorial mengharuskan Anda menginput sisi <strong>DEBIT</strong> dan <strong>KREDIT</strong> secara manual dan jumlahnya harus <strong>BALANCE (Seimbang)</strong> sebelum bisa disimpan.</p>
                    </div>
                ',
                'urutan' => 9,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
             [
                'judul' => '10. Jurnal Pemakaian Bahan & Rekening Air',
                'kategori' => 'Transaksi Jurnal',
                'deskripsi' => 'Modul khusus operasional',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Jurnal Pemakaian Bahan</h3>
                        <p>Digunakan untuk mencatat pengurangan stok gudang untuk dipakai dalam operasional (bukan dijual). Akun kredit otomatis diarahkan ke Persediaan.</p>
                        
                        <h3>Jurnal Rekening Air</h3>
                        <p>Khusus untuk mencatat Piutang Rekening Air dari pelanggan. Biasanya diinput massal atau diimport dari data billing bulanan.</p>
                    </div>
                ',
                'urutan' => 10,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],

            // ==================================================================================
            // BAB 4: IMPORT DATA
            // ==================================================================================
            [
                'judul' => '11. Import Jurnal dari Excel',
                'kategori' => 'Fitur Lanjutan',
                'deskripsi' => 'Cara upload massal data transaksi jurnal',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Langkah Import:</h3>
                        <ol>
                            <li>Buka halaman list jurnal yang diinginkan (misal: Jurnal Memorial).</li>
                            <li>Klik tombol <strong>Download Template</strong> di pojok kanan atas tabel.</li>
                            <li>Isi file Excel sesuai format:
                                <ul>
                                    <li>Jangan ubah judul kolom (Header Excel).</li>
                                    <li>Format tanggal: YYYY-MM-DD (2024-01-31).</li>
                                    <li>Pastikan Kode Akun/Nomor Bantu terdaftar di sistem.</li>
                                </ul>
                            </li>
                            <li>Simpan file.</li>
                            <li>Kembali ke aplikasi, klik tombol <strong>Import Excel</strong>.</li>
                            <li>Upload file yang sudah diisi.</li>
                            <li>Tunggu proses selesai. Sistem akan melaporkan jika ada baris yang gagal.</li>
                        </ol>
                    </div>
                ',
                'urutan' => 11,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],

            // ==================================================================================
            // BAB 5: LAPORAN
            // ==================================================================================
            [
                'judul' => '12. Laporan Keuangan & Buku Besar',
                'kategori' => 'Laporan',
                'deskripsi' => 'Menghasilkan Neraca, Laba Rugi, dan melihat detail akun',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Jenis Laporan Tersedia:</h3>
                        <ul>
                            <li><strong>Neraca (Balance Sheet)</strong>: Posisi harta, hutang, dan modal per tanggal tertentu.</li>
                            <li><strong>Laba Rugi (Profit & Loss)</strong>: Kinerja pendapatan vs beban dalam periode tertentu.</li>
                            <li><strong>Arus Kas</strong>: Aliran masuk keluar kas.</li>
                            <li><strong>Buku Besar (General Ledger)</strong>: Rincian mutasi per akun secara detail.</li>
                        </ul>

                        <h3>Cara Cetak:</h3>
                        <ol>
                            <li>Masuk menu Laporan.</li>
                            <li>Pilih periode (Bulan/Tahun atau Rentang Tanggal).</li>
                            <li>Klik <strong>Preview</strong> untuk melihat di layar.</li>
                            <li>Klik <strong>Export PDF</strong> atau <strong>Excel</strong> untuk mengunduh.</li>
                        </ol>
                    </div>
                ',
                'urutan' => 12,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => '13. Proses Tutup Buku (Closing)',
                'kategori' => 'Laporan',
                'deskripsi' => 'Prosedur akhir bulan/tahun',
                'konten' => '
                    <div class="prose dark:prose-invert max-w-none">
                        <h3>Closing Akhir Bulan</h3>
                        <p>Tujuannya untuk memastikan semua transaksi bulan tersebut sudah diposting dan tidak ada perubahan lagi.</p>
                        
                        <h3>Closing Akhir Tahun</h3>
                        <p>Sistem akan otomatis memindahkan Laba/Rugi Tahun Berjalan ke Laba Ditahan dan mengenolkan akun pendapatan/beban untuk tahun baru.</p>
                    </div>
                ',
                'urutan' => 13,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id ?? 1,
                'published_at' => now(),
            ],
            [
                'judul' => 'Tutorial Alur Penggunaan Sistem Berdasarkan Role',
                'kategori' => 'Governance Role',
                'deskripsi' => 'Panduan lengkap alur kerja dari input sampai posting buku besar dengan pembatasan role terbaru',
                'konten' => '<h2>Tujuan Dokumen</h2>
<p>Dokumen ini menjelaskan alur operasional harian sistem akuntansi dan batasan tindakan setiap role agar proses tetap terkontrol, auditable, dan sesuai prinsip pemisahan tugas.</p>

<h2>Skema Role Resmi</h2>
<ul>
<li><strong>super_admin</strong>: akses penuh, termasuk mengatur role dan permission.</li>
<li><strong>staff</strong> (termasuk varian staff): hanya input draft (create/update), tidak boleh confirm/post.</li>
<li><strong>kepala_sub_bagian</strong> (termasuk varian): input + confirm + unconfirm + post.</li>
<li><strong>kepala_bagian</strong> dan <strong>direktur</strong>: view only (monitoring dan review), tanpa aksi mutasi data.</li>
</ul>

<h2>Alur Umum Proses Jurnal</h2>
<ol>
<li><strong>Input Draft</strong> oleh staff:
<ul>
<li>Isi header transaksi: tanggal, no bukti/referensi, uraian.</li>
<li>Isi detail transaksi sampai lengkap.</li>
<li>Validasi internal form: item lengkap dan balance (untuk jurnal yang mensyaratkan debit=kredit).</li>
</ul>
</li>
<li><strong>Review & Confirm</strong> oleh kepala sub bagian:
<ul>
<li>Cek kelengkapan dokumen sumber.</li>
<li>Cek akun, nomor bantu, kode proyek, dan nilai nominal.</li>
<li>Lakukan confirm bila sudah benar.</li>
</ul>
</li>
<li><strong>Posting Buku Besar</strong> oleh kepala sub bagian:
<ul>
<li>Pastikan jurnal sudah confirmed.</li>
<li>Klik Post ke Buku Besar.</li>
<li>Sistem akan menolak jika tidak punya permission post_jurnal::*.</li>
</ul>
</li>
<li><strong>Monitoring</strong> oleh kepala bagian/direktur:
<ul>
<li>Memantau status pending/confirmed/posted.</li>
<li>Meninjau laporan dan audit trail.</li>
</ul>
</li>
</ol>

<h2>Alur Per Modul Jurnal</h2>
<h3>1. Jurnal Pembelian</h3>
<ul>
<li>Staff input item pembelian dan akun lawan (utang/beban/aset).</li>
<li>Kasub verifikasi bukti, supplier, dan nilai transaksi.</li>
<li>Kasub confirm lalu post ke buku besar.</li>
</ul>

<h3>2. Jurnal Rekening Air</h3>
<ul>
<li>Staff input detail pendapatan/tagihan.</li>
<li>Sistem validasi keseimbangan debit/kredit sebelum finalisasi item.</li>
<li>Kasub confirm dan post.</li>
</ul>

<h3>3. Jurnal Penerimaan Kas</h3>
<ul>
<li>Staff input sumber penerimaan dan kas/bank tujuan.</li>
<li>Kasub memastikan bukti penerimaan valid.</li>
<li>Kasub confirm dan post.</li>
</ul>

<h3>4. Jurnal Bayar Kas/Bank</h3>
<ul>
<li>Staff input detail pembayaran dan akun kas/bank sumber.</li>
<li>Kasub cek otorisasi pengeluaran dan bukti pendukung.</li>
<li>Kasub confirm dan post.</li>
</ul>

<h3>5. Jurnal Memorial</h3>
<ul>
<li>Staff input jurnal penyesuaian/koreksi.</li>
<li>Kasub verifikasi justifikasi akuntansi dan balance.</li>
<li>Kasub confirm dan post.</li>
</ul>

<h3>6. Jurnal Pemakaian Bahan</h3>
<ul>
<li>Staff input pemakaian persediaan bahan operasional.</li>
<li>Kasub cek dasar pemakaian dan akun pembebanan.</li>
<li>Kasub confirm dan post.</li>
</ul>

<h2>Aturan Kontrol</h2>
<ul>
<li>Draft hanya boleh diubah sebelum confirm.</li>
<li>Jurnal posted tidak boleh diubah langsung; gunakan jurnal koreksi jika perlu penyesuaian.</li>
<li>Semua aksi confirm/post tercatat pada audit log.</li>
</ul>

<h2>Checklist Operasional Harian</h2>
<ol>
<li>Staff menyelesaikan seluruh input harian.</li>
<li>Kasub melakukan review batch dan confirm.</li>
<li>Kasub melakukan posting batch ke buku besar.</li>
<li>Kepala bagian/direktur meninjau dashboard dan laporan.</li>
</ol>',
                'urutan' => 15,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
            [
                'judul' => 'Matriks Batasan Role dan Permission (Operasional)',
                'kategori' => 'Governance Role',
                'deskripsi' => 'Matriks detail hak aksi per role untuk seluruh proses jurnal',
                'konten' => '<h2>Matriks Hak Aksi</h2>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">
<thead>
<tr>
<th>Role</th>
<th>Lihat Jurnal</th>
<th>Input/Edit Draft</th>
<th>Confirm/Unconfirm</th>
<th>Post Buku Besar</th>
<th>Kelola Role/Permission</th>
</tr>
</thead>
<tbody>
<tr>
<td>super_admin</td>
<td>Ya</td>
<td>Ya</td>
<td>Ya</td>
<td>Ya</td>
<td>Ya</td>
</tr>
<tr>
<td>staff (semua varian)</td>
<td>Ya</td>
<td>Ya</td>
<td>Tidak</td>
<td>Tidak</td>
<td>Tidak</td>
</tr>
<tr>
<td>kepala_sub_bagian (semua varian)</td>
<td>Ya</td>
<td>Ya</td>
<td>Ya</td>
<td>Ya</td>
<td>Tidak</td>
</tr>
<tr>
<td>kepala_bagian</td>
<td>Ya</td>
<td>Tidak</td>
<td>Tidak</td>
<td>Tidak</td>
<td>Tidak</td>
</tr>
<tr>
<td>direktur_umum / direktur_utama</td>
<td>Ya</td>
<td>Tidak</td>
<td>Tidak</td>
<td>Tidak</td>
<td>Tidak</td>
</tr>
</tbody>
</table>

<h2>Catatan Implementasi Permission</h2>
<ul>
<li>Permission post dipisah per modul: <code>post_jurnal::*</code>.</li>
<li>Confirm/unconfirm dipisah per modul: <code>confirm_jurnal::*</code> dan <code>unconfirm_jurnal::*</code>.</li>
<li>Aksi posting divalidasi di UI dan backend service.</li>
</ul>

<h2>Aturan Perubahan Role</h2>
<ol>
<li>Hanya super_admin yang dapat mengubah role dan permission.</li>
<li>Perubahan role harus disertai catatan alasan.</li>
<li>Lakukan uji akses minimal pada 1 akun uji sebelum dipakai produksi.</li>
</ol>',
                'urutan' => 16,
                'is_published' => true,
                'is_manual_book' => true,
                'created_by' => $admin->id,
                'published_at' => now(),
            ],
        ];

        foreach ($dokumentasiData as $data) {
            Dokumentasi::updateOrCreate(
                ['judul' => $data['judul']], // Key pencarian
                $data // Data update
            );
        }

        $this->command->info('✅ Dokumentasi berhasil di-seed dengan ' . count($dokumentasiData) . ' bab panduan lengkap.');
    }
}
