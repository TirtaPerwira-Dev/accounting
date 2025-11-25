<?php

namespace App\Filament\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Resources\JurnalPenerimaanKasResource;
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
                        ->label('Filter Kas/Bank (Opsional)')
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
                        ->placeholder('Semua Kas/Bank'),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\JurnalPenerimaanKas::with(['kasBank.rekening.kelompok'])
                        ->whereDate('tanggal', '>=', $data['dari_tanggal'])
                        ->whereDate('tanggal', '<=', $data['sampai_tanggal']);

                    if (!empty($data['kas_bank_filter'])) {
                        $query->where('kas_bank_id', $data['kas_bank_filter']);
                    }

                    $records = $query->get();
                    $title = 'Laporan JPK ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d/m/Y') .
                        ' - ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');

                    return response()->streamDownload(function () use ($records, $title) {
                        echo \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.jurnal-penerimaan-kas-bulk', [
                            'records' => $records,
                            'title' => $title
                        ])->stream();
                    }, 'JPK-' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('Y-m-d') .
                        '_' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('Y-m-d') . '.pdf');
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
