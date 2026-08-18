<div class="flex gap-x-3 p-4 bg-white dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 transition-colors hover:bg-gray-50 dark:hover:bg-white/10 group">
    <div @class([
        'mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 dark:border-white/10',
        'bg-orange-50 text-orange-600 dark:bg-orange-400/10 dark:text-orange-400' => $action === 'confirm',
        'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400' => $action === 'post',
    ])>
        <x-filament::icon icon="heroicon-o-document-text" class="h-6 w-6" />
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex justify-between items-start gap-x-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-white break-words">
                @php
                    $reference = match(get_class($record)) {
                        \App\Models\JurnalBayarKasBank::class => $record->no_voucher,
                        \App\Models\JurnalPembelian::class => $record->bukti_item,
                        default => $record->nomor_bukti ?? ($record->bukti ?? null)
                    };
                @endphp
                {{ $reference ?? 'No Reference' }}
            </span>
            <div class="flex items-center gap-x-2 shrink-0">
                <span class="text-[10px] text-gray-400 font-medium uppercase tracking-tighter">
                    {{ $record->tanggal?->format('d M Y') }}
                </span>
                <button 
                    wire:click="dismissJournal('{{ $type }}', {{ $record->id }})"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                    title="Clear"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                </button>
            </div>
        </div>
        
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Amount: <span class="font-bold text-gray-700 dark:text-gray-200">Rp {{ number_format($record->total_amount ?? ($record->rp ?? 0), 0, ',', '.') }}</span>
        </div>

        <div class="mt-3 flex items-center gap-x-3">
            @php
                $resource = match($type) {
                    'Penerimaan Kas' => \App\Filament\Accounting\Resources\JurnalPenerimaanKasResource::class,
                    'Bayar Kas/Bank' => \App\Filament\Accounting\Resources\JurnalBayarKasBankResource::class,
                    'Pembelian' => \App\Filament\Accounting\Resources\JurnalPembelianResource::class,
                    'Memorial' => \App\Filament\Accounting\Resources\JurnalMemorialResource::class,
                    'Pemakaian Bahan' => \App\Filament\Accounting\Resources\JurnalPemakaianBahanResource::class,
                    'Rekening Air' => \App\Filament\Accounting\Resources\JurnalRekeningAirResource::class,
                    default => null,
                };
                
                $indexUrl = $resource ? $resource::getUrl('index', [
                    'tableFilters[is_posted][value]' => $action === 'post' ? '0' : '1',
                ], panel: 'accounting') : '#';
            @endphp

            <x-filament::link
                href="{{ $indexUrl }}"
                size="sm"
                color="gray"
                icon="heroicon-m-eye"
                class="font-bold tracking-tight text-xs"
            >
                View
            </x-filament::link>

            @if(($canAct ?? false) && $action === 'post')
                <x-filament::link
                    wire:click="postJournal('{{ get_class($record) }}', {{ $record->id }})"
                    wire:loading.attr="disabled"
                    size="sm"
                    color="success"
                    icon="heroicon-m-bolt"
                    class="font-bold tracking-tight text-xs cursor-pointer"
                >
                    Post Ledger
                </x-filament::link>
            @endif
        </div>
    </div>
</div>
