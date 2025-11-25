<?php

namespace App\Filament\Resources\JurnalPembelianResource\Pages;

use App\Filament\Resources\JurnalPembelianResource;
use App\Models\NomorBantu;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ListJurnalPembelians extends ListRecords
{
    protected static string $resource = JurnalPembelianResource::class;

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        // Tampilkan semua records (tidak di-group)
        return parent::getTableQuery();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JurnalPembelianResource\Widgets\JurnalPembelianStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Jurnal')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),

            Actions\Action::make('exportPdf')
                ->label('Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('dari_tanggal')
                                ->label('Dari Tanggal')
                                ->default(Carbon::now()->startOfMonth())
                                ->required(),

                            Forms\Components\DatePicker::make('sampai_tanggal')
                                ->label('Sampai Tanggal')
                                ->default(Carbon::now())
                                ->required(),
                        ]),

                    Forms\Components\Select::make('kode_hutang')
                        ->label('Kode Hutang (Opsional)')
                        ->placeholder('Semua akun hutang')
                        ->options(function () {
                            return NomorBantu::with(['rekening.kelompok'])
                                ->get()
                                ->filter(function ($item) {
                                    $kelompok = $item->rekening->kelompok->no_kel;
                                    return in_array($kelompok, ['50', '60', '62']); // Kewajiban
                                })
                                ->mapWithKeys(function ($n) {
                                    $code = $n->rekening->kelompok->no_kel .
                                        $n->rekening->no_rek .
                                        str_pad($n->no_bantu, 2, '0', STR_PAD_LEFT);
                                    return [$n->id => "[$code] {$n->nm_bantu}"];
                                });
                        })
                        ->searchable(),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'all' => 'Semua',
                            'confirmed' => 'Dikonfirmasi',
                            'pending' => 'Belum Konfirmasi',
                        ])
                        ->default('all'),
                ])
                ->modalHeading('Filter Laporan Jurnal Pembelian')
                ->modalSubmitActionLabel('Generate PDF')
                ->action(function (array $data) {
                    return $this->generatePdfReport($data);
                }),
        ];
    }

    protected function generatePdfReport(array $filters): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = $this->getFilteredQuery($filters);
        $data = $query->with(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek'])
            ->orderBy('tanggal', 'desc')
            ->get();

        $totalAmount = $data->sum('rp');
        $period = Carbon::parse($filters['dari_tanggal'])->format('d M Y') . ' - ' .
            Carbon::parse($filters['sampai_tanggal'])->format('d M Y');

        $pdf = Pdf::loadView('reports.jurnal-pembelian', [
            'data' => $data,
            'filters' => $filters,
            'period' => $period,
            'totalAmount' => $totalAmount,
            'generatedAt' => now()->format('d M Y H:i'),
        ]);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'laporan-jurnal-pembelian-' . now()->format('Y-m-d-H-i-s') . '.pdf'
        );
    }

    protected function getFilteredQuery(array $filters)
    {
        $query = JurnalPembelianResource::getEloquentQuery();

        // Filter tanggal
        $query->whereBetween('tanggal', [
            $filters['dari_tanggal'],
            $filters['sampai_tanggal']
        ]);

        // Filter kode hutang
        if (!empty($filters['kode_hutang'])) {
            $query->where('nomor_bantu_kredit_id', $filters['kode_hutang']);
        }

        // Filter status
        if ($filters['status'] === 'confirmed') {
            $query->where('is_confirmed', true);
        } elseif ($filters['status'] === 'pending') {
            $query->where('is_confirmed', false);
        }

        return $query;
    }
}
