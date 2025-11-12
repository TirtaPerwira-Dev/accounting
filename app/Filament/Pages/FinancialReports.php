<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Services\SakepReportService;
use App\Models\Company;
use App\Models\NomorBantu;
use Carbon\Carbon;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

class FinancialReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $title = 'Laporan Keuangan';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static ?string $navigationGroup = '4. Laporan Keuangan';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.financial-reports';

    public ?array $data = [];
    public ?array $reportData = null;
    public ?string $reportType = null;

    public function mount(): void
    {
        $this->form->fill([
            'company_id' => Company::first()?->id,
            'report_type' => 'balance_sheet',
            'as_of_date' => now()->toDateString(),
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Parameter Laporan')
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->live(),

                        Select::make('report_type')
                            ->label('Jenis Laporan')
                            ->options([
                                'trial_balance' => 'Neraca Saldo',
                                'balance_sheet' => 'Neraca',
                                'income_statement' => 'Laporan Laba Rugi',
                                'cash_flow' => 'Laporan Arus Kas',
                                'general_ledger' => 'Buku Besar',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn() => $this->reportData = null),

                        DatePicker::make('as_of_date')
                            ->label('Per Tanggal')
                            ->native(false)
                            ->default(now())
                            ->visible(fn(callable $get) => in_array($get('report_type'), ['trial_balance', 'balance_sheet']))
                            ->required(),

                        DatePicker::make('from_date')
                            ->label('Dari Tanggal')
                            ->native(false)
                            ->default('2024-01-01') // Set default to cover sample data
                            ->visible(fn(callable $get) => in_array($get('report_type'), ['income_statement', 'cash_flow', 'general_ledger']))
                            ->required(),

                        DatePicker::make('to_date')
                            ->label('Sampai Tanggal')
                            ->native(false)
                            ->default(now())
                            ->visible(fn(callable $get) => in_array($get('report_type'), ['income_statement', 'cash_flow', 'general_ledger']))
                            ->required(),

                        Select::make('nomor_bantu_id')
                            ->label('Pilih Nomor Bantu')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) return [];

                                // Get company's accounting standard ID first
                                $company = Company::find($companyId);
                                if (!$company || !$company->accounting_standard_id) return [];

                                $nomorBantuData = NomorBantu::with(['kelompok', 'rekening'])
                                    ->whereHas('kelompok', function ($q) use ($company) {
                                        $q->where('standard_id', $company->accounting_standard_id);
                                    })
                                    ->get()
                                    ->sortBy([
                                        ['kelompok.no_kel', 'asc'],
                                        ['rekening.no_rek', 'asc'],
                                        ['no_bantu', 'asc']
                                    ]);

                                // Group by kelompok (section) with proper ordering
                                $grouped = [];
                                foreach (
                                    $nomorBantuData->groupBy(function ($item) {
                                        return sprintf('%02d - %s', $item->kelompok->no_kel, $item->kelompok->nama_kel);
                                    }) as $kelompokKey => $accounts
                                ) {
                                    $grouped[$kelompokKey] = $accounts->mapWithKeys(function ($nomorBantu) {
                                        $code = sprintf(
                                            '%s%s%02d',
                                            $nomorBantu->kelompok->no_kel,
                                            $nomorBantu->rekening->no_rek,
                                            $nomorBantu->no_bantu
                                        );
                                        return [$nomorBantu->id => $code . ' - ' . $nomorBantu->nm_bantu];
                                    })->toArray();
                                }

                                return $grouped;
                            })
                            ->visible(fn(callable $get) => $get('report_type') === 'general_ledger')
                            ->searchable()
                            ->required()
                            ->validationAttribute('pilih Nomor Bantu')
                            ->requiredWith('report_type')
                            ->rules([
                                'required_if:report_type,general_ledger'
                            ])
                            ->validationMessages([
                                'required_if' => 'Nomor Bantu belum dipilih. Silahkan memilih nomor bantu yang akan dibuat laporan.',
                                'required' => 'Nomor Bantu belum dipilih. Silahkan memilih nomor bantu yang akan dibuat laporan.',
                            ]),
                    ])
                    ->columns(3)
                    ->footerActions([
                        Action::make('generate')
                            ->label('Buat Laporan')
                            ->action('generateReport')
                            ->color('primary'),
                    ]),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        try {
            $data = $this->form->getState();
            $reportService = new SakepReportService();

            switch ($data['report_type']) {
                case 'trial_balance':
                    $this->reportData = $reportService->generateTrialBalance(
                        $data['company_id'],
                        $data['as_of_date']
                    );
                    break;

                case 'balance_sheet':
                    $this->reportData = $reportService->generateBalanceSheet(
                        $data['company_id'],
                        $data['as_of_date']
                    );
                    break;

                case 'income_statement':
                    $this->reportData = $reportService->generateIncomeStatement(
                        $data['company_id'],
                        $data['from_date'],
                        $data['to_date']
                    );
                    break;

                case 'cash_flow':
                    $this->reportData = $reportService->generateCashFlowStatement(
                        $data['company_id'],
                        $data['from_date'],
                        $data['to_date']
                    );
                    break;

                case 'general_ledger':
                    $this->reportData = $reportService->getGeneralLedger(
                        $data['nomor_bantu_id'],
                        $data['from_date'],
                        $data['to_date']
                    );
                    break;
            }

            $this->reportType = $data['report_type'];

            Notification::make()
                ->title('Laporan berhasil dibuat')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error membuat laporan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportPdf()
    {
        try {
            if (!$this->reportData || !$this->reportType) {
                Notification::make()
                    ->title('Error')
                    ->body('Tidak ada data laporan untuk diekspor')
                    ->danger()
                    ->send();
                return;
            }

            // Store report data in session for the download route
            session([
                'export_report_data' => $this->reportData,
                'export_report_type' => $this->reportType,
                'export_company_id' => $this->data['company_id'] ?? null,
                'export_period_data' => $this->data
            ]);

            // Use JavaScript to open download link
            $this->dispatch('openDownloadLink', route('report.export.pdf'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Ekspor PDF')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportExcel(): void
    {
        try {
            if (!$this->reportData || !$this->reportType) {
                Notification::make()
                    ->title('Error')
                    ->body('Tidak ada data laporan untuk diekspor')
                    ->danger()
                    ->send();
                return;
            }

            // Store report data in session for the download route
            session([
                'export_report_data' => $this->reportData,
                'export_report_type' => $this->reportType,
                'export_company_id' => $this->data['company_id'] ?? null,
                'export_period_data' => $this->data
            ]);

            // Use JavaScript to open download link
            $this->dispatch('openDownloadLink', route('report.export.excel'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Ekspor Excel')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getReportData(): ?array
    {
        return $this->reportData;
    }

    public function getReportType(): ?string
    {
        return $this->reportType;
    }

    private function getPeriodText(): string
    {
        if (in_array($this->reportType, ['trial_balance', 'balance_sheet'])) {
            $date = $this->data['as_of_date'] ?? now()->toDateString();
            return 'Per ' . Carbon::parse($date)->format('d F Y');
        } else {
            $fromDate = $this->data['from_date'] ?? now()->startOfMonth()->toDateString();
            $toDate = $this->data['to_date'] ?? now()->endOfMonth()->toDateString();
            return Carbon::parse($fromDate)->format('d M Y') . ' - ' . Carbon::parse($toDate)->format('d M Y');
        }
    }
}
