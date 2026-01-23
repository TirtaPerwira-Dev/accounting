<?php

namespace App\Filament\Accounting\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Accounting\Resources\JurnalRekeningAirResource;
use App\Filament\Widgets\JurnalRekeningAirStatsWidget;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

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

            // Export PDF Action
            Actions\Action::make('exportPdf')
                ->label('Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->modalHeading('Filter Laporan PDF')
                ->modalDescription('Pilih filter untuk laporan yang akan di-generate')
                ->modalSubmitActionLabel('Generate PDF')
                ->modalWidth('md')
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->native(false)
                        ->default(now()->startOfMonth())
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->native(false)
                        ->default(now()->endOfMonth())
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            '' => 'Semua',
                            'confirmed' => 'Sudah Dikonfirmasi',
                            'pending' => 'Belum Dikonfirmasi',
                        ])
                        ->default('')
                        ->native(false),
                ])
                ->action(function (array $data) {
                    // Build URL with query parameters
                    $params = array_filter([
                        'start_date' => $data['start_date'] ?? null,
                        'end_date' => $data['end_date'] ?? null,
                        'status' => $data['status'] ?? '',
                    ]);
                    
                    $url = route('jurnal-rekening-air.pdf', $params);
                    
                    // Redirect to open in new window using JavaScript
                    $this->js("window.open('$url', '_blank');");
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
}
