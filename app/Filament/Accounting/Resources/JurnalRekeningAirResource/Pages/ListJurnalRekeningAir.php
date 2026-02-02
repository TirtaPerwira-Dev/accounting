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

            // Export PDF Action
            Actions\Action::make('exportPdf')
                ->label('Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->native(false)
                        ->default(now()->startOfMonth())
                        ->required()
                        ->maxDate(now()),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->native(false)
                        ->default(now())
                        ->required()
                        ->maxDate(now())
                        ->afterOrEqual('start_date'),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            '' => 'Semua',
                            'confirmed' => 'Sudah Dikonfirmasi',
                            'pending' => 'Belum Dikonfirmasi',
                        ])
                        ->default(''),
                ])
                ->action(function (array $data) {
                    $url = route('jurnal-rekening-air.pdf', [
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'status' => $data['status'] ?? '',
                    ]);
                    
                    // Dispatch browser event to open URL in new tab
                    $this->dispatch('open-url-in-new-tab', url: $url);
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
