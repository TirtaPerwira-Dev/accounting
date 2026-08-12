@php
    $options = $getState() ?? [];
    $selected = (string) data_get($this, 'data.item_sumber', '');
@endphp

@if(empty($options))
    <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
        Belum ada hasil pencarian. Jalankan pencarian di Section 1 terlebih dahulu.
    </div>
@else
    <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
        Klik salah satu kartu untuk memilih item sumber. Informasi sumber dan simulasi debit/kredit ditampilkan langsung pada kartu.
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        @foreach($options as $value => $label)
            @php
                $isSelected = $selected === (string) $value;
                $parts = array_map('trim', explode('|', (string) $label));
                $tanggal = $parts[0] ?? '-';
                $bukti = $parts[1] ?? '-';
                $akun = $parts[2] ?? '-';
                $posisi = strtoupper($parts[3] ?? '-');
                $jumlah = $parts[4] ?? 'Rp 0';
                $koreksiPosisi = $posisi === 'D' ? 'K' : 'D';

                $valueParts = array_pad(explode('|', (string) $value), 3, null);
                $sumberJurnal = str_replace('_', ' ', (string) ($valueParts[0] ?? '-'));
            @endphp

            <button
                type="button"
                wire:click="selectSourceItem(@js($value))"
                class="w-full rounded-xl border p-4 text-left transition {{ $isSelected ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-200 dark:border-primary-400 dark:bg-primary-900/20 dark:ring-primary-800' : 'border-gray-200 bg-white hover:border-primary-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-700 dark:hover:bg-gray-800/80' }}"
            >
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ strtoupper($sumberJurnal) }}</span>
                    <span class="rounded-md px-2 py-0.5 text-xs font-semibold {{ $posisi === 'D' ? 'bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300' : 'bg-success-50 text-success-700 dark:bg-success-900/30 dark:text-success-300' }}">
                        {{ $posisi }}
                    </span>
                </div>

                <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    {{ $akun }}
                </div>

                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <div>Tanggal: {{ $tanggal }}</div>
                    <div class="text-right">Bukti: {{ $bukti }}</div>
                </div>

                <div class="mt-3 rounded-lg border border-gray-200 px-3 py-2 text-xs dark:border-gray-700">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">Sumber</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $posisi }} | {{ $jumlah }}</div>
                        </div>
                        <div class="text-gray-400 dark:text-gray-500">→</div>
                        <div class="text-right">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">Koreksi</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $koreksiPosisi }} | {{ $jumlah }}</div>
                        </div>
                    </div>
                </div>
            </button>
        @endforeach
    </div>
@endif
