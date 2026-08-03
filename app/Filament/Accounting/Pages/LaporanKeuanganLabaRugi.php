<?php

namespace App\Filament\Accounting\Pages;

use App\Models\Company;
use App\Services\SakepReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LaporanKeuanganLabaRugi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.accounting.pages.laporan-keuangan-v2-laba-rugi';

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static ?string $title = 'Laporan Laba Rugi';

    protected static ?string $navigationGroup = 'Laporan Keuangan';

    protected static ?int $navigationGroupSort = 4;

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'laporan-keuangan-v2-laba-rugi';

    public ?array $data = [];

    public ?array $reportData = null;

    public function mount(): void
    {
        $this->form->fill([
            'company_id' => Company::query()->value('id'),
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laba Rugi')
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(Company::query()->pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('from_date')
                            ->label('Dari Tanggal')
                            ->native(false)
                            ->required(),

                        DatePicker::make('to_date')
                            ->label('Sampai Tanggal')
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        try {
            $state = $this->form->getState();
            $service = new SakepReportService();

            $this->reportData = $service->generateIncomeStatement(
                (int) $state['company_id'],
                $state['from_date'],
                $state['to_date']
            );

            Notification::make()
                ->title('Laporan laba rugi berhasil dibuat')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal membuat laporan laba rugi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
