<div class="max-w-2xl mx-auto">
    <!-- Revenue Section -->
    <div class="mb-8">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">PENDAPATAN</h4>
        <div class="space-y-2">
            @forelse($data['revenues'] ?? [] as $revenue)
            <div class="flex justify-between items-center py-2">
                <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $revenue['account_name'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $revenue['sakep_code'] }}</span>
                </div>
                <span class="text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($revenue['amount'], 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="text-sm text-gray-500 dark:text-gray-400 py-2">Tidak ada pendapatan tercatat untuk periode ini</div>
            @endforelse
        </div>
        <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center font-semibold">
                <span class="text-gray-900 dark:text-gray-100">Total Pendapatan</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_revenues'] ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Expenses Section -->
    <div class="mb-8">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">BEBAN</h4>
        <div class="space-y-2">
            @forelse($data['expenses'] ?? [] as $expense)
            <div class="flex justify-between items-center py-2">
                <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $expense['account_name'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $expense['sakep_code'] }}</span>
                </div>
                <span class="text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($expense['amount'], 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="text-sm text-gray-500 dark:text-gray-400 py-2">Tidak ada beban tercatat untuk periode ini</div>
            @endforelse
        </div>
        <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center font-semibold">
                <span class="text-gray-900 dark:text-gray-100">Total Beban</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_expenses'] ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Net Income -->
    <div class="mt-6 p-4 {{ ($data['net_income'] ?? 0) >= 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }} rounded-lg">
        <div class="flex justify-between items-center">
            <span class="text-lg font-bold {{ ($data['net_income'] ?? 0) >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">LABA BERSIH</span>
            <span class="text-lg font-bold {{ ($data['net_income'] ?? 0) >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                Rp {{ number_format($data['net_income'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        <div class="mt-2 text-sm {{ ($data['net_income'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
            {{ ($data['net_income'] ?? 0) >= 0 ? 'Laba' : 'Rugi' }} untuk periode ini
            @if(($data['net_income'] ?? 0) == 0)
                Impas untuk periode ini
            @endif
        </div>
    </div>

    <!-- Calculation Summary -->
    <div class="mt-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Ringkasan Perhitungan</h5>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Total Pendapatan:</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_revenues'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Total Beban:</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_expenses'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                <div class="flex justify-between font-semibold">
                    <span class="text-gray-900 dark:text-gray-100">Laba Bersih:</span>
                    <span class="{{ ($data['net_income'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        Rp {{ number_format($data['net_income'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    @if(($data['total_revenues'] ?? 0) > 0)
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @if(isset($data['gross_profit']))
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="text-sm font-medium text-blue-900 dark:text-blue-200">Laba Kotor</div>
            <div class="text-lg font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($data['gross_profit'], 0, ',', '.') }}</div>
        </div>
        @endif

        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
            <div class="text-sm font-medium text-purple-900 dark:text-purple-200">Margin Keuntungan</div>
            <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ number_format((($data['net_income'] ?? 0) / ($data['total_revenues'] ?? 1)) * 100, 1) }}%</div>
        </div>
    </div>
    @endif
</div>
