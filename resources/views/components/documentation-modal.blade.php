<x-filament::modal id="documentation" width="6xl">
    <x-slot name="trigger">
        <span class="hidden"></span>
    </x-slot>

    <x-slot name="heading">
        Dokumentasi Sistem
    </x-slot>

    <div class="prose dark:prose-invert max-w-none">
        <h2>Dokumentasi Sistem Akuntansi Air Minum SAKEP</h2>

        <h3>📚 Gambaran Umum</h3>
        <p>Sistem Akuntansi Air Minum berbasis SAKEP (Standar Akuntansi Keuangan Entitas Privat) dirancang khusus untuk PDAM, BUMDes Air, dan perusahaan air minum lainnya.</p>

        <h3>✨ Fitur Utama</h3>
        <ul>
            <li><strong>Pencatatan Transaksi Harian:</strong> Penjualan air, pembelian, gaji, dan transaksi lainnya</li>
            <li><strong>Otomatisasi Pajak:</strong> PPN, PPh, dan e-Faktur terintegrasi</li>
            <li><strong>Rekonsiliasi Bank:</strong> Otomatis mencocokkan mutasi bank dengan jurnal</li>
            <li><strong>Laporan Keuangan:</strong> Neraca, Laba Rugi, Arus Kas sesuai PSAK</li>
            <li><strong>Audit Trail:</strong> Semua aktivitas tercatat dengan lengkap</li>
        </ul>

        <h3>🗂️ Struktur Chart of Accounts (SAKEP)</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <h4>Kelompok Akun</h4>
                <ul>
                    <li>10 - Aktiva Lancar</li>
                    <li>20 - Investasi Jk. Panjang</li>
                    <li>30 - Aktiva Tetap</li>
                    <li>40 - Aktiva Lain-lain</li>
                    <li>50 - Kewajiban Jk. Pendek</li>
                    <li>60 - Kewajiban Jk. Panjang</li>
                    <li>70 - Modal dan Cadangan</li>
                    <li>80 - Pendapatan</li>
                    <li>90 - Biaya</li>
                </ul>
            </div>
            <div>
                <h4>Contoh Rekening</h4>
                <ul>
                    <li>1101 - Kas</li>
                    <li>1102 - Bank</li>
                    <li>1301 - Piutang Rekening Air</li>
                    <li>4101 - Pendapatan Jasa Air Bersih</li>
                    <li>5101 - Beban Air Baku</li>
                    <li>2101 - Utang Supplier</li>
                </ul>
            </div>
        </div>

        <h3>📝 Jenis Jurnal</h3>
        <ol>
            <li><strong>Jurnal Rekening Air:</strong> Pencatatan penjualan air kepada pelanggan</li>
            <li><strong>Jurnal Penerimaan Kas:</strong> Penerimaan pembayaran dari pelanggan</li>
            <li><strong>Jurnal Pembelian:</strong> Pembelian bahan kimia, pipa, dan supplies</li>
            <li><strong>Jurnal Bayar Kas/Bank:</strong> Pembayaran kepada supplier dan biaya</li>
            <li><strong>Jurnal Pemakaian Bahan:</strong> Pemakaian bahan operasional</li>
            <li><strong>Jurnal Memorial:</strong> Penyesuaian, depresiasi, dan koreksi</li>
        </ol>

        <h3>🔐 Keamanan</h3>
        <ul>
            <li>Role-based access control (RBAC) dengan Spatie Permission</li>
            <li>Activity logs untuk semua transaksi</li>
            <li>Soft deletes untuk data recovery</li>
            <li>Email verification dan 2FA (opsional)</li>
            <li>Audit trail lengkap dengan created_by tracking</li>
        </ul>

        <h3>📊 Laporan</h3>
        <p>Sistem menyediakan berbagai laporan keuangan:</p>
        <ul>
            <li>Neraca (Balance Sheet)</li>
            <li>Laba Rugi (Income Statement)</li>
            <li>Arus Kas (Cash Flow Statement)</li>
            <li>Buku Besar (General Ledger)</li>
            <li>Jurnal Umum (General Journal)</li>
            <li>Trial Balance</li>
        </ul>

        <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg mt-6">
            <p class="text-sm"><strong>💡 Tips:</strong> Untuk memulai, silakan buat Chart of Accounts terlebih dahulu, kemudian input saldo awal, dan mulai pencatatan transaksi harian.</p>
        </div>
    </div>
</x-filament::modal>
