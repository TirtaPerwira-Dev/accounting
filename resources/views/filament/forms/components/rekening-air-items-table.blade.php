@php
    $items = $getState() ?? [];
    $totalDebit = 0;
    $totalKredit = 0;

    // Calculate totals from items
    if (is_array($items) && !empty($items)) {
        foreach ($items as $item) {
            if (($item['position'] ?? '') === 'debit') {
                $totalDebit += $item['jumlah'] ?? 0;
            } else {
                $totalKredit += $item['jumlah'] ?? 0;
            }
        }
    }

    $isBalance = $totalDebit === $totalKredit && $totalDebit > 0;

    // Get options for display
    $rekeningOptions = collect();
    $nomorBantuOptions = collect();
    $kodeProyekOptions = collect();

    try {
        $rekeningOptions = \App\Models\Rekening::with('kelompok')
            ->get()
            ->mapWithKeys(function ($rekening) {
                $code = $rekening->kelompok->no_kel . '-' . $rekening->no_rek;
                return [$rekening->id => "[$code] {$rekening->nama_rek}"];
            });

        $nomorBantuOptions = \App\Models\NomorBantu::with(['rekening.kelompok'])
            ->get()
            ->mapWithKeys(function ($n) {
                return [$n->id => "{$n->no_bantu} - {$n->nm_bantu}"];
            });

        $kodeProyekOptions = \App\Models\KodeProyek::pluck('name', 'id');
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
                    Belum Ada Item Transaksi
                </h4>
            </div>
            
            <div class="fi-ta-empty-state-description-ctn">
                <p class="fi-ta-empty-state-description text-sm text-gray-500 dark:text-gray-400">
                    Gunakan form di atas untuk menambah item transaksi
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
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Proyek</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Rekening</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Nomor Bantu</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-center">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">D/K</span>
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
                                        @if(!empty($item['kode_proyek']) && $kodeProyekOptions->has($item['kode_proyek']))
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-info fi-badge-color-info bg-info-50 text-info-600 ring-info-600/10 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30">
                                                {{ $kodeProyekOptions->get($item['kode_proyek']) }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 italic text-xs">-</span>
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
                                        @if(!empty($item['rekening']) && $rekeningOptions->has($item['rekening']))
                                            <div class="max-w-sm">
                                                <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-gray fi-badge-color-gray bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30 font-mono">
                                                    {{ $rekeningOptions->get($item['rekening']) }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 italic text-xs">-</span>
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
                                        @if(!empty($item['nomor_bantu']) && $nomorBantuOptions->has($item['nomor_bantu']))
                                            <div class="max-w-sm">
                                                <span class="text-xs">
                                                    {{ $nomorBantuOptions->get($item['nomor_bantu']) }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 italic text-xs">-</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-center">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white">
                                        @if(($item['position'] ?? '') === 'debit')
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-danger fi-badge-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                                D
                                            </span>
                                        @else
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-success fi-badge-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                                K
                                            </span>
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
                                        @if(($item['position'] ?? '') === 'debit')
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-danger fi-badge-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                                Rp {{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-success fi-badge-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                                Rp {{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}
                                            </span>
                                        @endif
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
                                            wire:click="editItem({{ $index }}, {{ json_encode($item) }})"
                                            onclick="if(@this.get('items_completed')) { alert('Items sudah dikonfirmasi selesai. Klik Reset Konfirmasi untuk mengubah item.'); return false; }"
                                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-sm fi-btn-size-sm gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 focus-visible:ring-primary-600 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:focus-visible:ring-primary-500 ring-1 ring-gray-950/10 dark:ring-white/20"
                                            title="Edit item"
                                        >
                                            <svg class="animate-spin fi-btn-icon transition duration-75 h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z"></path>
                                            </svg>
                                            <svg class="fi-btn-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z"/>
                                            </svg>
                                            <span class="fi-btn-label">Edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="removeItem({{ $index }})"
                                            onclick="if(@this.get('items_completed')) { alert('Items sudah dikonfirmasi selesai. Klik Reset Konfirmasi untuk menghapus item.'); return false; }"
                                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-danger fi-btn-color-danger fi-size-sm fi-btn-size-sm gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-red-600 text-white hover:bg-red-500 focus-visible:ring-red-500 dark:bg-red-500 dark:hover:bg-red-400 dark:focus-visible:ring-red-400"
                                            title="Hapus item"
                                        >
                                            <svg class="animate-spin fi-btn-icon transition duration-75 h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z"></path>
                                            </svg>
                                            <svg class="fi-btn-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="fi-btn-label">Hapus</span>
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
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white font-bold">
                                        Total
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex flex-col gap-1 items-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white font-bold">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-danger fi-badge-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                            D: Rp {{ number_format($totalDebit, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 text-gray-950 dark:text-white font-bold">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-success fi-badge-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                            K: Rp {{ number_format($totalKredit, 0, ',', '.') }}
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
                                    @if($isBalance)
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-success fi-badge-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                            ✅ Balance
                                        </span>
                                    @else
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-warning fi-badge-color-warning bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30">
                                            ⚠️ Tidak Balance
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
