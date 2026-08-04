<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Jurnal Koreksi" description="Gunakan halaman ini untuk membuat jurnal koreksi (reversal + akun koreksi) dari item jurnal yang sudah diinput.">
            <form wire:submit="createKoreksi" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                    <x-filament::button type="submit" icon="heroicon-o-check-circle" size="lg">
                        Simpan Jurnal Koreksi
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @php
            $recentKoreksi = \App\Models\JurnalMemorial::query()
                ->where('keterangan', 'like', '[KOREKSI]%')
                ->latest('id')
                ->limit(20)
                ->get();
        @endphp

        <x-filament::section heading="Riwayat Jurnal Koreksi" description="20 jurnal koreksi terakhir.">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-2 pr-3">Tanggal</th>
                            <th class="py-2 pr-3">No Bukti</th>
                            <th class="py-2 pr-3">Keterangan</th>
                            <th class="py-2 pr-3 text-right">Nominal</th>
                            <th class="py-2 pr-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentKoreksi as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-3">{{ optional($row->tanggal)->format('d/m/Y') }}</td>
                                <td class="py-2 pr-3">{{ $row->bukti }}</td>
                                <td class="py-2 pr-3">{{ $row->keterangan }}</td>
                                <td class="py-2 pr-3 text-right">Rp {{ number_format((float) $row->rp, 0, ',', '.') }}</td>
                                <td class="py-2 pr-3 text-center">
                                    {{ $row->is_posted ? 'Posted' : ($row->is_confirmed ? 'Confirmed' : 'Draft') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-3 text-center text-gray-500">Belum ada jurnal koreksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
