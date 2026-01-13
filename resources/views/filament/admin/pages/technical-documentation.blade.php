<x-filament-panels::page>
    <div class="max-w-7xl mx-auto">
        <div class="prose dark:prose-invert max-w-none">
            
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 dark:from-gray-900 dark:to-black rounded-xl p-8 mb-8 text-white shadow-2xl">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-white/10 rounded-lg">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold mb-2 text-white">⚙️ Technical Documentation</h1>
                        <p class="text-gray-300 text-lg">For Developers & System Administrators</p>
                    </div>
                </div>
            </div>

            <!-- Tech Stack -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">🛠️</span>
                    Tech Stack
                </h2>
                
                <div class="grid md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-lg p-6 border border-red-200 dark:border-red-700">
                        <div class="text-4xl mb-3">🐘</div>
                        <h4 class="font-bold text-lg text-red-900 dark:text-red-100 mb-2">PHP</h4>
                        <p class="text-sm text-red-700 dark:text-red-300">Version 8.1+</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">Backend Language</p>
                    </div>

                    <div class="bg-gradient-to-br from-red-600/10 to-pink-600/10 dark:from-red-900/20 dark:to-pink-900/20 rounded-lg p-6 border border-red-300 dark:border-red-700">
                        <div class="text-4xl mb-3">🎯</div>
                        <h4 class="font-bold text-lg text-red-900 dark:text-red-100 mb-2">Laravel</h4>
                        <p class="text-sm text-red-700 dark:text-red-300">Version 11.x</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">PHP Framework</p>
                    </div>

                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-lg p-6 border border-amber-200 dark:border-amber-700">
                        <div class="text-4xl mb-3">💡</div>
                        <h4 class="font-bold text-lg text-amber-900 dark:text-amber-100 mb-2">Filament PHP</h4>
                        <p class="text-sm text-amber-700 dark:text-amber-300">Version 3.x</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Admin Panel</p>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                        <div class="text-4xl mb-3">🐘</div>
                        <h4 class="font-bold text-lg text-blue-900 dark:text-blue-100 mb-2">PostgreSQL</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300">Version 14+</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Database</p>
                    </div>
                </div>
            </div>

            <!-- Architecture Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">🏗️</span>
                    Architecture Overview
                </h2>
                
                <div class="bg-gradient-to-r from-slate-50 to-gray-100 dark:from-slate-900 dark:to-gray-800 rounded-lg p-6 border border-gray-300 dark:border-gray-600">
                    <pre class="text-sm overflow-x-auto"><code class="language-plaintext">┌─────────────────────────────────────────────────┐
│  Frontend Layer (Livewire + Alpine.js)        │
├─────────────────────────────────────────────────┤
│  Filament PHP 3.x (Admin Panel Framework)     │
├─────────────────────────────────────────────────┤
│  Laravel 11 Application Layer                  │
│  ├─ Controllers (API & Web Routes)             │
│  ├─ Services (Business Logic)                  │
│  ├─ Models (Eloquent ORM)                      │
│  └─ Observers (Event Listeners)                │
├─────────────────────────────────────────────────┤
│  Middleware & Security                          │
│  ├─ Spatie Permission (RBAC)                   │
│  ├─ Shield (Role Management)                   │
│  └─ Activity Log (Audit Trail)                 │
├─────────────────────────────────────────────────┤
│  PostgreSQL 14+ Database                        │
└─────────────────────────────────────────────────┘</code></pre>
                </div>
            </div>

            <!-- Database Structure -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">🗄️</span>
                    Database Structure
                </h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700">
                        <h4 class="font-bold text-lg mb-4 text-purple-900 dark:text-purple-100">📊 Core Tables</h4>
                        <div class="space-y-2 font-mono text-sm">
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">kelompok_akun</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">rekening</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">nomor_bantu</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">journals</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">journal_details</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
                        <h4 class="font-bold text-lg mb-4 text-green-900 dark:text-green-100">🔐 Auth & Logs</h4>
                        <div class="space-y-2 font-mono text-sm">
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">users</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">roles</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">permissions</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">activity_log</div>
                            <div class="p-2 bg-white/60 dark:bg-gray-800/60 rounded">authentication_log</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-gray-800 dark:bg-black rounded-lg p-6 border border-gray-600">
                    <h4 class="font-bold text-lg mb-4 text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                        Schema Example
                    </h4>
                    <pre class="text-xs text-green-400 overflow-x-auto"><code class="language-sql">CREATE TABLE journals (
    id BIGSERIAL PRIMARY KEY,
    tanggal DATE NOT NULL,
    nomor_jurnal VARCHAR(50) UNIQUE NOT NULL,
    keterangan TEXT,
    jenis_jurnal VARCHAR(50),
    total_debit DECIMAL(15,2),
    total_kredit DECIMAL(15,2),
    is_balanced BOOLEAN DEFAULT false,
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);</code></pre>
                </div>
            </div>

            <!-- API Documentation -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">🔌</span>
                    Key Services & Components
                </h2>
                
                <div class="space-y-4">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-300 dark:border-blue-600">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">📝</span>
                            <div>
                                <h4 class="font-bold text-blue-900 dark:text-blue-100">JournalService</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Business logic untuk pencatatan jurnal, validasi balance, dan otomasi posting</p>
                                <code class="block mt-2 p-2 bg-blue-900 text-blue-100 rounded text-xs">app/Services/JournalService.php</code>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-300 dark:border-purple-600">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">👁️</span>
                            <div>
                                <h4 class="font-bold text-purple-900 dark:text-purple-100">ActivityLogObserver</h4>
                                <p class="text-sm text-purple-700 dark:text-purple-300 mt-1">Automatically log semua aktivitas CRUD pada models</p>
                                <code class="block mt-2 p-2 bg-purple-900 text-purple-100 rounded text-xs">app/Observers/ActivityLogObserver.php</code>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-300 dark:border-green-600">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">🔒</span>
                            <div>
                                <h4 class="font-bold text-green-900 dark:text-green-100">Shield RBAC</h4>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">Role-based access control dengan Spatie Permission integration</p>
                                <code class="block mt-2 p-2 bg-green-900 text-green-100 rounded text-xs">Middleware: can:view_journal, can:edit_journal</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Features -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
                <h2 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <span class="text-4xl">🛡️</span>
                    Security & Compliance
                </h2>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="flex gap-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700">
                        <span class="text-2xl">🔐</span>
                        <div>
                            <h4 class="font-bold text-red-900 dark:text-red-100">Authentication</h4>
                            <p class="text-sm text-red-700 dark:text-red-300">Laravel Sanctum + Email Verification</p>
                        </div>
                    </div>

                    <div class="flex gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-700">
                        <span class="text-2xl">👥</span>
                        <div>
                            <h4 class="font-bold text-orange-900 dark:text-orange-100">Authorization</h4>
                            <p class="text-sm text-orange-700 dark:text-orange-300">Spatie Permission (RBAC)</p>
                        </div>
                    </div>

                    <div class="flex gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                        <span class="text-2xl">📜</span>
                        <div>
                            <h4 class="font-bold text-yellow-900 dark:text-yellow-100">Audit Trail</h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Spatie Activity Log (all actions tracked)</p>
                        </div>
                    </div>

                    <div class="flex gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                        <span class="text-2xl">♻️</span>
                        <div>
                            <h4 class="font-bold text-green-900 dark:text-green-100">Data Recovery</h4>
                            <p class="text-sm text-green-700 dark:text-green-300">Soft Deletes (restore capability)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Development Commands -->
            <div class="bg-gradient-to-r from-gray-800 to-black rounded-xl p-8 mb-6 text-white">
                <h2 class="text-3xl font-bold mb-6 flex items-center gap-3">
                    <span class="text-4xl">⚡</span>
                    Development Commands
                </h2>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Start Development Server</p>
                        <code class="block p-3 bg-black/50 rounded font-mono text-sm text-green-400">php artisan serve</code>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Run Migrations</p>
                        <code class="block p-3 bg-black/50 rounded font-mono text-sm text-green-400">php artisan migrate --seed</code>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Clear Cache</p>
                        <code class="block p-3 bg-black/50 rounded font-mono text-sm text-green-400">php artisan optimize:clear</code>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Generate Permissions</p>
                        <code class="block p-3 bg-black/50 rounded font-mono text-sm text-green-400">php artisan shield:generate</code>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 dark:from-indigo-800 dark:to-purple-900 rounded-xl p-6 text-white">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">💬</div>
                    <div>
                        <h3 class="font-bold text-xl mb-2">Need Technical Support?</h3>
                        <p class="text-indigo-100">Contact the development team for API documentation, deployment assistance, or technical troubleshooting.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
