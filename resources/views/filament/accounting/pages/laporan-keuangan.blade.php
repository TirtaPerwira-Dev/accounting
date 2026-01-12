<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form Filter --}}
        <x-filament::card>
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Filter Laporan
                    </h3>
                </div>

                <form wire:submit="generateReport" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <x-filament::button
                            type="submit"
                            color="primary"
                            icon="heroicon-o-chart-bar"
                            size="lg"
                        >
                            Generate Laporan
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </x-filament::card>

        {{-- Report Display --}}
        @if($reportData)
            <x-filament::card>
                <div class="space-y-6">
                    {{-- Header Laporan --}}
                    <div class="text-center pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $reportData['title'] ?? 'LAPORAN' }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $reportData['periode'] ?? '-' }}
                        </p>
                    </div>

                    {{-- Content --}}
                    @if($reportType === 'neraca')
                        {{-- Neraca Layout --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Aktiva --}}
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                                <h3 class="text-xl font-bold mb-4 text-primary-600 dark:text-primary-400 border-b-2 border-primary-600 dark:border-primary-400 pb-2">
                                    AKTIVA
                                </h3>
                                <div class="space-y-2">
                                    @foreach($reportData['aktiva'] as $item)
                                        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="inline-block w-8 font-mono text-gray-500 dark:text-gray-400 font-semibold">{{ $item['kode'] }}</span>
                                                <span class="ml-2">{{ $item['nama'] }}</span>
                                            </span>
                                            <span class="font-semibold text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($item['saldo'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                    <div class="mt-4 pt-4 border-t-2 border-gray-300 dark:border-gray-600">
                                        <div class="flex justify-between items-center p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                                            <span class="font-bold text-lg text-primary-900 dark:text-primary-100">TOTAL AKTIVA</span>
                                            <span class="font-bold text-lg text-primary-900 dark:text-primary-100 tabular-nums">
                                                Rp {{ number_format($reportData['total_aktiva'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Pasiva --}}
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                                <h3 class="text-xl font-bold mb-4 text-success-600 dark:text-success-400 border-b-2 border-success-600 dark:border-success-400 pb-2">
                                    PASIVA
                                </h3>
                                <div class="space-y-2">
                                    @foreach($reportData['pasiva'] as $item)
                                        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="inline-block w-8 font-mono text-gray-500 dark:text-gray-400 font-semibold">{{ $item['kode'] }}</span>
                                                <span class="ml-2">{{ $item['nama'] }}</span>
                                            </span>
                                            <span class="font-semibold text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($item['saldo'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                    <div class="mt-4 pt-4 border-t-2 border-gray-300 dark:border-gray-600">
                                        <div class="flex justify-between items-center p-3 bg-success-50 dark:bg-success-900/20 rounded-lg">
                                            <span class="font-bold text-lg text-success-900 dark:text-success-100">TOTAL PASIVA</span>
                                            <span class="font-bold text-lg text-success-900 dark:text-success-100 tabular-nums">
                                                Rp {{ number_format($reportData['total_pasiva'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @elseif($reportType === 'laba_rugi')
                        {{-- Laba Rugi Layout --}}
                        <div class="max-w-4xl mx-auto">
                            {{-- Pendapatan --}}
                            <div class="mb-6 bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                                <h3 class="text-xl font-bold mb-4 text-success-600 dark:text-success-400 border-b-2 border-success-600 dark:border-success-400 pb-2">
                                    PENDAPATAN
                                </h3>
                                <div class="space-y-2">
                                    @foreach($reportData['pendapatan'] as $item)
                                        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="inline-block w-8 font-mono text-gray-500 dark:text-gray-400 font-semibold">{{ $item['kode'] }}</span>
                                                <span class="ml-2">{{ $item['nama'] }}</span>
                                            </span>
                                            <span class="font-semibold text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($item['saldo'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                    <div class="mt-4 pt-4 border-t border-gray-300 dark:border-gray-600">
                                        <div class="flex justify-between items-center p-3 bg-success-50 dark:bg-success-900/20 rounded-lg">
                                            <span class="font-semibold text-success-900 dark:text-success-100">Total Pendapatan</span>
                                            <span class="font-semibold text-success-900 dark:text-success-100 tabular-nums">
                                                Rp {{ number_format($reportData['total_pendapatan'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Beban --}}
                            <div class="mb-6 bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                                <h3 class="text-xl font-bold mb-4 text-danger-600 dark:text-danger-400 border-b-2 border-danger-600 dark:border-danger-400 pb-2">
                                    BEBAN
                                </h3>
                                <div class="space-y-2">
                                    @foreach($reportData['beban'] as $item)
                                        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="inline-block w-8 font-mono text-gray-500 dark:text-gray-400 font-semibold">{{ $item['kode'] }}</span>
                                                <span class="ml-2">{{ $item['nama'] }}</span>
                                            </span>
                                            <span class="font-semibold text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($item['saldo'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                    <div class="mt-4 pt-4 border-t border-gray-300 dark:border-gray-600">
                                        <div class="flex justify-between items-center p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                                            <span class="font-semibold text-danger-900 dark:text-danger-100">Total Beban</span>
                                            <span class="font-semibold text-danger-900 dark:text-danger-100 tabular-nums">
                                                Rp {{ number_format($reportData['total_beban'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Laba/Rugi --}}
                            <div class="border-t-4 border-gray-800 dark:border-gray-200 pt-6">
                                <div class="flex justify-between items-center p-6 bg-{{ $reportData['laba_rugi'] >= 0 ? 'success' : 'danger' }}-100 dark:bg-{{ $reportData['laba_rugi'] >= 0 ? 'success' : 'danger' }}-900/30 rounded-xl shadow-lg">
                                    <span class="text-2xl font-bold text-{{ $reportData['laba_rugi'] >= 0 ? 'success' : 'danger' }}-900 dark:text-{{ $reportData['laba_rugi'] >= 0 ? 'success' : 'danger' }}-100">
                                        {{ $reportData['status'] }}
                                    </span>
                                    <span class="text-3xl font-bold text-{{ $reportData['laba_rugi'] >= 0 ? 'success' : 'danger' }}-700 dark:text-{{ $reportData['laba_rugi'] >= 0 ? 'success' : 'danger' }}-300 tabular-nums">
                                        Rp {{ number_format(abs($reportData['laba_rugi']), 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    @elseif($reportType === 'trial_balance')
                        {{-- Trial Balance Layout --}}
                        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-100 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Kode
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Nama Rekening
                                            </th>
                                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Debit
                                            </th>
                                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Kredit
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($reportData['data'] as $item)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm font-mono font-semibold text-gray-600 dark:text-gray-400">
                                                    {{ $item['kode'] }}
                                                </td>
                                                <td class="px-6 py-3 text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $item['nama'] }}
                                                </td>
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-right tabular-nums text-gray-900 dark:text-gray-100">
                                                    @if($item['debit'] > 0)
                                                        <span class="font-semibold">Rp {{ number_format($item['debit'], 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-600">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-right tabular-nums text-gray-900 dark:text-gray-100">
                                                    @if($item['kredit'] > 0)
                                                        <span class="font-semibold">Rp {{ number_format($item['kredit'], 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-600">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-100 dark:bg-gray-900">
                                        <tr class="border-t-2 border-gray-400 dark:border-gray-600">
                                            <td colspan="2" class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white uppercase">
                                                TOTAL
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($reportData['total_debit'], 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($reportData['total_kredit'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    @elseif($reportType === 'buku_besar')
                        {{-- Buku Besar Layout --}}
                        <div class="space-y-8">
                            <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 border-l-4 border-primary-600 dark:border-primary-400">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-semibold">Total Rekening:</span> {{ $reportData['total_rekening'] ?? 0 }} rekening
                                </p>
                            </div>

                            @foreach($reportData['data'] ?? [] as $rekening)
                                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-md">
                                    {{-- Header Rekening --}}
                                    <div class="bg-gradient-to-r from-primary-600 to-primary-500 dark:from-primary-700 dark:to-primary-600 px-6 py-4">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <h3 class="text-lg font-bold text-white">
                                                    {{ $rekening['kode'] }} - {{ $rekening['nama'] }}
                                                </h3>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-primary-100">Saldo Akhir</p>
                                                <p class="text-xl font-bold text-white tabular-nums">
                                                    Rp {{ number_format($rekening['saldo_akhir'] ?? 0, 0, ',', '.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tabel Transaksi --}}
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Tanggal
                                                    </th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Jenis Transaksi
                                                    </th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Debit
                                                    </th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Kredit
                                                    </th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                        Saldo
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @forelse($rekening['transaksi'] ?? [] as $tr)
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                            {{ \Carbon\Carbon::parse($tr['tanggal'])->format('d/m/Y') }}
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                            {{ $tr['jenis'] }}
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right tabular-nums">
                                                            @if($tr['debit'] > 0)
                                                                <span class="text-green-600 dark:text-green-400 font-semibold">
                                                                    Rp {{ number_format($tr['debit'], 0, ',', '.') }}
                                                                </span>
                                                            @else
                                                                <span class="text-gray-400 dark:text-gray-600">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right tabular-nums">
                                                            @if($tr['kredit'] > 0)
                                                                <span class="text-red-600 dark:text-red-400 font-semibold">
                                                                    Rp {{ number_format($tr['kredit'], 0, ',', '.') }}
                                                                </span>
                                                            @else
                                                                <span class="text-gray-400 dark:text-gray-600">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                                            Rp {{ number_format($tr['saldo'], 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                                            Tidak ada transaksi
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot class="bg-gray-50 dark:bg-gray-900">
                                                <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                                    <td colspan="2" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                                                        Total
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-right font-bold text-green-700 dark:text-green-400 tabular-nums">
                                                        Rp {{ number_format($rekening['total_debit'] ?? 0, 0, ',', '.') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-right font-bold text-red-700 dark:text-red-400 tabular-nums">
                                                        Rp {{ number_format($rekening['total_kredit'] ?? 0, 0, ',', '.') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 dark:text-white tabular-nums">
                                                        Rp {{ number_format($rekening['saldo_akhir'] ?? 0, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            @endforeach

                            @if(empty($reportData['data']))
                                <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="text-gray-400 dark:text-gray-600">
                                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                            Tidak ada data transaksi
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Tidak ada transaksi dalam periode yang dipilih
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                    @else
                        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-20 h-20 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    {{ ucwords(str_replace('_', ' ', $reportType)) }}
                                </p>
                                <p class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-4">
                                    Laporan dalam pengembangan
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-500">
                                    Fitur laporan ini sedang dalam tahap pengembangan dan akan segera tersedia.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::card>
        @else
            <x-filament::card>
                <div class="text-center py-16">
                    <div class="text-gray-400 dark:text-gray-600">
                        <p class="text-xl font-medium text-gray-900 dark:text-white mb-2">
                            Pilih Jenis Laporan
                        </p>
                        <p class="text-gray-500 dark:text-gray-400">
                            Tentukan jenis laporan dan periode, lalu klik "Generate Laporan"
                        </p>
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
