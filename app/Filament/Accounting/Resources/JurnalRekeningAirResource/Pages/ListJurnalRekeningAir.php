<?php

namespace App\Filament\Accounting\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Accounting\Resources\JurnalRekeningAirResource;
use App\Filament\Widgets\JurnalRekeningAirStatsWidget;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;

use function Filament\Support\is_app_url;

class ListJurnalRekeningAir extends ListRecords
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Jurnal')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),

            Actions\Action::make('exportPdf')
                ->label('Report Submitted')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('dari_tanggal')
                        ->label('Dari Tanggal')
                        ->native(false)
                        ->default(now()->startOfMonth())
                        ->required(),
                    Forms\Components\DatePicker::make('sampai_tanggal')
                        ->label('Sampai Tanggal')
                        ->native(false)
                        ->default(now())
                        ->required()
                        ->afterOrEqual('dari_tanggal'),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'all' => 'Semua',
                            'confirmed' => 'Dikonfirmasi',
                            'pending' => 'Belum Konfirmasi',
                        ])
                        ->default('all'),
                ])
                ->modalWidth('md')
                ->modalHeading('Filter Laporan Jurnal Rekening Air')
                ->modalSubmitActionLabel('Cetak PDF')
                ->action(function (array $data) {
                    $url = route('report.periodic-pdf', [
                        'type' => 'rekening_air',
                        'dari_tanggal' => $data['dari_tanggal'],
                        'sampai_tanggal' => $data['sampai_tanggal'],
                        'status' => $data['status'] ?? 'all',
                    ]);

                    $this->js('window.open("' . $url . '", "_blank")');
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Jurnal Rekening Air & Non Air';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JurnalRekeningAirStatsWidget::class,
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.pages.list-footer-script');
    }
}
