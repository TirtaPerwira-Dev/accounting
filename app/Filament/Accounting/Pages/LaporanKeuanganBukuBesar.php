<?php

namespace App\Filament\Accounting\Pages;

use App\Models\Company;
use App\Models\NomorBantu;
use App\Services\SakepReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LaporanKeuanganBukuBesar extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static string $view = 'filament.accounting.pages.laporan-keuangan-v2-buku-besar';

    protected static ?string $navigationLabel = 'Buku Besar';

    protected static ?string $title = 'Laporan Buku Besar';

    protected static ?string $navigationGroup = 'Laporan Keuangan';

    protected static ?int $navigationGroupSort = 4;

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'laporan-keuangan-v2-buku-besar';

    public ?array $data = [];

    public ?array $reportData = null;

    public function mount(): void
    {
        $this->form->fill([
            'company_id' => Company::query()->value('id'),
            'nomor_bantu_id' => null,
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Buku Besar')
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(Company::query()->pluck('name', 'id'))
                            ->required(),

                        Select::make('nomor_bantu_id')
                            ->label('Nomor Bantu')
                            ->options(function () {
                                return NomorBantu::query()
                                    ->with(['kelompok', 'rekening'])
                                    ->get()
                                    ->sortBy(function (NomorBantu $nomorBantu) {
                                        return sprintf(
                                            '%04d-%06d-%03s',
                                            (int) ($nomorBantu->rekening?->kelompok_id ?? 0),
                                            (int) ($nomorBantu->rekening?->no_rek ?? 0),
                                            (string) $nomorBantu->no_bantu
                                        );
                                    })
                                    ->mapWithKeys(function (NomorBantu $nomorBantu) {
                                        $kel = str_pad((string) ($nomorBantu->kelompok?->no_kel ?? ''), 2, '0', STR_PAD_LEFT);
                                        $rek = str_pad((string) ($nomorBantu->rekening?->no_rek ?? ''), 4, '0', STR_PAD_LEFT);
                                        $bantu = str_pad((string) $nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT);
                                        $label = trim($kel . '-' . $rek . '-' . $bantu . ' | ' . ($nomorBantu->nm_bantu ?? '-'));

                                        return [$nomorBantu->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
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
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        try {
            $state = $this->form->getState();
            $service = new SakepReportService();

            $this->reportData = $service->getGeneralLedger(
                (int) $state['nomor_bantu_id'],
                $state['from_date'],
                $state['to_date']
            );

            Notification::make()
                ->title('Laporan buku besar berhasil dibuat')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal membuat laporan buku besar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
