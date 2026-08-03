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

class LaporanKeuanganNeraca extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static string $view = 'filament.accounting.pages.laporan-keuangan-v2-neraca';

    protected static ?string $navigationLabel = 'Neraca';

    protected static ?string $title = 'Laporan Neraca';

    protected static ?string $navigationGroup = 'Laporan Keuangan';

    protected static ?int $navigationGroupSort = 4;

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'laporan-keuangan-v2-neraca';

    public ?array $data = [];

    public ?array $reportData = null;

    public function mount(): void
    {
        $this->form->fill([
            'company_id' => Company::query()->value('id'),
            'as_of_date' => now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Neraca')
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(Company::query()->pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('as_of_date')
                            ->label('Per Tanggal')
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        try {
            $state = $this->form->getState();
            $service = new SakepReportService();

            $this->reportData = $service->generateBalanceSheet(
                (int) $state['company_id'],
                $state['as_of_date']
            );

            Notification::make()
                ->title('Laporan neraca berhasil dibuat')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal membuat laporan neraca')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
