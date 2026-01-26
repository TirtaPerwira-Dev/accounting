<div class="p-3.5 bg-white dark:bg-white/5 border border-gray-100 dark:border-white/10 rounded-xl shadow-sm space-y-3.5 transition-all hover:shadow-md hover:border-primary-500/20">
    <div class="flex justify-between items-start gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div @class([
                'w-10 h-10 flex items-center justify-center rounded-lg shrink-0',
                'bg-orange-50 text-orange-600 dark:bg-orange-500/10' => $action === 'confirm',
                'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' => $action === 'post',
            ])>
                <x-filament::icon icon="heroicon-o-document-text" class="w-5 h-5" />
            </div>
            <div class="min-w-0">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-0.5">Bukti/Voucher</span>
                <p class="text-[13px] font-bold text-gray-900 dark:text-gray-100 truncate tracking-tight">
                    {{ $record->nomor_bukti ?? ($record->bukti ?? 'Tanpa Nomor') }}
                </p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium mt-0.5">
                    {{ $record->tanggal?->format('d M Y') }}
                </p>
            </div>
        </div>
        <div class="text-right shrink-0">
            <p class="text-xs font-bold text-primary-600 dark:text-primary-400">
                Rp {{ number_format($record->total_amount ?? ($record->jumlah_item ?? 0), 0, ',', '.') }}
            </p>
            <span @class([
                'inline-block mt-1 px-2 py-0.5 rounded-md text-[8px] font-bold uppercase tracking-wider',
                'bg-orange-100 text-orange-700 dark:bg-orange-400/10 dark:text-orange-400' => $action === 'confirm',
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400' => $action === 'post',
            ])>
                {{ $action === 'confirm' ? 'Konfirmasi' : 'Posting' }}
            </span>
        </div>
    </div>
    
    <div class="flex items-center gap-2 pt-3 border-t border-gray-50 dark:border-white/5">
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
                'tableFilters[is_confirmed][value]' => ($action === 'confirm' ? '0' : '1'),
            ]) : '#';
        @endphp

        <a href="{{ $indexUrl }}" class="flex-1">
            <x-filament::button size="xs" color="gray" variant="outline" class="w-full justify-center">
                Detail
            </x-filament::button>
        </a>

        @if($action === 'confirm')
            <x-filament::button 
                size="xs" 
                color="warning" 
                icon="heroicon-o-check"
                wire:click="confirmJournal('{{ get_class($record) }}', {{ $record->id }})"
                wire:loading.attr="disabled"
                class="flex-1 justify-center"
            >
                Confirm
            </x-filament::button>
        @else
            <x-filament::button 
                size="xs" 
                color="success" 
                icon="heroicon-o-bolt"
                wire:click="postJournal('{{ get_class($record) }}', {{ $record->id }})"
                wire:loading.attr="disabled"
                class="flex-1 justify-center"
            >
                Post Now
            </x-filament::button>
        @endif
    </div>
</div>
