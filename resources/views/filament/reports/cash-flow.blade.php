<!-- Operating Activities -->
<div class="mb-8">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">ARUS KAS DARI AKTIVITAS OPERASIONAL</h4>
    <div class="space-y-2">
        @forelse($data['operating_activities'] ?? [] as $item)
        <div class="flex justify-between items-center py-2">
            <div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['description'] ?? $item['name'] ?? '-' }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $item['code'] ?? '' }}</span>
            </div>
            <span class="text-sm {{ ($item['amount'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($item['amount'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        @empty
        <div class="text-sm text-gray-500 dark:text-gray-400 py-2">Tidak ada aktivitas operasional tercatat</div>
        @endforelse
    </div>
    <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center font-semibold">
            <span class="text-gray-900 dark:text-gray-100">Kas Bersih dari Aktivitas Operasional</span>
            <span class="{{ ($data['net_operating_cash_flow'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($data['net_operating_cash_flow'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($data['net_operating_cash_flow'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>

<!-- Investing Activities -->
<div class="mb-8">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">ARUS KAS DARI AKTIVITAS INVESTASI</h4>
    <div class="space-y-2">
        @forelse($data['investing_activities'] ?? [] as $item)
        <div class="flex justify-between items-center py-2">
            <div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['description'] ?? $item['name'] ?? '-' }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $item['code'] ?? '' }}</span>
            </div>
            <span class="text-sm {{ ($item['amount'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($item['amount'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        @empty
        <div class="text-sm text-gray-500 dark:text-gray-400 italic py-2">Tidak ada aktivitas investasi untuk periode ini</div>
        @endforelse
    </div>
    <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center font-semibold">
            <span class="text-gray-900 dark:text-gray-100">Kas Bersih dari Aktivitas Investasi</span>
            <span class="{{ ($data['net_investing_cash_flow'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($data['net_investing_cash_flow'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($data['net_investing_cash_flow'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>

<!-- Financing Activities -->
<div class="mb-8">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">ARUS KAS DARI AKTIVITAS PENDANAAN</h4>
    <div class="space-y-2">
        @forelse($data['financing_activities'] ?? [] as $item)
        <div class="flex justify-between items-center py-2">
            <div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['description'] ?? $item['name'] ?? '-' }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $item['code'] ?? '' }}</span>
            </div>
            <span class="text-sm {{ ($item['amount'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($item['amount'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        @empty
        <div class="text-sm text-gray-500 dark:text-gray-400 italic py-2">Tidak ada aktivitas pendanaan untuk periode ini</div>
        @endforelse
    </div>
    <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center font-semibold">
            <span class="text-gray-900 dark:text-gray-100">Kas Bersih dari Aktivitas Pendanaan</span>
            <span class="{{ ($data['net_financing_cash_flow'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($data['net_financing_cash_flow'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($data['net_financing_cash_flow'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>

<!-- Net Change in Cash -->
<div class="mt-6 p-4 {{ ($data['net_change_in_cash'] ?? 0) >= 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }} rounded-lg">
    <div class="flex justify-between items-center">
        <span class="text-lg font-bold {{ ($data['net_change_in_cash'] ?? 0) >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">PERUBAHAN BERSIH KAS</span>
        <span class="text-lg font-bold {{ ($data['net_change_in_cash'] ?? 0) >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
            {{ ($data['net_change_in_cash'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($data['net_change_in_cash'] ?? 0, 0, ',', '.') }}
        </span>
    </div>
</div>

<!-- Cash Summary -->
<div class="mt-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
    <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Ringkasan Kas</h5>
    <div class="space-y-2 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Saldo Kas Awal:</span>
            <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['beginning_cash_balance'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Perubahan Bersih Kas:</span>
            <span class="{{ ($data['net_change_in_cash'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($data['net_change_in_cash'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($data['net_change_in_cash'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
            <div class="flex justify-between font-semibold">
                <span class="text-gray-900 dark:text-gray-100">Saldo Kas Akhir:</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['ending_cash_balance'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                <span>Saldo Kas Terhitung:</span>
                <span>Rp {{ number_format($data['calculated_ending_cash'] ?? 0, 0, ',', '.') }}</span>
            </div>
            @if(abs(($data['ending_cash_balance'] ?? 0) - ($data['calculated_ending_cash'] ?? 0)) > 0.01)
            <div class="flex justify-between text-xs text-red-500 dark:text-red-400 mt-1 font-medium">
                <span>Selisih:</span>
                <span>Rp {{ number_format(abs(($data['ending_cash_balance'] ?? 0) - ($data['calculated_ending_cash'] ?? 0)), 0, ',', '.') }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
