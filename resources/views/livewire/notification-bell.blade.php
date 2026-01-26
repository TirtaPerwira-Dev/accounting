<div>
    <x-filament::modal id="notification-slideover" slide-over width="md">
        <x-slot name="trigger">
            <div class="flex items-center px-1">
                <button type="button" 
                    class="group relative flex items-center justify-center w-10 h-10 rounded-full bg-gray-100/30 dark:bg-white/5 transition-all duration-200 hover:bg-white dark:hover:bg-white/10"
                    title="Pusat Notifikasi"
                >
                    <x-filament::icon
                        icon="heroicon-o-bell"
                        class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-primary-600 transition-colors"
                    />
                    @if($notificationsCount > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-danger-600 text-[8px] font-black text-white ring-2 ring-white dark:ring-gray-950 shadow-sm transition-transform group-hover:scale-110">
                            {{ $notificationsCount > 99 ? '9+ ' : $notificationsCount }}
                        </span>
                    @endif
                </button>
            </div>
        </x-slot>

        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center bg-primary-100/50 dark:bg-primary-900/30 rounded-xl">
                    <x-filament::icon icon="heroicon-o-bell" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white leading-tight">Pusat Notifikasi</h2>
                    <p class="text-[10px] text-gray-500 font-medium">Informasi transaksi dan sistem terbaru</p>
                </div>
            </div>
        </x-slot>

        <div class="space-y-10 pb-6">
            @if(count($pendingJournals) > 0)
                <div class="space-y-4">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Jurnal Menunggu Aksi</h3>
                    <div class="space-y-6">
                        @foreach($pendingJournals as $type => $data)
                            <div class="space-y-3">
                                <div class="flex items-center justify-between px-1">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $type }}</span>
                                    <span class="text-[10px] bg-primary-50 dark:bg-primary-500/10 text-primary-600 px-2 py-0.5 rounded-full font-bold">
                                        {{ $data['count'] ?? 0 }} Pending
                                    </span>
                                </div>
                                <div class="grid gap-3">
                                    @foreach($data['unconfirmed'] as $record)
                                        @include('livewire.notification-item', ['record' => $record, 'type' => $type, 'action' => 'confirm'])
                                    @endforeach
                                    
                                    @foreach($data['unposted'] as $record)
                                        @include('livewire.notification-item', ['record' => $record, 'type' => $type, 'action' => 'post'])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(isset($recentLogs['activity']) && count($recentLogs['activity']) > 0)
                <div class="space-y-4">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Log Aktivitas Terbaru</h3>
                    <div class="grid gap-2">
                        @foreach($recentLogs['activity'] as $log)
                            <div class="flex items-start gap-3 p-3 bg-gray-50/50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 transition-colors hover:bg-gray-100 dark:hover:bg-white/10">
                                <div class="mt-1 w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <span class="text-[10px] font-bold text-gray-900 dark:text-gray-200 uppercase tracking-tight">{{ $log->log_name }}</span>
                                        <span class="text-[9px] text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-600 dark:text-gray-400 leading-normal">{{ $log->description }}</p>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ \App\Filament\Accounting\Resources\ActivityLogResource::getUrl() }}" class="block p-2 text-center text-[10px] font-bold text-primary-600 hover:underline uppercase tracking-widest">
                            Lihat Semua Log
                        </a>
                    </div>
                </div>
            @endif

            @if(isset($recentLogs['auth']) && count($recentLogs['auth']) > 0)
                <div class="space-y-4">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Log Keamanan</h3>
                    <div class="grid gap-2">
                        @foreach($recentLogs['auth'] as $log)
                            <div class="flex items-center gap-3 p-3 bg-gray-50/50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10">
                                <div @class([
                                    'w-1.5 h-1.5 rounded-full shrink-0',
                                    'bg-success-500' => $log->login_successful,
                                    'bg-danger-500' => !$log->login_successful,
                                ])></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[11px] font-bold text-gray-900 dark:text-gray-200">{{ $log->ip_address }}</span>
                                        <span class="text-[9px] text-gray-400">{{ $log->login_at?->format('H:i') ?? '-' }}</span>
                                    </div>
                                    <p class="truncate text-[9px] text-gray-500 opacity-60 mt-0.5">
                                        {{ str()->limit($log->user_agent, 35) }}
                                    </p>
                                </div>
                                <x-filament::icon 
                                    icon="{{ $log->login_successful ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle' }}" 
                                    class="w-3.5 h-3.5 {{ $log->login_successful ? 'text-success-500' : 'text-danger-500' }} shrink-0 opacity-40" 
                                />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($notificationsCount === 0 && (!isset($recentLogs['activity']) || count($recentLogs['activity']) === 0))
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-white/5 rounded-full flex items-center justify-center mb-4">
                        <x-filament::icon icon="heroicon-o-bell-slash" class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada notifikasi baru</p>
                </div>
            @endif
        </div>
    </x-filament::modal>
</div>
