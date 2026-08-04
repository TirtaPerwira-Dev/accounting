@php
    $items = $getState() ?? [];
    $total = collect($items)->sum('jumlah');
    $totalItemCount = is_array($items) ? count($items) : 0;

    // ViewField blade tidak selalu menyediakan helper $get, jadi ambil dari state Livewire.
    $livewire = $getLivewire();
    $formData = data_get($livewire, 'data', []);

    $totalItemInput = (int) preg_replace('/[^0-9]/', '', (string) data_get($formData, 'total_item_input', '0'));
    $nominalInput = (float) preg_replace('/[^0-9]/', '', (string) data_get($formData, 'nominal_input', '0'));
    $selisihTotalItem = $totalItemInput - $totalItemCount;
    $selisihNominal = $nominalInput - (float) $total;
    $isSelisihTotalItemZero = $selisihTotalItem === 0;
    $isSelisihNominalZero = abs($selisihNominal) < 0.01;
    $selisihTotalItemLabelClass = $isSelisihTotalItemZero
        ? 'text-xs font-semibold text-success-700 dark:text-success-400'
        : 'text-xs font-semibold text-danger-700 dark:text-danger-400';
    $selisihNominalLabelClass = $isSelisihNominalZero
        ? 'text-xs font-semibold text-success-700 dark:text-success-400'
        : 'text-xs font-semibold text-danger-700 dark:text-danger-400';
    $selisihTotalItemValueClass = $isSelisihTotalItemZero
        ? 'text-xs font-mono font-semibold text-success-700 dark:text-success-400'
        : 'text-xs font-mono font-semibold text-danger-700 dark:text-danger-400';
    $selisihNominalValueClass = $isSelisihNominalZero
        ? 'text-xs font-mono font-semibold text-success-700 dark:text-success-400'
        : 'text-xs font-mono font-semibold text-danger-700 dark:text-danger-400';

    // Get options for display
    $rekeningOptions = collect();
    $nomorBantuOptions = collect();
    $kodeProyekOptions = collect();

    try {
        $rekeningIds = collect($items)
            ->map(fn($item) => $item['rekening_id'] ?? $item['rekening'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $nomorBantuIds = collect($items)
            ->map(fn($item) => $item['nomor_bantu_id'] ?? $item['nomor_bantu'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $kodeProyekIds = collect($items)
            ->map(fn($item) => $item['kode_proyek_id'] ?? $item['kode_proyek'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $rekeningOptions = \App\Models\Rekening::with('kelompok')
            ->whereIn('id', $rekeningIds)
            ->get()
            ->mapWithKeys(function ($rekening) {
                $code = $rekening->kelompok->no_kel . '-' . $rekening->no_rek;
                return [$rekening->id => "[$code] {$rekening->nama_rek}"];
            });

        $nomorBantuOptions = \App\Models\NomorBantu::with(['rekening.kelompok'])
            ->whereIn('id', $nomorBantuIds)
            ->get()
            ->mapWithKeys(function ($n) {
                return [$n->id => "{$n->no_bantu} - {$n->nm_bantu}"];
            });

        $kodeProyekOptions = \App\Models\KodeProyek::whereIn('id', $kodeProyekIds)->pluck('name', 'id');
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                    </svg>
                </div>
            </div>

            <div class="fi-ta-empty-state-heading-ctn">
                <h4 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Belum Ada Item Pembayaran
                </h4>
            </div>

            <div class="fi-ta-empty-state-description-ctn">
                <p class="fi-ta-empty-state-description text-sm text-gray-500 dark:text-gray-400">
                    Gunakan form di atas untuk menambah item pembayaran
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
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Kode Proyek</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Rekening</span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Keterangan</span>
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
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
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
                                        @if(!empty($item['kode_proyek_id']) && $kodeProyekOptions->has($item['kode_proyek_id']))
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-info fi-badge-color-info bg-info-50 text-info-600 ring-info-600/10 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30">
                                                {{ $kodeProyekOptions->get($item['kode_proyek_id']) }}
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
                                <div class="flex flex-col gap-1">
                                    @if(!empty($item['rekening_id']) && $rekeningOptions->has($item['rekening_id']))
                                        <div class="text-sm leading-6 text-gray-950 dark:text-white">
                                            {{ $rekeningOptions->get($item['rekening_id']) }}
                                        </div>
                                        @if(!empty($item['nomor_bantu_id']) && $nomorBantuOptions->has($item['nomor_bantu_id']))
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ $nomorBantuOptions->get($item['nomor_bantu_id']) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400 italic text-xs">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex">
                                    <div class="fi-ta-text-item text-sm leading-6 text-gray-950 dark:text-white w-full">
                                        <span class="text-gray-600 dark:text-gray-400 text-xs block max-w-xl whitespace-normal break-all leading-5"
                                            style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;">
                                            {{ $item['keterangan'] ?: '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2.5 min-w-[theme(spacing.6)] py-1 fi-color-success fi-badge-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 font-mono">
                                            Rp {{ number_format($item['jumlah'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="editItem({{ $index }})"
                                        class="fi-link group/link relative inline-flex items-center justify-center outline-none fi-size-md fi-link-size-md gap-1.5 fi-color-custom fi-ac-action fi-ac-link-action"
                                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                                        title="Edit item">
                                        <svg class="fi-link-icon h-5 w-5 text-custom-500 dark:text-custom-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/>
                                            <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/>
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="removeItem({{ $index }})"
                                        class="fi-link group/link relative inline-flex items-center justify-center outline-none fi-size-md fi-link-size-md gap-1.5 fi-color-danger fi-ac-action fi-ac-link-action"
                                        title="Hapus item">
                                        <svg class="fi-link-icon h-5 w-5 text-danger-500 dark:text-danger-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <td colspan="4" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 font-bold text-gray-950 dark:text-white">
                                        TOTAL PEMBAYARAN:
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td colspan="2" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-sm font-bold ring-1 ring-inset px-3 min-w-[theme(spacing.6)] py-1.5 fi-color-success fi-badge-color-success bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 font-mono">
                                            Rp {{ number_format($total, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-2">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 {{ $selisihTotalItemLabelClass }}">
                                        Selisih Total Item Input:
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td colspan="2" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-2">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <span class="{{ $selisihTotalItemValueClass }}">{{ number_format($selisihTotalItem, 0, ',', '.') }} item</span>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-2">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 {{ $selisihNominalLabelClass }}">
                                        Selisih Nominal Input:
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td colspan="2" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-2">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <span class="{{ $selisihNominalValueClass }}">Rp {{ number_format($selisihNominal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
