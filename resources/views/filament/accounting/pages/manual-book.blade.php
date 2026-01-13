<x-filament-panels::page>
    <div class="max-w-7xl mx-auto">
        <div class="prose dark:prose-invert max-w-none">
            
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 dark:from-purple-800 dark:to-pink-900 rounded-xl p-8 mb-8 text-white shadow-xl">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-white/20 rounded-lg">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold mb-2 text-white">📖 Panduan Pengguna</h1>
                        <p class="text-purple-100 text-lg">Langkah-demi-langkah menggunakan sistem akuntansi</p>
                    </div>
                </div>
            </div>

            <!-- Quick Start Guide -->
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6 mb-8 border border-blue-200 dark:border-blue-700">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">🚀</div>
                    <div>
                        <h3 class="font-bold text-xl text-blue-900 dark:text-blue-100 mb-3">Quick Start - Memulai dalam 5 Langkah</h3>
                        <div class="grid md:grid-cols-5 gap-4">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-xl mb-2">1</div>
                                <p class="text-sm text-blue-800 dark:text-blue-200 font-semibold">Setup CoA</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-purple-500 text-white rounded-full flex items-center justify-center font-bold text-xl mb-2">2</div>
                                <p class="text-sm text-purple-800 dark:text-purple-200 font-semibold">Saldo Awal</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-pink-500 text-white rounded-full flex items-center justify-center font-bold text-xl mb-2">3</div>
                                <p class="text-sm text-pink-800 dark:text-pink-200 font-semibold">Input Transaksi</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-orange-500 text-white rounded-full flex items-center justify-center font-bold text-xl mb-2">4</div>
                                <p class="text-sm text-orange-800 dark:text-orange-200 font-semibold">Rekonsiliasi</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-xl mb-2">5</div>
                                <p class="text-sm text-green-800 dark:text-green-200 font-semibold">Laporan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step-by-Step Instructions -->
            <div class="space-y-6">
                
                <!-- Step 1 -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 text-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold text-xl">1</div>
                            <h3 class="text-2xl font-bold">Setup Chart of Accounts (CoA)</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-2xl">📋</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Buat Kelompok Akun</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Buat kelompok akun sesuai SAKEP (10-Aktiva Lancar, 20-Investasi, dll)</p>
                                    <code class="block mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs">Menu: Accounting → Chart of Accounts → Kelompok Akun → Create</code>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-2xl">🔢</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Buat Rekening</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Tambahkan rekening di bawah setiap kelompok (1101-Kas, 1102-Bank, dll)</p>
                                    <code class="block mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs">Menu: Accounting → Chart of Accounts → Rekening → Create</code>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-2xl">🏷️</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Buat Nomor Bantu (Opsional)</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Detail rekening untuk tracking lebih spesifik (1102.01-Bank BPD, dll)</p>
                                    <code class="block mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs">Menu: Accounting → Chart of Accounts → Nomor Bantu → Create</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4 text-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold text-xl">2</div>
                            <h3 class="text-2xl font-bold">Input Saldo Awal</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <span class="text-2xl">💵</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Masukkan Saldo Awal</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Input saldo awal untuk setiap rekening (Kas, Bank, Piutang, dll)</p>
                                    <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded">
                                        <p class="text-xs text-yellow-800 dark:text-yellow-200"><strong>⚠️ Penting:</strong> Total Debit harus = Total Kredit!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-pink-500 to-pink-600 p-4 text-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold text-xl">3</div>
                            <h3 class="text-2xl font-bold">Pencatatan Transaksi Harian</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="flex gap-3 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                <span class="text-2xl">💧</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Jurnal Rekening Air</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Catat penjualan air ke pelanggan</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                <span class="text-2xl">💰</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Jurnal Penerimaan Kas</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Catat penerimaan pembayaran</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                <span class="text-2xl">🛒</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Jurnal Pembelian</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Catat pembelian bahan/supplies</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                <span class="text-2xl">💳</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Jurnal Bayar Kas/Bank</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Catat pembayaran supplier</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                <span class="text-2xl">📦</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Jurnal Pemakaian Bahan</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Catat pemakaian bahan operasional</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
                                <span class="text-2xl">📝</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Jurnal Memorial</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Penyesuaian, depresiasi, koreksi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-4 text-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold text-xl">4</div>
                            <h3 class="text-2xl font-bold">Rekonsiliasi Bank & Penutupan</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <span class="text-2xl">🏦</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Rekonsiliasi Bank</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Cocokkan mutasi bank dengan jurnal</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <span class="text-2xl">📊</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Penyesuaian Akhir Periode</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Catat accrual, deferral, dan depresiasi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 p-4 text-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold text-xl">5</div>
                            <h3 class="text-2xl font-bold">Generate Laporan Keuangan</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="flex gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-2xl">📋</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Neraca</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Balance Sheet</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-2xl">💹</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Laba Rugi</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Income Statement</p>
                                </div>
                            </div>
                            <div class="flex gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-2xl">💵</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100">Arus Kas</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Cash Flow</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Troubleshooting Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mt-8">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">🔧</span>
                    Troubleshooting - Masalah Umum
                </h2>
                
                <div class="space-y-4">
                    <details class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-700">
                        <summary class="font-bold cursor-pointer text-red-900 dark:text-red-100">❌ Jurnal tidak balance (Debit ≠ Kredit)</summary>
                        <div class="mt-3 text-sm text-red-800 dark:text-red-200">
                            <p><strong>Solusi:</strong> Periksa kembali detail jurnal. Pastikan total debit sama dengan total kredit. Gunakan fitur validasi otomatis di form input.</p>
                        </div>
                    </details>

                    <details class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-700">
                        <summary class="font-bold cursor-pointer text-yellow-900 dark:text-yellow-100">⚠️ Rekonsiliasi bank tidak cocok</summary>
                        <div class="mt-3 text-sm text-yellow-800 dark:text-yellow-200">
                            <p><strong>Solusi:</strong> Periksa tanggal transaksi, outstanding checks, dan deposits in transit. Gunakan fitur filter untuk memudahkan pencocokan.</p>
                        </div>
                    </details>

                    <details class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                        <summary class="font-bold cursor-pointer text-blue-900 dark:text-blue-100">ℹ️ Tidak bisa menghapus data</summary>
                        <div class="mt-3 text-sm text-blue-800 dark:text-blue-200">
                            <p><strong>Penjelasan:</strong> Sistem menggunakan soft delete untuk keamanan data. Data tidak benar-benar terhapus, hanya disembunyikan. Admin dapat restore jika diperlukan.</p>
                        </div>
                    </details>

                    <details class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                        <summary class="font-bold cursor-pointer text-green-900 dark:text-green-100">✅ Bagaimana cara export laporan?</summary>
                        <div class="mt-3 text-sm text-green-800 dark:text-green-200">
                            <p><strong>Cara:</strong> Buka menu Laporan, pilih jenis laporan, klik tombol "Export" di pojok kanan atas. Pilih format PDF atau Excel sesuai kebutuhan.</p>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 dark:from-indigo-800 dark:to-purple-900 rounded-xl p-6 text-white mt-8">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">📞</div>
                    <div>
                        <h3 class="font-bold text-xl mb-2">Butuh Bantuan?</h3>
                        <p class="text-indigo-100">Jika mengalami kendala yang tidak tercantum di sini, hubungi administrator sistem atau tim IT support untuk bantuan lebih lanjut.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
