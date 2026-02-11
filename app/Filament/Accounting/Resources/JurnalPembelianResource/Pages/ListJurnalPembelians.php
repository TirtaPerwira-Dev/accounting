<?php

namespace App\Filament\Accounting\Resources\JurnalPembelianResource\Pages;

use App\Filament\Accounting\Resources\JurnalPembelianResource;
use App\Models\NomorBantu;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ListJurnalPembelians extends ListRecords
{
    protected static string $resource = JurnalPembelianResource::class;

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        // Tampilkan per detail item, bukan per header
        return \App\Models\JurnalPembelianDetail::query()
            ->select([
                'jurnal_pembelian_details.*',
                'jurnal_pembelians.tanggal',
                'jurnal_pembelians.no_reff',
                'jurnal_pembelians.is_confirmed',
                'jurnal_pembelians.is_posted',
                'jurnal_pembelians.confirmed_at',
                'jurnal_pembelians.confirmed_by',
                'jurnal_pembelians.nomor_bantu_kredit_id as header_nomor_bantu_kredit_id',
            ])
            ->join('jurnal_pembelians', 'jurnal_pembelian_details.jurnal_pembelian_id', '=', 'jurnal_pembelians.id')
            ->with([
                'jurnalPembelian.nomorBantuKredit.rekening.kelompok',
                'kelompokDebit',
                'rekeningDebit',
                'nomorBantuDebit.rekening.kelompok',
                'kodeProyek',
            ])
            ->whereNull('jurnal_pembelians.deleted_at');
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
                ->modalWidth('md')
                ->modalSubmitActionLabel('Cetak PDF')
                ->action(function (array $data) {
                    $url = route('report.periodic-pdf', [
                        'type' => 'pembelian',
                        'dari_tanggal' => $data['dari_tanggal'],
                        'sampai_tanggal' => $data['sampai_tanggal'],
                        'kode_hutang' => $data['kode_hutang'] ?? null,
                        'status' => $data['status'] ?? 'all',
                    ]);
                    
                    Notification::make()
                        ->title('PDF sedang diproses')
                        ->body('Laporan PDF akan dibuka di tab baru')
                        ->success()
                        ->send();
                    
                    $this->js('window.open("' . $url . '", "_blank")');
                }),
        ];
    }

    protected function generatePdfReport(array $filters): \Illuminate\Http\Response
    {
        $query = $this->getFilteredQuery($filters);
        $data = $query->with(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek'])
            ->orderBy('tanggal', 'desc')
            ->get();

        // Clean data to ensure proper UTF-8 encoding
        $data->each(function ($item) {
            if (isset($item->bukti_item)) {
                $item->bukti_item = mb_convert_encoding($item->bukti_item, 'UTF-8', 'UTF-8');
            }
            if (isset($item->keterangan)) {
                $item->keterangan = mb_convert_encoding($item->keterangan, 'UTF-8', 'UTF-8');
            }
            if (isset($item->nama_akun_kredit)) {
                $item->nama_akun_kredit = mb_convert_encoding($item->nama_akun_kredit, 'UTF-8', 'UTF-8');
            }
            if (isset($item->nama_akun_debit)) {
                $item->nama_akun_debit = mb_convert_encoding($item->nama_akun_debit, 'UTF-8', 'UTF-8');
            }
        });

        $totalAmount = $data->sum('rp');
        $period = Carbon::parse($filters['dari_tanggal'])->format('d M Y') . ' - ' .
            Carbon::parse($filters['sampai_tanggal'])->format('d M Y');

        $pdf = Pdf::loadView('reports.jurnal-pembelian', [
            'data' => $data,
            'filters' => $filters,
            'period' => $period,
            'totalAmount' => $totalAmount,
            'generatedAt' => now()->format('d M Y H:i'),
        ])->setPaper('a4', 'portrait')
          ->setOption('isHtml5ParserEnabled', true)
          ->setOption('isRemoteEnabled', true);

        // Stream PDF untuk preview di browser (bukan download langsung)
        $filename = 'laporan-jurnal-pembelian-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public');
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
