@php
    $items = $getState() ?? [];
    $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
    $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');
    $balance = $totalDebit - $totalKredit;

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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
            </div>

            <div class="fi-ta-empty-state-heading-ctn">
                <h4 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Belum Ada Item Memorial
                </h4>
            </div>

            <div class="fi-ta-empty-state-description-ctn">
                <p class="fi-ta-empty-state-description text-sm text-gray-500 dark:text-gray-400">
                    Gunakan form di atas untuk menambah item jurnal memorial
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
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Keterangan</span>
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
                                <div class="flex flex-col gap-1">
                                    @if(!empty($item['rekening']) && $rekeningOptions->has($item['rekening']))
                                        <div class="text-sm leading-6 text-gray-950 dark:text-white">
                                            {{ $rekeningOptions->get($item['rekening']) }}
                                        </div>
                                        @if(!empty($item['nomor_bantu']) && $nomorBantuOptions->has($item['nomor_bantu']))
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ $nomorBantuOptions->get($item['nomor_bantu']) }}
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
                                <div class="flex justify-center">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 {{ $item['position'] === 'debit' ? 'fi-color-primary bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30' : 'fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30' }}">
                                            {{ strtoupper($item['position'] ?? 'D') }}
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
                                <div class="flex">
                                    <div class="fi-ta-text-item text-sm leading-6 text-gray-950 dark:text-white">
                                        <span class="text-gray-600 dark:text-gray-400 text-xs max-w-xs truncate">
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
                                <div class="flex justify-end gap-4">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 font-bold text-gray-950 dark:text-white">
                                        DEBIT: <span class="text-primary-600 dark:text-primary-400">Rp {{ number_format($totalDebit, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5 text-sm leading-6 font-bold text-gray-950 dark:text-white">
                                        KREDIT: <span class="text-danger-600 dark:text-danger-400">Rp {{ number_format($totalKredit, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td colspan="3" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                        <div class="fi-ta-col-wrp px-3 py-4">
                            <div class="fi-ta-text grid w-full gap-y-1">
                                <div class="flex justify-end">
                                    <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-sm font-bold ring-1 ring-inset px-3 min-w-[theme(spacing.6)] py-1.5 {{ $balance == 0 ? 'fi-color-success bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30' : 'fi-color-warning bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30' }} font-mono">
                                            {{ $balance == 0 ? '✓ Balance' : 'Selisih: Rp ' . number_format(abs($balance), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
