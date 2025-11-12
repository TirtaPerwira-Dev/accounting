<div class="space-y-6 bg-gray-50 dark:bg-gray-900 min-h-screen p-6">
    <!-- Account Header -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            @if($data['account'])
                @if($data['account_type'] === 'nomor_bantu')
                    {{ sprintf('%02d%04d%02d', $data['account']->rekening->kelompok->no_kel, $data['account']->rekening->no_rek, $data['account']->no_bantu) }} - {{ $data['account']->nm_bantu }}
                @elseif($data['account_type'] === 'rekening')
                    {{ sprintf('%02d%04d', $data['account']->kelompok->no_kel, $data['account']->no_rek) }} - {{ $data['account']->nama_rek }}
                @else
                    {{ sprintf('%02d', $data['account']->no_kel) }} - {{ $data['account']->nama_kel }}
                @endif
            @else
                Akun Tidak Ditemukan
            @endif
        </h4>
        <div class="mt-2 flex items-center space-x-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                {{ ucfirst($data['account_type'] ?? 'unknown') }}
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-400">
                Period: {{ \Carbon\Carbon::parse($data['period_start'] ?? now())->format('d M Y') }} -
                {{ \Carbon\Carbon::parse($data['period_end'] ?? now())->format('d M Y') }}
            </span>
        </div>
        <div class="mt-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Saldo Awal: </span>
            <span class="text-sm {{ ($data['opening_balance'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                Rp {{ number_format(abs($data['opening_balance'] ?? 0), 0, ',', '.') }}
                {{ ($data['opening_balance'] ?? 0) >= 0 ? '(Debit)' : '(Kredit)' }}
            </span>
        </div>
    </div>

    <!-- Transactions Table -->
<div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <table class="min-w-full">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Tanggal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Referensi</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Keterangan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Akun</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Debit</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Kredit</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Saldo</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
            @forelse($data['transactions'] ?? [] as $transaction)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $transaction['reference'] ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                        {{ $transaction['description'] ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                        <div>{{ $data['account']->nm_bantu ?? $data['account']->nama_rek ?? $data['account']->nama_kel ?? '-' }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            @if($data['account_type'] === 'nomor_bantu')
                                {{ sprintf('%02d%04d%02d', $data['account']->rekening->kelompok->no_kel, $data['account']->rekening->no_rek, $data['account']->no_bantu) }}
                            @elseif($data['account_type'] === 'rekening')
                                {{ sprintf('%02d%04d', $data['account']->kelompok->no_kel, $data['account']->no_rek) }}
                            @else
                                {{ sprintf('%02d', $data['account']->no_kel) }}
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ isset($transaction['debit']) && $transaction['debit'] > 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ isset($transaction['debit']) && $transaction['debit'] > 0 ? 'Rp ' . number_format($transaction['debit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ isset($transaction['credit']) && $transaction['credit'] > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ isset($transaction['credit']) && $transaction['credit'] > 0 ? 'Rp ' . number_format($transaction['credit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $transaction['balance'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        Rp {{ number_format(abs($transaction['balance']), 0, ',', '.') }}
                        <small class="text-xs block">{{ $transaction['balance'] >= 0 ? '(Dr)' : '(Cr)' }}</small>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium">Tidak ada transaksi</p>
                            <p class="text-sm">Belum ada transaksi untuk akun ini pada periode yang dipilih</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="bg-gray-100 dark:bg-gray-700 border-t-2 border-gray-200 dark:border-gray-600">
            <tr>
                <td colspan="4" class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">TOTAL</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600 dark:text-green-400">
                    Rp {{ number_format($data['total_debit'] ?? 0, 0, ',', '.') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-600 dark:text-red-400">
                    Rp {{ number_format($data['total_credit'] ?? 0, 0, ',', '.') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold {{ ($data['ending_balance'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    Rp {{ number_format($data['ending_balance'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Ledger Summary -->
<div class="mt-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Ringkasan Buku Besar</h5>
    <div class="flex flex-wrap gap-6 justify-between text-sm">
        <div class="flex items-center space-x-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg flex-1 min-w-[200px]">
            <div class="shrink-0 w-12 h-12 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400 font-medium">Total Entri</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format(count($data['transactions'] ?? [])) }}</div>
            </div>
        </div>

        <div class="flex items-center space-x-4 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg flex-1 min-w-[200px]">
            <div class="shrink-0 w-12 h-12 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400 font-medium">Total Debit</div>
                <div class="text-xl font-bold text-green-600 dark:text-green-400">Rp {{ number_format($data['total_debit'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="flex items-center space-x-4 p-4 bg-red-50 dark:bg-red-900/30 rounded-lg flex-1 min-w-[200px]">
            <div class="shrink-0 w-12 h-12 bg-red-100 dark:bg-red-800 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/>
                </svg>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400 font-medium">Total Kredit</div>
                <div class="text-xl font-bold text-red-600 dark:text-red-400">Rp {{ number_format($data['total_credit'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="flex items-center space-x-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex-1 min-w-[200px]">
            <div class="shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-800 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400 font-medium">Saldo Akhir</div>
                <div class="text-xl font-bold {{ ($data['ending_balance'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    Rp {{ number_format(abs($data['ending_balance'] ?? 0), 0, ',', '.') }}
                    <small class="text-xs block">{{ ($data['ending_balance'] ?? 0) >= 0 ? '(Debit)' : '(Kredit)' }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Balance Verification -->
@if(($data['total_debit'] ?? 0) != ($data['total_credit'] ?? 0))
<div class="mt-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 dark:border-red-600 p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="shrink-0">
                <div class="w-8 h-8 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">Peringatan: Jurnal Tidak Seimbang</h3>
                <p class="text-sm text-red-600 dark:text-red-400">Debit dan Kredit harus sama dalam jurnal yang benar</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm text-red-600 dark:text-red-400 font-medium">Selisih:</div>
            <div class="text-lg font-bold text-red-700 dark:text-red-300">Rp {{ number_format(abs(($data['total_debit'] ?? 0) - ($data['total_credit'] ?? 0)), 0, ',', '.') }}</div>
        </div>
    </div>
</div>
@endif
</div>
