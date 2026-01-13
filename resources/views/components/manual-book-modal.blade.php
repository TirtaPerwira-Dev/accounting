<x-filament::modal id="manual-book" width="6xl">
    <x-slot name="trigger">
        <span class="hidden"></span>
    </x-slot>

    <x-slot name="heading">
        Manual Book - Panduan Pengguna
    </x-slot>

    <div class="prose dark:prose-invert max-w-none">
        <h2>📖 Manual Book Sistem Akuntansi</h2>

        <h3>1. Login & Akses</h3>
        <ol>
            <li>Buka browser dan akses sistem</li>
            <li>Login menggunakan email dan password yang telah terdaftar</li>
            <li>Jika belum punya akun, klik "Buat Akun Baru" dan tunggu verifikasi dari Super Admin</li>
            <li>Setelah login, Anda akan melihat dashboard sesuai role Anda</li>
        </ol>

        <h3>2. Transaksi Penjualan Air</h3>
        <h4>Langkah-langkah:</h4>
        <ol>
            <li>Buka menu <strong>Jurnal Rekening Air</strong></li>
            <li>Klik tombol <strong>Buat Jurnal Baru</strong></li>
            <li>Isi data:
                <ul>
                    <li><strong>Tanggal:</strong> Tanggal transaksi</li>
                    <li><strong>No. Referensi:</strong> Nomor bukti/invoice</li>
                    <li><strong>Deskripsi:</strong> Keterangan transaksi</li>
                    <li><strong>Detail Jurnal:</strong> Pilih akun dan masukkan nominal</li>
                </ul>
            </li>
            <li>Pastikan <strong>Total Debit = Total Kredit</strong></li>
            <li>Klik <strong>Simpan</strong></li>
        </ol>

        <div class="bg-yellow-50 dark:bg-yellow-950 p-4 rounded-lg my-4">
            <p class="text-sm"><strong>⚠️ Penting:</strong> Sistem akan otomatis menghitung PPN 11% jika akun terpilih adalah akun yang kena pajak.</p>
        </div>

        <h3>3. Penerimaan Kas</h3>
        <ol>
            <li>Buka menu <strong>Jurnal Penerimaan Kas</strong></li>
            <li>Klik <strong>Buat Jurnal Baru</strong></li>
            <li>Pilih <strong>Akun Kas/Bank</strong> (Debit)</li>
            <li>Pilih <strong>Piutang Usaha</strong> atau <strong>Pendapatan</strong> (Kredit)</li>
            <li>Pastikan balance dan simpan</li>
        </ol>

        <h3>4. Pembelian</h3>
        <ol>
            <li>Buka menu <strong>Jurnal Pembelian</strong></li>
            <li>Klik <strong>Buat Jurnal Baru</strong></li>
            <li>Debit: <strong>Beban/Persediaan</strong></li>
            <li>Kredit: <strong>Kas/Bank</strong> atau <strong>Utang Usaha</strong></li>
            <li>Jika ada PPN Masukan, tambahkan akun PPN Masukan (Debit)</li>
            <li>Simpan transaksi</li>
        </ol>

        <h3>5. Pemakaian Bahan</h3>
        <ol>
            <li>Buka menu <strong>Jurnal Pemakaian Bahan</strong></li>
            <li>Debit: <strong>Biaya Operasional</strong> (misal: Biaya Kaporit)</li>
            <li>Kredit: <strong>Persediaan Bahan</strong></li>
            <li>Simpan</li>
        </ol>

        <h3>6. Pembayaran (Bayar Kas/Bank)</h3>
        <ol>
            <li>Buka menu <strong>Jurnal Bayar Kas/Bank</strong></li>
            <li>Debit: <strong>Utang</strong> atau <strong>Beban</strong></li>
            <li>Kredit: <strong>Kas/Bank</strong></li>
            <li>Jika ada pemotongan PPh 23, tambahkan akun PPh 23 (Kredit)</li>
            <li>Simpan</li>
        </ol>

        <h3>7. Jurnal Memorial (Penyesuaian)</h3>
        <ol>
            <li>Buka menu <strong>Jurnal Memorial</strong></li>
            <li>Gunakan untuk:
                <ul>
                    <li>Penyusutan aset tetap</li>
                    <li>Akrual beban/pendapatan</li>
                    <li>Koreksi kesalahan</li>
                    <li>Jurnal penutup akhir periode</li>
                </ul>
            </li>
            <li>Isi detail dan pastikan balance</li>
            <li>Simpan</li>
        </ol>

        <h3>8. Melihat Laporan</h3>
        <ol>
            <li>Buka menu <strong>Laporan</strong></li>
            <li>Pilih jenis laporan yang diinginkan:
                <ul>
                    <li><strong>Neraca:</strong> Posisi keuangan per tanggal tertentu</li>
                    <li><strong>Laba Rugi:</strong> Kinerja keuangan periode tertentu</li>
                    <li><strong>Arus Kas:</strong> Aliran kas masuk dan keluar</li>
                    <li><strong>Buku Besar:</strong> Rincian per akun</li>
                </ul>
            </li>
            <li>Atur filter tanggal dan parameter lainnya</li>
            <li>Klik <strong>Export</strong> untuk download PDF/Excel</li>
        </ol>

        <h3>9. Tips & Trik</h3>
        <ul>
            <li>Selalu cek <strong>Activity Log</strong> untuk tracking perubahan</li>
            <li>Gunakan fitur <strong>Filter</strong> untuk mencari data lebih cepat</li>
            <li>Backup data secara berkala</li>
            <li>Pastikan balance sebelum posting jurnal</li>
            <li>Gunakan <strong>Soft Delete</strong> untuk hapus data (data tetap tersimpan)</li>
        </ul>

        <h3>10. Troubleshooting</h3>
        <div class="bg-red-50 dark:bg-red-950 p-4 rounded-lg">
            <h4>Jurnal tidak balance?</h4>
            <p class="text-sm">Pastikan total debit = total kredit. Sistem akan menampilkan error jika tidak balance.</p>

            <h4>Tidak bisa hapus jurnal?</h4>
            <p class="text-sm">Periksa permission Anda. Hanya user dengan role tertentu yang bisa menghapus.</p>

            <h4>Laporan tidak muncul data?</h4>
            <p class="text-sm">Periksa filter tanggal dan pastikan ada transaksi pada periode tersebut.</p>
        </div>

        <div class="bg-green-50 dark:bg-green-950 p-4 rounded-lg mt-6">
            <p class="text-sm"><strong>✅ Bantuan Lebih Lanjut:</strong> Jika ada pertanyaan, hubungi Super Admin atau tim IT Support.</p>
        </div>
    </div>
</x-filament::modal>
