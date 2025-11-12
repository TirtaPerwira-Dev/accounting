<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Assets -->
    <div>
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">ASET</h4>
        <div class="space-y-2">
            @forelse($data['assets'] as $asset)
            <div class="flex justify-between items-center py-2">
                <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $asset['account_name'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $asset['sakep_code'] }}</span>
                </div>
                <span class="text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($asset['balance'], 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="text-sm text-gray-500 dark:text-gray-400 py-2">Tidak ada aset tercatat untuk periode ini</div>
            @endforelse
        </div>
        <div class="mt-4 pt-4 border-t border-gray-300 dark:border-gray-600">
            <div class="flex justify-between items-center font-bold">
                <span class="text-gray-900 dark:text-gray-100">TOTAL ASET</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_assets'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Liabilities & Equity -->
    <div>
        <!-- Liabilities -->
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">KEWAJIBAN</h4>
        <div class="space-y-2">
            @forelse($data['liabilities'] as $liability)
            <div class="flex justify-between items-center py-2">
                <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $liability['account_name'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $liability['sakep_code'] }}</span>
                </div>
                <span class="text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($liability['balance'], 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="text-sm text-gray-500 dark:text-gray-400 py-2">Tidak ada kewajiban tercatat untuk periode ini</div>
            @endforelse
        </div>
        <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center font-semibold">
                <span class="text-gray-900 dark:text-gray-100">Total Kewajiban</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_liabilities'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Equity -->
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 mt-6 pb-2 border-b border-gray-200 dark:border-gray-700">EKUITAS</h4>
        <div class="space-y-2">
            @forelse($data['equity'] as $equity)
            <div class="flex justify-between items-center py-2">
                <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $equity['account_name'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $equity['sakep_code'] }}</span>
                </div>
                <span class="text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($equity['balance'], 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="text-sm text-gray-500 dark:text-gray-400 py-2">Tidak ada ekuitas tercatat untuk periode ini</div>
            @endforelse
        </div>
        <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center font-semibold">
                <span class="text-gray-900 dark:text-gray-100">Total Ekuitas</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_equity'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Total Liabilities & Equity -->
        <div class="mt-4 pt-4 border-t border-gray-300 dark:border-gray-600">
            <div class="flex justify-between items-center font-bold">
                <span class="text-gray-900 dark:text-gray-100">TOTAL KEWAJIBAN & EKUITAS</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_liabilities_equity'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Balance Check -->
<div class="mt-6 p-4 {{ $data['is_balanced'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }} rounded-lg">
    <div class="flex items-center">
        @if($data['is_balanced'])
            <svg class="w-5 h-5 text-green-400 dark:text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-green-800 dark:text-green-200 font-medium">Neraca Seimbang</span>
            <span class="text-green-600 dark:text-green-400 ml-2">(Aset = Kewajiban + Ekuitas)</span>
        @else
            <svg class="w-5 h-5 text-red-400 dark:text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-800 dark:text-red-200 font-medium">Neraca TIDAK Seimbang</span>
            <span class="text-red-600 dark:text-red-400 ml-2">(Selisih: Rp {{ number_format(abs($data['total_assets'] - $data['total_liabilities_equity']), 0, ',', '.') }})</span>
        @endif
    </div>
</div>
