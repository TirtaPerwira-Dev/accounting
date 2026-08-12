@props([
    'items' => [],
])

<div class="space-y-2">
    @if(empty($items))
        <div class="text-center py-4 text-gray-500">
            Belum ada item yang ditambahkan
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Bukti</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelompok / Rekening</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Bantu</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Proyek</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($items as $index => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $item['nomor_bukti'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">
                                @if(isset($item['kelompok_id']) && $item['kelompok_id'])
                                    {{ \App\Models\Kelompok::find($item['kelompok_id'])?->no_kel ?? '' }}
                                @endif
                                @if(isset($item['rekening_id']) && $item['rekening_id'])
                                    {{ \App\Models\Rekening::find($item['rekening_id'])?->no_rek ?? '' }} -
                                    {{ \App\Models\Rekening::find($item['rekening_id'])?->nama_rek ?? '' }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-900">
                                @if(isset($item['nomor_bantu_id']) && $item['nomor_bantu_id'])
                                    {{ \App\Models\NomorBantu::find($item['nomor_bantu_id'])?->no_bantu ?? '' }} -
                                    {{ \App\Models\NomorBantu::find($item['nomor_bantu_id'])?->nm_bantu ?? '' }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-900">
                                @if(isset($item['kode_proyek_id']) && $item['kode_proyek_id'])
                                    {{ \App\Models\KodeProyek::find($item['kode_proyek_id'])?->kode ?? '' }} -
                                    {{ \App\Models\KodeProyek::find($item['kode_proyek_id'])?->name ?? '' }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-900 text-right font-medium">
                                Rp {{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-500">{{ $item['keterangan_item'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-center">
                                <button
                                    wire:click="$dispatch('editItem', { index: {{ $index }}, item: @json($item) })"
                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium mr-2"
                                >
                                    Edit
                                </button>
                                <button
                                    wire:click="$dispatch('removeItem', { index: {{ $index }} })"
                                    class="text-red-600 hover:text-red-900 text-sm font-medium"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 text-right font-bold text-lg text-gray-900">
            Total: Rp {{ number_format(collect($items)->sum('jumlah'), 0, ',', '.') }}
        </div>
    @endif
</div>