<x-filament-panels::page>
    <div class="max-w-7xl mx-auto">
        <div class="prose dark:prose-invert max-w-none">

            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-700 dark:from-blue-800 dark:to-blue-900 rounded-xl p-8 mb-8 text-white shadow-xl">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-white/20 rounded-lg">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold mb-2 text-white">📚 Dokumentasi Sistem</h1>
                        <p class="text-blue-100 text-lg">Sistem Akuntansi Air Minum Berbasis SAKEP</p>
                    </div>
                </div>
            </div>

            <!-- Quick Info Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-green-50 dark:bg-green-950 rounded-lg p-6 border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-green-500 text-white rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg text-green-900 dark:text-green-100">Framework</h3>
                    </div>
                    <p class="text-green-800 dark:text-green-200">Laravel 11 + Filament PHP 3</p>
                </div>

                <div class="bg-purple-50 dark:bg-purple-950 rounded-lg p-6 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-purple-500 text-white rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg text-purple-900 dark:text-purple-100">Database</h3>
                    </div>
                    <p class="text-purple-800 dark:text-purple-200">PostgreSQL 14+</p>
                </div>

                <div class="bg-amber-50 dark:bg-amber-950 rounded-lg p-6 border border-amber-200 dark:border-amber-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-amber-500 text-white rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg text-amber-900 dark:text-amber-100">Security</h3>
                    </div>
                    <p class="text-amber-800 dark:text-amber-200">RBAC + Activity Logs</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">✨</span>
                    Fitur Utama
                </h2>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="flex gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <span class="text-2xl">📝</span>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">Pencatatan Transaksi Harian</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Penjualan air, pembelian, gaji, dan transaksi lainnya</p>
                        </div>
                    </div>

                    <div class="flex gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <span class="text-2xl">💰</span>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">Otomatisasi Pajak</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">PPN, PPh, dan e-Faktur terintegrasi</p>
                        </div>
                    </div>

                    <div class="flex gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <span class="text-2xl">🏦</span>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">Rekonsiliasi Bank</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Otomatis mencocokkan mutasi bank dengan jurnal</p>
                        </div>
                    </div>

                    <div class="flex gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <span class="text-2xl">📊</span>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">Laporan Keuangan</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Neraca, Laba Rugi, Arus Kas sesuai PSAK</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart of Accounts Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">🗂️</span>
                    Struktur Chart of Accounts (SAKEP)
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                        <h4 class="font-bold text-lg mb-4 text-blue-900 dark:text-blue-100">📋 Kelompok Akun</h4>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">10</span>
                                <span class="text-gray-700 dark:text-gray-300">Aktiva Lancar</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">20</span>
                                <span class="text-gray-700 dark:text-gray-300">Investasi Jk. Panjang</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">30</span>
                                <span class="text-gray-700 dark:text-gray-300">Aktiva Tetap</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">50</span>
                                <span class="text-gray-700 dark:text-gray-300">Kewajiban Jk. Pendek</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">70</span>
                                <span class="text-gray-700 dark:text-gray-300">Modal dan Cadangan</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">80</span>
                                <span class="text-gray-700 dark:text-gray-300">Pendapatan</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">90</span>
                                <span class="text-gray-700 dark:text-gray-300">Biaya</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
                        <h4 class="font-bold text-lg mb-4 text-green-900 dark:text-green-100">🔢 Contoh Rekening</h4>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-green-600 dark:text-green-400">1101</span>
                                <span class="text-gray-700 dark:text-gray-300">Kas</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-green-600 dark:text-green-400">1102</span>
                                <span class="text-gray-700 dark:text-gray-300">Bank</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-green-600 dark:text-green-400">1301</span>
                                <span class="text-gray-700 dark:text-gray-300">Piutang Rekening Air</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-green-600 dark:text-green-400">8101</span>
                                <span class="text-gray-700 dark:text-gray-300">Pendapatan Jasa Air Bersih</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-green-600 dark:text-green-400">9101</span>
                                <span class="text-gray-700 dark:text-gray-300">Beban Air Baku</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-white/50 dark:bg-gray-800/50 rounded">
                                <span class="font-mono font-bold text-green-600 dark:text-green-400">5001</span>
                                <span class="text-gray-700 dark:text-gray-300">Utang Supplier</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl p-6 border border-yellow-300 dark:border-yellow-700">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">💡</div>
                    <div>
                        <h3 class="font-bold text-lg text-yellow-900 dark:text-yellow-100 mb-2">Tips Memulai</h3>
                        <p class="text-yellow-800 dark:text-yellow-200">Untuk memulai, silakan buat <strong>Chart of Accounts</strong> terlebih dahulu, kemudian input saldo awal, dan mulai pencatatan transaksi harian. Pastikan untuk memahami struktur SAKEP sebelum mulai input data.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
