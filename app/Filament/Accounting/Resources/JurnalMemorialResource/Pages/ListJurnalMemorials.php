<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use App\Filament\Widgets\JurnalMemorialStatsWidget;
use App\Models\JurnalMemorial;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ListJurnalMemorials extends ListRecords
{
    protected static string $resource = JurnalMemorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Jurnal')
                ->icon('heroicon-o-plus-circle'),

            Actions\Action::make('exportPdf')
                ->label('Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
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
                    $query = JurnalMemorial::query()
                        ->with(['kelompok', 'rekening', 'nomorBantu', 'company'])
                        ->whereBetween('tanggal', [$data['start_date'], $data['end_date']]);

                    if ($data['status'] === 'confirmed') {
                        $query->where('is_confirmed', true);
                    } elseif ($data['status'] === 'pending') {
                        $query->where('is_confirmed', false);
                    }

                    $journals = $query->orderBy('tanggal', 'desc')->get();
                    $company = Auth::user()?->company ?? (object)['name' => 'PDAM PURBALINGGA'];
                    $startDate = date('d/m/Y', strtotime($data['start_date']));
                    $endDate = date('d/m/Y', strtotime($data['end_date']));
                    $status = $data['status'];

                    $pdf = Pdf::loadView('reports.jurnal-memorial', compact('journals', 'company', 'startDate', 'endDate', 'status'));
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'laporan-jurnal-memorial-' . date('Ymd') . '.pdf');
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JurnalMemorialStatsWidget::class,
        ];
    }
}
