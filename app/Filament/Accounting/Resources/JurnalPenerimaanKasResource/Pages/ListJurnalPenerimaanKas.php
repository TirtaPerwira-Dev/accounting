<?php

namespace App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Accounting\Resources\JurnalPenerimaanKasResource;
use App\Filament\Widgets\JurnalPenerimaanKasStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJurnalPenerimaanKas extends ListRecords
{
    protected static string $resource = JurnalPenerimaanKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Jurnal')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),

            Actions\Action::make('export_all_pdf')
                ->label('Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->modalHeading('Filter Laporan PDF')
                ->modalDescription('Pilih filter untuk laporan yang akan di-generate')
                ->modalSubmitActionLabel('Generate PDF')
                ->modalWidth('md')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('dari_tanggal')
                        ->label('Dari Tanggal')
                        ->default(now()->startOfMonth())
                        ->required()
                        ->native(false),
                    \Filament\Forms\Components\DatePicker::make('sampai_tanggal')
                        ->label('Sampai Tanggal')
                        ->default(now()->endOfMonth())
                        ->required()
                        ->native(false)
                        ->afterOrEqual('dari_tanggal'),
                    \Filament\Forms\Components\Select::make('kas_bank_filter')
                        ->label('Filter Kas/Bank')
                        ->options(function () {
                            return \App\Models\NomorBantu::whereHas('rekening', function ($query) {
                                $query->whereHas('kelompok', function ($q) {
                                    $q->where('no_kel', '10');
                                })
                                    ->where(function ($q) {
                                        $q->where('no_rek', 'like', '1101%')
                                            ->orWhere('no_rek', 'like', '1102%');
                                    });
                            })
                                ->with(['rekening.kelompok'])
                                ->get()
                                ->mapWithKeys(fn($item) => [
                                    $item->id => "{$item->rekening->kelompok->no_kel}-{$item->rekening->no_rek}-{$item->no_bantu} - {$item->nm_bantu}"
                                ]);
                        })
                        ->searchable()
                        ->placeholder('Semua Kas/Bank')
                        ->native(false),
                    \Filament\Forms\Components\Select::make('status')
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
                        'dari_tanggal' => $data['dari_tanggal'] ?? null,
                        'sampai_tanggal' => $data['sampai_tanggal'] ?? null,
                        'kas_bank_filter' => $data['kas_bank_filter'] ?? null,
                        'status' => $data['status'] ?? '',
                    ]);
                    
                    $url = route('jurnal-penerimaan-kas.pdf', $params);
                    
                    // Redirect to open in new window using JavaScript
                    $this->js("window.open('$url', '_blank');");
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JurnalPenerimaanKasStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // \App\Filament\Widgets\JurnalPenerimaanKasTableWidget::class,
        ];
    }
}
