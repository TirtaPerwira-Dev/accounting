<div class="flex items-center px-1">
    <x-filament::modal id="notification-slideover" slide-over width="md">
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-o-bell"
                color="gray"
                :badge="$notificationsCount"
                badge-color="danger"
                size="lg"
                label="Notifications"
                class="filament-notifications-trigger"
            />
        </x-slot>

        <x-slot name="heading">
            <span class="text-xl font-bold tracking-tight">Notifications</span>
        </x-slot>

        <div class="space-y-6 pb-6">
            @if(count($pendingJournals) > 0)
                <div class="space-y-4">
                    <div class="space-y-4">
                        @foreach($pendingJournals as $type => $data)
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2 px-1">
                                    <div class="flex items-center gap-2 flex-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $type }}</span>
                                        <div class="h-px flex-1 bg-gray-100 dark:bg-white/10"></div>
                                    </div>
                                    <button 
                                        wire:click="clearAllJournals('{{ $type }}')"
                                        class="text-[10px] font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 uppercase tracking-tight ml-2"
                                    >
                                        Clear All
                                    </button>
                                </div>
                                <div class="grid gap-3">
                                    @foreach($data['records'] as $record)
                                        @include('livewire.notification-item', [
                                            'record' => $record,
                                            'type' => $type,
                                            'action' => $data['action'] ?? 'post',
                                            'canAct' => $data['can_post'] ?? false,
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(isset($recentLogs['activity']) && count($recentLogs['activity']) > 0)
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between gap-2 px-1">
                        <div class="flex items-center gap-2 flex-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Activity Logs</span>
                            <div class="h-px flex-1 bg-gray-100 dark:bg-white/10"></div>
                        </div>
                        <button 
                            wire:click="clearAllLogs('activity')"
                            class="text-[10px] font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 uppercase tracking-tight ml-2"
                        >
                            Clear All
                        </button>
                    </div>
                    <div class="grid gap-2">
                        @foreach($recentLogs['activity'] as $log)
                            <div class="relative flex items-start gap-3 p-3 bg-white dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 transition-colors hover:bg-gray-50 dark:hover:bg-white/10 group">
                                <div class="mt-1 flex h-8 w-8 items-center justify-center rounded-lg bg-gray-50 dark:bg-white/5 text-gray-400 text-xs">
                                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-4 w-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start mb-0.5">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white capitalize truncate pr-2">{{ $log->log_name }}</span>
                                        <div class="flex items-center gap-x-2 shrink-0">
                                            <span class="text-[9px] text-gray-400 font-medium whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</span>
                                            <button 
                                                wire:click="dismissLog('activity', {{ $log->id }})"
                                                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                                                title="Clear"
                                            >
                                                <x-filament::icon icon="heroicon-m-x-mark" class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-normal">{{ $log->description }}</p>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ \App\Filament\Accounting\Resources\ActivityLogResource::getUrl(panel: 'accounting') }}" class="block p-2 text-center text-xs font-semibold text-primary-600 hover:underline">
                            View all activity
                        </a>
                    </div>
                </div>
            @endif

            @if(isset($recentLogs['auth']) && count($recentLogs['auth']) > 0)
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between gap-2 px-1">
                        <div class="flex items-center gap-2 flex-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Security Logs</span>
                            <div class="h-px flex-1 bg-gray-100 dark:bg-white/10"></div>
                        </div>
                        <button 
                            wire:click="clearAllLogs('auth')"
                            class="text-[10px] font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 uppercase tracking-tight ml-2"
                        >
                            Clear All
                        </button>
                    </div>
                    <div class="grid gap-2">
                        @foreach($recentLogs['auth'] as $log)
                            <div class="relative flex items-center gap-3 p-3 bg-white dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 group">
                                <div @class([
                                    'h-2 w-2 rounded-full shrink-0',
                                    'bg-success-500' => $log->login_successful,
                                    'bg-danger-500' => !$log->login_successful,
                                ])></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white pr-2">{{ $log->ip_address }}</span>
                                        <div class="flex items-center gap-x-2 shrink-0">
                                            <span class="text-[9px] text-gray-400 font-medium whitespace-nowrap">{{ $log->login_at?->format('H:i') ?? '-' }}</span>
                                            <button 
                                                wire:click="dismissLog('auth', {{ $log->id }})"
                                                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                                                title="Clear"
                                            >
                                                <x-filament::icon icon="heroicon-m-x-mark" class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <p class="truncate text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ str()->limit($log->user_agent, 35) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ \App\Filament\Accounting\Resources\AuthenticationLogResource::getUrl(panel: 'accounting') }}" class="block p-2 text-center text-xs font-semibold text-primary-600 hover:underline">
                            View all security logs
                        </a>
                    </div>
                </div>
            @endif

            @if($notificationsCount === 0 && (!isset($recentLogs['activity']) || count($recentLogs['activity']) === 0))
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-white/5 rounded-full flex items-center justify-center mb-4">
                        <x-filament::icon icon="heroicon-o-bell-slash" class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No new notifications</p>
                </div>
            @endif
        </div>
    </x-filament::modal>
</div>
