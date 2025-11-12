<div class="space-y-6">
    <!-- Trial Balance Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                Trial Balance - {{ $company->name }}
            </h3>
            <p class="text-sm text-gray-600">
                Opening balances for all root accounts as of {{ now()->format('d M Y') }}
            </p>
        </div>
    </div>

    <!-- Trial Balance Table -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Account Code
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Account Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Debit
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Credit
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php
                            $totalDebit = 0;
                            $totalCredit = 0;
                        @endphp

                        @foreach($accounts as $account)
                            @php
                                $totalDebit += $account['debit'];
                                $totalCredit += $account['credit'];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                    {{ $account['code'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $account['name'] }}
                                    @if($account['children_count'] > 0)
                                        <span class="text-xs text-gray-500 ml-2">
                                            ({{ $account['children_count'] }} sub accounts)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @switch($account['type'])
                                            @case('asset') bg-green-100 text-green-800 @break
                                            @case('liability') bg-yellow-100 text-yellow-800 @break
                                            @case('equity') bg-blue-100 text-blue-800 @break
                                            @case('revenue') bg-purple-100 text-purple-800 @break
                                            @case('expense') bg-red-100 text-red-800 @break
                                            @default bg-gray-100 text-gray-800
                                        @endswitch">
                                        {{ ucfirst($account['type']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $account['debit'] > 0 ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                    @if($account['debit'] > 0)
                                        Rp {{ number_format($account['debit'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $account['credit'] > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                    @if($account['credit'] > 0)
                                        Rp {{ number_format($account['credit'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        <!-- Total Row -->
                        <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" colspan="3">
                                TOTAL
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                                Rp {{ number_format($totalDebit, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                Rp {{ number_format($totalCredit, 2) }}
                            </td>
                        </tr>

                        <!-- Balance Check -->
                        <tr class="bg-{{ $totalDebit == $totalCredit ? 'green' : 'red' }}-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" colspan="3">
                                <span class="font-medium">
                                    @if($totalDebit == $totalCredit)
                                        ✅ BALANCED
                                    @else
                                        ❌ NOT BALANCED (Difference: Rp {{ number_format(abs($totalDebit - $totalCredit), 2) }})
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                                Difference: Rp {{ number_format(abs($totalDebit - $totalCredit), 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                @if($totalDebit == $totalCredit)
                                    <span class="text-green-600">✓ Balanced</span>
                                @else
                                    <span class="text-red-600">✗ Unbalanced</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
