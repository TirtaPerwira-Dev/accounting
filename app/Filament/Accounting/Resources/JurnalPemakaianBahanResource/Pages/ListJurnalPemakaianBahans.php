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
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->native(false),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Akhir')
                        ->required()
                        ->default(now()->endOfMonth())
                        ->native(false),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'semua' => 'Semua',
                            'confirmed' => 'Dikonfirmasi',
                            'pending' => 'Pending',
                        ])
                        ->default('semua')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $query = JurnalPemakaianBahan::query()
                        ->with(['kelompokDebit', 'rekeningDebit', 'nomorBantuDebit', 'kelompokKredit', 'rekeningKredit', 'nomorBantuKredit', 'company'])
                        ->whereBetween('tanggal', [$data['start_date'], $data['end_date']]);

                    if ($data['status'] === 'confirmed') {
                        $query->where('is_confirmed', true);
                    } elseif ($data['status'] === 'pending') {
                        $query->where('is_confirmed', false);
                    }

                    $journals = $query->orderBy('tanggal', 'desc')->get();
                    $company = auth()->user()?->company ?? (object)['name' => 'PDAM PURBALINGGA'];
                    $startDate = date('d/m/Y', strtotime($data['start_date']));
                    $endDate = date('d/m/Y', strtotime($data['end_date']));
                    $status = $data['status'];

                    $pdf = Pdf::loadView('reports.jurnal-pemakaian-bahan', compact('journals', 'company', 'startDate', 'endDate', 'status'));

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'laporan-jurnal-pemakaian-bahan-' . date('Ymd') . '.pdf');
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
