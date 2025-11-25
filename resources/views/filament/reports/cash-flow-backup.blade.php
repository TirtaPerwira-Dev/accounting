<div class="max-w-3xl mx-auto">
<!-- Operating Activities -->
<div class="mb-8">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">ARUS KAS DARI AKTIVITAS OPERASIONAL</h4>
    <div class="space-y-2">
        @foreach($data['operating'] as $item)
        <div class="flex justify-between items-center py-2">
            <div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $item['code'] }}</span>
            </div>
            <span class="text-sm {{ $item['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $item['amount'] >= 0 ? '+' : '' }}Rp {{ number_format($item['amount'], 2) }}
            </span>
        </div>
        @endforeach
    </div>
    <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center font-semibold">
            <span class="text-gray-900 dark:text-gray-100">Net Cash from Operating Activities</span>
            <span class="{{ $data['total_operating'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $data['total_operating'] >= 0 ? '+' : '' }}Rp {{ number_format($data['total_operating'], 2) }}
            </span>
        </div>
    </div>
</div>

<!-- Investing Activities -->
<div class="mb-8">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">CASH FLOWS FROM INVESTING ACTIVITIES</h4>
    <div class="space-y-2">
        @forelse($data['investing'] as $item)
        <div class="flex justify-between items-center py-2">
            <div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $item['code'] }}</span>
            </div>
            <span class="text-sm {{ $item['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $item['amount'] >= 0 ? '+' : '' }}Rp {{ number_format($item['amount'], 2) }}
            </span>
        </div>
        @empty
        <div class="text-sm text-gray-500 dark:text-gray-400 italic py-2">No investing activities for this period</div>
        @endforelse
    </div>
    <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center font-semibold">
            <span class="text-gray-900 dark:text-gray-100">Net Cash from Investing Activities</span>
            <span class="{{ $data['total_investing'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $data['total_investing'] >= 0 ? '+' : '' }}Rp {{ number_format($data['total_investing'], 2) }}
            </span>
        </div>
    </div>
</div>

<!-- Financing Activities -->
<div class="mb-8">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">CASH FLOWS FROM FINANCING ACTIVITIES</h4>
    <div class="space-y-2">
        @forelse($data['financing'] as $item)
        <div class="flex justify-between items-center py-2">
            <div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $item['code'] }}</span>
            </div>
            <span class="text-sm {{ $item['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $item['amount'] >= 0 ? '+' : '' }}Rp {{ number_format($item['amount'], 2) }}
            </span>
        </div>
        @empty
        <div class="text-sm text-gray-500 dark:text-gray-400 italic py-2">No financing activities for this period</div>
        @endforelse
    </div>
    <div class="mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center font-semibold">
            <span class="text-gray-900 dark:text-gray-100">Net Cash from Financing Activities</span>
            <span class="{{ $data['total_financing'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $data['total_financing'] >= 0 ? '+' : '' }}Rp {{ number_format($data['total_financing'], 2) }}
            </span>
        </div>
    </div>
</div>

<!-- Net Change in Cash -->
<div class="mt-6 p-4 {{ $data['net_change'] >= 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }} rounded-lg">
    <div class="flex justify-between items-center">
        <span class="text-lg font-bold {{ $data['net_change'] >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">NET CHANGE IN CASH</span>
        <span class="text-lg font-bold {{ $data['net_change'] >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
            {{ $data['net_change'] >= 0 ? '+' : '' }}Rp {{ number_format($data['net_change'], 2) }}
        </span>
    </div>
</div>

<!-- Cash Summary -->
<div class="mt-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
    <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Cash Summary</h5>
    <div class="space-y-2 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Beginning Cash Balance:</span>
            <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['beginning_cash'], 2) }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Net Change in Cash:</span>
            <span class="{{ $data['net_change'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $data['net_change'] >= 0 ? '+' : '' }}Rp {{ number_format($data['net_change'], 2) }}
            </span>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
            <div class="flex justify-between font-semibold">
                <span class="text-gray-900 dark:text-gray-100">Ending Cash Balance:</span>
                <span class="text-gray-900 dark:text-gray-100">Rp {{ number_format($data['ending_cash'], 2) }}</span>
            </div>
        </div>
    </div>
</div>    <!-- Investing Activities -->
    <div class="mb-8">
        <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">CASH FLOWS FROM INVESTING ACTIVITIES</h4>
        <div class="space-y-2">
            @forelse($data['investing_activities'] as $activity)
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-900">{{ $activity['description'] }}</span>
                <span class="text-sm {{ $activity['amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($activity['amount'], 2) }}
                </span>
            </div>
            @empty
            <div class="text-sm text-gray-500 py-2">No investing activities recorded</div>
            @endforelse
        </div>
        <div class="mt-4 pt-2 border-t border-gray-200">
            <div class="flex justify-between items-center font-semibold">
                <span class="text-gray-900">Net Cash from Investing Activities</span>
                <span class="{{ $data['net_investing_cash'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($data['net_investing_cash'], 2) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Financing Activities -->
    <div class="mb-8">
        <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">CASH FLOWS FROM FINANCING ACTIVITIES</h4>
        <div class="space-y-2">
            @forelse($data['financing_activities'] as $activity)
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-900">{{ $activity['description'] }}</span>
                <span class="text-sm {{ $activity['amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($activity['amount'], 2) }}
                </span>
            </div>
            @empty
            <div class="text-sm text-gray-500 py-2">No financing activities recorded</div>
            @endforelse
        </div>
        <div class="mt-4 pt-2 border-t border-gray-200">
            <div class="flex justify-between items-center font-semibold">
                <span class="text-gray-900">Net Cash from Financing Activities</span>
                <span class="{{ $data['net_financing_cash'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($data['net_financing_cash'], 2) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="pt-4 border-t-2 border-gray-300">
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-900">Net Increase (Decrease) in Cash</span>
                <span class="text-sm {{ $data['net_cash_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($data['net_cash_change'], 2) }}
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-900">Cash at Beginning of Period</span>
                <span class="text-sm text-gray-900">Rp {{ number_format($data['cash_beginning'], 2) }}</span>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-300">
            <div class="flex justify-between items-center font-bold text-lg">
                <span class="text-gray-900">CASH AT END OF PERIOD</span>
                <span class="text-gray-900">Rp {{ number_format($data['cash_ending'], 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Cash Health Indicator -->
    <div class="mt-6 p-4 {{ $data['cash_ending'] >= 0 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }} rounded-lg">
        <div class="flex items-center">
            @if($data['cash_ending'] >= 0)
                <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-green-800 font-medium">Positive Cash Position</span>
            @else
                <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="text-red-800 font-medium">Negative Cash Position - Requires Attention</span>
            @endif
        </div>
    </div>
</div>
