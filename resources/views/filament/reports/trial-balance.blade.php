<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">Kode Akun</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">Nama Akun</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">Debit</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">Kredit</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($data['accounts'] as $account)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $account['sakep_code'] }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    {{ $account['account_name'] }}
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 ml-2">
                        {{ ucfirst($account['account_type'] ?? 'N/A') }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ ($account['debit'] ?? 0) > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ ($account['debit'] ?? 0) > 0 ? 'Rp ' . number_format($account['debit'] ?? 0, 0, ',', '.') : '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ ($account['credit'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ ($account['credit'] ?? 0) > 0 ? 'Rp ' . number_format($account['credit'] ?? 0, 0, ',', '.') : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50 dark:bg-gray-800">
            <tr class="font-bold">
                <td colspan="2" class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">TOTAL</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_debit'], 0, ',', '.') }}</td>
                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">Rp {{ number_format($data['total_credit'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-4 p-4 {{ $data['is_balanced'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }} rounded-lg">
        <div class="flex items-center">
            @if($data['is_balanced'])
                <svg class="w-5 h-5 text-green-400 dark:text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-green-800 dark:text-green-200 font-medium">Neraca Saldo Seimbang</span>
            @else
                <svg class="w-5 h-5 text-red-400 dark:text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="text-red-800 dark:text-red-200 font-medium">Neraca Saldo TIDAK Seimbang</span>
                <span class="text-red-600 dark:text-red-400 ml-2">(Selisih: Rp {{ number_format(abs($data['total_debit'] - $data['total_credit']), 0, ',', '.') }})</span>
            @endif
        </div>
    </div>
</div>
