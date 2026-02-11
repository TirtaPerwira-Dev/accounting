<?php

namespace App\Filament\Accounting\Resources\JurnalPemakaianBahanResource\Pages;

use App\Filament\Accounting\Resources\JurnalPemakaianBahanResource;
use App\Filament\Widgets\JurnalPemakaianBahanStatsWidget;
use App\Models\JurnalPemakaianBahan;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;

class ListJurnalPemakaianBahans extends ListRecords
{
    protected static string $resource = JurnalPemakaianBahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Jurnal')
                ->icon('heroicon-o-plus-circle'),

            Actions\Action::make('exportPdf')
                ->label('Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('dari_tanggal')
                        ->label('Dari Tanggal')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->native(false),
                    Forms\Components\DatePicker::make('sampai_tanggal')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->default(now())
                        ->native(false),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'all' => 'Semua',
                            'confirmed' => 'Dikonfirmasi',
                            'pending' => 'Belum Konfirmasi',
                        ])
                        ->default('all')
                        ->required(),
                ])
                ->modalWidth('md')
                ->modalHeading('Filter Laporan Jurnal Pemakaian Bahan')
                ->modalSubmitActionLabel('Cetak PDF')
                ->action(function (array $data) {
                    $url = route('report.periodic-pdf', [
                        'type' => 'pemakaian_bahan',
                        'dari_tanggal' => $data['dari_tanggal'],
                        'sampai_tanggal' => $data['sampai_tanggal'],
                        'status' => $data['status'] ?? 'all',
                    ]);
                    
                    $this->js('window.open("' . $url . '", "_blank")');
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JurnalPemakaianBahanStatsWidget::class,
        ];
    }
}
