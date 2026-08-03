<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit="generateReport" class="space-y-4">
                {{ $this->form }}
                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-play" color="primary">
                        Tampilkan Laba Rugi
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        @if($reportData)
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-filament::card>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide">Pendapatan</h3>
                    <div class="space-y-2">
                        @foreach($reportData['revenues'] as $item)
                            <div class="flex items-start justify-between text-sm">
                                <span>{{ $item['sakep_code'] }} - {{ $item['account_name'] }}</span>
                                <span class="font-semibold">{{ number_format($item['amount'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide">Beban</h3>
                    <div class="space-y-2">
                        @foreach($reportData['expenses'] as $item)
                            <div class="flex items-start justify-between text-sm">
                                <span>{{ $item['sakep_code'] }} - {{ $item['account_name'] }}</span>
                                <span class="font-semibold">{{ number_format($item['amount'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
            </div>

            <x-filament::card>
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                    <div class="rounded-lg border p-4">
                        <div class="text-gray-500">Total Pendapatan</div>
                        <div class="text-lg font-bold">{{ number_format($reportData['total_revenues'], 0, ',', '.') }}</div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-gray-500">Total Beban</div>
                        <div class="text-lg font-bold">{{ number_format($reportData['total_expenses'], 0, ',', '.') }}</div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-gray-500">Laba Bersih</div>
                        <div class="text-lg font-bold {{ $reportData['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($reportData['net_income'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
