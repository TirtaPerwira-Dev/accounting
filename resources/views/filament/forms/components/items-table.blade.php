@php
    $items = $getState() ?? [];
    $total = 0;

    // Calculate total from items
    if (is_array($items) && !empty($items)) {
        $total = array_sum(array_column($items, 'jumlah'));
    }

    // Get options for display
    $nomorBantuOptions = collect();
    $kodeProyekOptions = collect();

    try {
        $nomorBantuOptions = \App\Models\NomorBantu::with(['rekening.kelompok'])
            ->get()
            ->mapWithKeys(function ($n) {
                $code = $n->rekening->kelompok->no_kel .
                    $n->rekening->no_rek .
                    str_pad($n->no_bantu, 2, '0', STR_PAD_LEFT);
                return [$n->id => "[$code] {$n->nm_bantu}"];
            });

        $kodeProyekOptions = \App\Models\KodeProyek::pluck('name', 'id');
    } catch (Exception $e) {
        // Handle any database errors gracefully
    }
@endphp

@if(empty($items) || !is_array($items))
    <div class="text-center py-8 text-gray-500">
        <div class="mb-2">
            <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-sm">Belum ada item pembelian</p>
        <p class="text-xs text-gray-400">Gunakan form di atas untuk menambah item</p>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bukti</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Proyek</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kode Rekening</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                @foreach($items as $index => $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $index + 1 }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                        @if(!empty($item['bukti']))
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ $item['bukti'] }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                        <div class="max-w-xs">
                            <p class="truncate">{{ $item['keterangan'] ?? '-' }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                        @if(!empty($item['kode_proyek_id']) && $kodeProyekOptions->has($item['kode_proyek_id']))
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200">
                                {{ $kodeProyekOptions->get($item['kode_proyek_id']) }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if(!empty($item['nomor_bantu_debit_id']) && $nomorBantuOptions->has($item['nomor_bantu_debit_id']))
                            <span class="text-xs text-gray-600 dark:text-gray-300">
                                {{ $nomorBantuOptions->get($item['nomor_bantu_debit_id']) }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-right text-green-600 dark:text-green-400">
                        Rp {{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex justify-center space-x-2">
                            <button
                                type="button"
                                onclick="editItem({{ $index }}, {{ json_encode($item) }})"
                                class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-700 dark:text-indigo-200 dark:hover:bg-indigo-600"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="deleteItem({{ $index }})"
                                class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-700 dark:text-red-200 dark:hover:bg-red-600"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                        Total:
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-bold text-green-600 dark:text-green-400">
                        Rp {{ number_format($total, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif

<script>
function deleteItem(index) {
    if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
        try {
            // Use Livewire to get and update the state
            const currentItems = @this.get('pembelian_items') || [];

            // Remove item at index
            currentItems.splice(index, 1);

            // Update the form data
            @this.set('pembelian_items', currentItems);

            // Show notification using Filament notification
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    type: 'success',
                    message: 'Item berhasil dihapus!'
                }
            }));
        } catch (error) {
            console.error('Error deleting item:', error);
        }
    }
}

function editItem(index, item) {
    try {
        // Populate the form with item data
        @this.set('temp_bukti', item.bukti || '');
        @this.set('temp_keterangan', item.keterangan || '');
        @this.set('temp_kode_proyek_id', item.kode_proyek_id || null);
        @this.set('temp_nomor_bantu_debit_id', item.nomor_bantu_debit_id || null);
        @this.set('temp_jumlah', item.jumlah ? item.jumlah.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '');

        // Remove the item from the list (will be re-added when user clicks "Tambah Item")
        deleteItem(index);

        // Scroll to form
        setTimeout(() => {
            const formElement = document.querySelector('[data-field-wrapper="temp_bukti"]') ||
                               document.querySelector('input[name="temp_bukti"]') ||
                               document.querySelector('.temp-form');
            if (formElement) {
                formElement.scrollIntoView({ behavior: 'smooth' });
            }
        }, 100);
    } catch (error) {
        console.error('Error editing item:', error);
    }
}
</script>
