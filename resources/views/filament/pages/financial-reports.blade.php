<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        <!-- Report Display Section -->
        @if($this->getReportData())
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{
                            match($this->getReportType()) {
                                'trial_balance' => 'Trial Balance',
                                'balance_sheet' => 'Balance Sheet (Neraca)',
                                'income_statement' => 'Income Statement (Laba Rugi)',
                                'cash_flow' => 'Cash Flow Statement (Arus Kas)',
                                'general_ledger' => 'General Ledger',
                                default => 'Financial Report'
                            }
                        }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        @if(in_array($this->getReportType(), ['trial_balance', 'balance_sheet']))
                            As of {{ \Carbon\Carbon::parse($this->getReportData()['as_of_date'] ?? now())->format('d F Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($this->getReportData()['period_start'] ?? now())->format('d M Y') }} -
                            {{ \Carbon\Carbon::parse($this->getReportData()['period_end'] ?? now())->format('d M Y') }}
                        @endif
                    </p>
                </div>

                <div class="p-6">
                    @switch($this->getReportType())
                        @case('trial_balance')
                            @include('filament.reports.trial-balance', ['data' => $this->getReportData()])
                            @break

                        @case('balance_sheet')
                            @include('filament.reports.balance-sheet', ['data' => $this->getReportData()])
                            @break

                        @case('income_statement')
                            @include('filament.reports.income-statement', ['data' => $this->getReportData()])
                            @break

                        @case('cash_flow')
                            @include('filament.reports.cash-flow', ['data' => $this->getReportData()])
                            @break

                        @case('general_ledger')
                            @include('filament.reports.general-ledger', ['data' => $this->getReportData()])
                            @break

                        @default
                            <p class="text-gray-500">Report type not supported</p>
                    @endswitch
                </div>

                <!-- Export Buttons -->
                <div class="px-6 pb-6">
                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="exportPdf"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-sm"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            Ekspor PDF
                        </button>

                        <button
                            wire:click="exportExcel"
                            class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 shadow-sm"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Ekspor Excel
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('openDownloadLink', (url) => {
                window.open(url, '_blank');
            });
        });
    </script>
</x-filament-panels::page>
