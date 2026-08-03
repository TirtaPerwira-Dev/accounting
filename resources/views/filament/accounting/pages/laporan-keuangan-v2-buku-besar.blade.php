<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit="generateReport" class="space-y-4">
                {{ $this->form }}
                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-play" color="primary">
                        Tampilkan Buku Besar
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        @if($reportData)
            <x-filament::card>
                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Periode: {{ \Carbon\Carbon::parse($reportData['period_start'])->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($reportData['period_end'])->format('d/m/Y') }}
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Referensi</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Keterangan</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Debit</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Kredit</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($reportData['transactions'] as $trx)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ \Carbon\Carbon::parse($trx['date'])->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $trx['reference'] }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $trx['description'] }}</td>
                                        <td class="px-4 py-2 text-sm text-right">{{ $trx['debit'] > 0 ? number_format($trx['debit'], 0, ',', '.') : '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-right">{{ $trx['credit'] > 0 ? number_format($trx['credit'], 0, ',', '.') : '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-right font-semibold">{{ number_format($trx['balance'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Tidak ada transaksi pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm text-right">Total</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ number_format($reportData['total_debit'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ number_format($reportData['total_credit'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ number_format($reportData['ending_balance'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
