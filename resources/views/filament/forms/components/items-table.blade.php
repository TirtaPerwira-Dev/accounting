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

        $kodeProyekOptions = \App\Models\KodeProyek::query()
            ->select(['id', 'kode', 'name'])
            ->get()
            ->mapWithKeys(fn($proyek) => [
                $proyek->id => $proyek->kode . ' - ' . $proyek->name,
            ]);
    } catch (Exception $e) {
        // Handle any database errors gracefully
    }
@endphp

@if(empty($items) || !is_array($items))
    <div class="fi-ta-empty-state px-6 py-12 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl">
        <div class="fi-ta-empty-state-content mx-auto max-w-lg text-center">
            <div class="fi-ta-empty-state-icon-ctn mb-4 flex justify-center">
                <div class="fi-ta-empty-state-icon flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <svg class="fi-ta-empty-state-icon-svg h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>
                    </svg>
                </div>
            </div>

            <div class="fi-ta-empty-state-heading-ctn">
                <h4 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Belum Ada Item Pembelian
                </h4>
            </div>

            <div class="fi-ta-empty-state-description-ctn">
                <p class="fi-ta-empty-state-description text-sm text-gray-500 dark:text-gray-400">
                    Gunakan form di atas untuk menambah item pembelian
                </p>
            </div>
        </div>
    </div>
@else
    <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="divide-y divide-gray-200 dark:divide-white/5">
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">No</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Bukti</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Keterangan</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Proyek</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Kode Rekening</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-end">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Jumlah</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-center">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Aksi</span>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                @foreach($items as $index => $item)
                <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white font-medium">
                                        {{ $index + 1 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white">
                                        @if(!empty($item['bukti']))
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-warning fi-badge-color-warning bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30">
                                                {{ $item['bukti'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 italic text-xs">No bukti</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white">
                                        <div class="max-w-xs whitespace-normal">
                                            <span class="block break-words leading-5">{{ $item['keterangan'] ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white">
                                        @if(!empty($item['kode_proyek_id']) && $kodeProyekOptions->has($item['kode_proyek_id']))
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-info fi-badge-color-info bg-info-50 text-info-600 ring-info-600/10 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30">
                                                {{ $kodeProyekOptions->get($item['kode_proyek_id']) }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 italic text-xs">No project</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white">
                                        @if(!empty($item['nomor_bantu_debit_id']) && $nomorBantuOptions->has($item['nomor_bantu_debit_id']))
                                            <div class="max-w-sm">
                                                <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-gray fi-badge-color-gray bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30 font-mono">
                                                    {{ $nomorBantuOptions->get($item['nomor_bantu_debit_id']) }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 italic text-xs">No account</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white font-semibold">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-success fi-badge-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                            Rp {{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-center">
                                    <div class="fi-ta-actions flex shrink-0 items-center gap-3">
                                        <button
                                            type="button"
                                            wire:click="editItem({{ $index }})"
                                            @disabled($get('items_completed'))
                                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-sm fi-btn-size-sm gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 focus-visible:ring-primary-600 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:focus-visible:ring-primary-500 ring-1 ring-gray-950/10 dark:ring-white/20"
                                            title="Edit item"
                                        >
                                            <svg class="fi-btn-icon transition duration-75 h-4 w-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/>
                                                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="removeItem({{ $index }})"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus item ini?');"
                                            @disabled($get('items_completed'))
                                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-danger fi-btn-color-danger fi-size-sm fi-btn-size-sm gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-danger-600 hover:bg-danger-50 focus-visible:ring-primary-600 dark:bg-white/5 dark:text-danger-400 dark:hover:bg-danger-500/10 dark:focus-visible:ring-primary-500 ring-1 ring-danger-600/20 dark:ring-danger-400/30"
                                            title="Hapus item"
                                        >
                                            <svg class="fi-btn-icon transition duration-75 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="divide-y divide-gray-200 dark:divide-white/5">
                <tr class="bg-gray-50 dark:bg-white/5">
                    <td colspan="5" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white font-semibold">
                                        Total Keseluruhan:
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-sm font-bold ring-1 ring-inset px-3 py-2 fi-color-success fi-badge-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                            Rp {{ number_format($total, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4"></div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif

<script>
// Utility function untuk scroll ke form setelah edit
function scrollToEditForm() {
    setTimeout(() => {
        // Multiple selectors untuk menemukan form input
        const selectors = [
            'input[wire\\:model="temp_bukti"]',
            'input[name="temp_bukti"]',
            '[data-field-wrapper="temp_bukti"]',
            '.temp-bukti-field',
            '#temp_bukti'
        ];

        let formElement = null;
        for (const selector of selectors) {
            formElement = document.querySelector(selector);
            if (formElement) break;
        }

        if (formElement) {
            formElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Focus pada input jika memungkinkan
            if (formElement.tagName === 'INPUT') {
                formElement.focus();
            }
        }
    }, 300); // Delay lebih lama untuk memastikan form sudah terupdate
}

// Event listener untuk menangani setelah edit item
document.addEventListener('DOMContentLoaded', function() {
    // Listen untuk event Livewire setelah action selesai
    window.addEventListener('livewire:navigated', function() {
        console.log('Items table initialized');
    });

    // Listen untuk custom event dari backend
    window.addEventListener('scroll-to-form', function() {
        scrollToEditForm();
    });
});
</script>
