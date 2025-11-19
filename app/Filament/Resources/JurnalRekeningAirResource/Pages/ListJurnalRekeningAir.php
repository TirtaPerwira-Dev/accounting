<?php

namespace App\Filament\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Resources\JurnalRekeningAirResource;
use App\Filament\Widgets\JurnalRekeningAirStatsWidget;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListJurnalRekeningAir extends ListRecords
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Jurnal')
                ->icon('heroicon-o-plus'),

            // Export PDF Action
            Actions\Action::make('exportPdf')
                ->label('Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->native(false),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->native(false),
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
                    $query = static::$resource::getEloquentQuery();

                    if ($data['start_date']) {
                        $query->whereDate('tanggal', '>=', $data['start_date']);
                    }

                    if ($data['end_date']) {
                        $query->whereDate('tanggal', '<=', $data['end_date']);
                    }

                    if ($data['status'] === 'confirmed') {
                        $query->where('is_confirmed', true);
                    } elseif ($data['status'] === 'pending') {
                        $query->where('is_confirmed', false);
                    }

                    $journals = $query->with([
                        'company',
                        'kelompokKredit',
                        'rekeningKredit',
                        'nomorBantuKredit',
                        'kodeProyek'
                    ])->orderBy('tanggal', 'desc')->get();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-rekening-air', [
                        'journals' => $journals,
                        'company' => Auth::user()?->company ?? \App\Models\Company::first(),
                        'startDate' => $data['start_date'],
                        'endDate' => $data['end_date'],
                        'status' => $data['status'],
                    ]);

                    $filename = 'jurnal-rekening-air-' . now()->format('Y-m-d-His') . '.pdf';

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename);
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
