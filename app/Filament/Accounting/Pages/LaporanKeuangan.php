<?php

namespace App\Filament\Accounting\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use App\Models\JurnalRekeningAir;
use App\Models\JurnalPemakaianBahan;
use App\Models\JurnalMemorial;
use App\Models\JurnalPembelian;
use App\Models\JurnalBayarKasBank;
use App\Models\JurnalPenerimaanKas;
use App\Models\Kelompok;
use App\Models\SaldoAwalRekening;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanKeuangan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.accounting.pages.laporan-keuangan';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?string $title = 'Laporan Keuangan';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    public ?array $reportData = null;
    public ?string $reportType = null;

    public function mount(): void
    {
        $this->form->fill([
            'report_type' => 'neraca',
            'periode_start' => now()->startOfYear()->format('Y-m-d'),
            'periode_end' => now()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->schema([
                        Select::make('report_type')
                            ->label('Jenis Laporan')
                            ->options([
                                'neraca' => 'Neraca (Balance Sheet)',
                                'laba_rugi' => 'Laba Rugi (Income Statement)',
                                'arus_kas' => 'Arus Kas (Cash Flow)',
                                'perubahan_modal' => 'Perubahan Modal (Equity Statement)',
                                'buku_besar' => 'Buku Besar',
                                'trial_balance' => 'Neraca Saldo (Trial Balance)',
                            ])
                            ->required()
                            ->reactive()
                            ->default('neraca'),

                        DatePicker::make('periode_start')
                            ->label('Periode Awal')
                            ->required()
                            ->default(now()->startOfYear()),

                        DatePicker::make('periode_end')
                            ->label('Periode Akhir')
                            ->required()
                            ->default(now()),

                        Select::make('kelompok_filter')
                            ->label('Filter Kelompok (Opsional)')
                            ->options(Kelompok::pluck('nama_kel', 'id'))
                            ->searchable()
                            ->visible(fn($get) => in_array($get('report_type'), ['buku_besar', 'trial_balance'])),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn() => $this->reportData !== null)
                ->action('exportPdf'),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->visible(fn() => $this->reportData !== null)
                ->action('exportExcel'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Forms\Components\Actions\Action::make('generate')
                ->label('Generate Laporan')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        $this->reportType = $data['report_type'];

        match ($this->reportType) {
            'neraca' => $this->reportData = $this->generateNeraca($data),
            'laba_rugi' => $this->reportData = $this->generateLabaRugi($data),
            'arus_kas' => $this->reportData = $this->generateArusKas($data),
            'perubahan_modal' => $this->reportData = $this->generatePerubahanModal($data),
            'buku_besar' => $this->reportData = $this->generateBukuBesar($data),
            'trial_balance' => $this->reportData = $this->generateTrialBalance($data),
            default => $this->reportData = [],
        };

        $this->dispatch('report-generated');
    }

    protected function generateNeraca(array $filters): array
    {
        $periodeEnd = Carbon::parse($filters['periode_end']);
        $tahun = $periodeEnd->year;

        // Ambil semua transaksi sampai periode akhir
        $transaksi = $this->getAllTransactions(null, $periodeEnd);

        // Kelompokkan berdasarkan kelompok akun
        $aktiva = [];
        $pasiva = [];

        // Kelompok 10-40 = Aktiva
        $aktivaKelompok = Kelompok::where('kel', 1)->get();
        foreach ($aktivaKelompok as $kelompok) {
            // Saldo = Saldo Awal + Mutasi
            $saldoAwal = $this->getSaldoAwalKelompok($kelompok->id, $tahun);
            $mutasi = $this->hitungSaldoKelompok($kelompok->id, $transaksi);
            $saldoAkhir = $saldoAwal + $mutasi;
            
            if ($saldoAkhir != 0) {
                $aktiva[] = [
                    'kode' => $kelompok->no_kel,
                    'nama' => $kelompok->nama_kel,
                    'saldo' => $saldoAkhir,
                ];
            }
        }

        // Kelompok 50-70 = Pasiva
        $pasivaKelompok = Kelompok::where('kel', 2)->get();
        foreach ($pasivaKelompok as $kelompok) {
            $saldoAwal = $this->getSaldoAwalKelompok($kelompok->id, $tahun);
            $mutasi = $this->hitungSaldoKelompok($kelompok->id, $transaksi);
            $saldoAkhir = $saldoAwal + $mutasi;
            
            if ($saldoAkhir != 0) {
                $pasiva[] = [
                    'kode' => $kelompok->no_kel,
                    'nama' => $kelompok->nama_kel,
                    'saldo' => $saldoAkhir,
                ];
            }
        }

        return [
            'title' => 'NERACA',
            'periode' => $periodeEnd->format('d F Y'),
            'aktiva' => $aktiva,
            'pasiva' => $pasiva,
            'total_aktiva' => array_sum(array_column($aktiva, 'saldo')),
            'total_pasiva' => array_sum(array_column($pasiva, 'saldo')),
        ];
    }

    protected function generateLabaRugi(array $filters): array
    {
        $periodeStart = Carbon::parse($filters['periode_start']);
        $periodeEnd = Carbon::parse($filters['periode_end']);

        $transaksi = $this->getAllTransactions($periodeStart, $periodeEnd);

        // Kelompok 80-88 = Pendapatan
        $pendapatan = [];
        $pendapatanKelompok = Kelompok::whereIn('kel', [3])->get();
        foreach ($pendapatanKelompok as $kelompok) {
            $saldo = $this->hitungSaldoKelompok($kelompok->id, $transaksi);
            if ($saldo != 0) {
                $pendapatan[] = [
                    'kode' => $kelompok->no_kel,
                    'nama' => $kelompok->nama_kel,
                    'saldo' => abs($saldo), // Pendapatan kredit, tampilkan positif
                ];
            }
        }

        // Kelompok 91-98 = Beban
        $beban = [];
        $bebanKelompok = Kelompok::whereIn('kel', [4, 5, 6])->get();
        foreach ($bebanKelompok as $kelompok) {
            $saldo = $this->hitungSaldoKelompok($kelompok->id, $transaksi);
            if ($saldo != 0) {
                $beban[] = [
                    'kode' => $kelompok->no_kel,
                    'nama' => $kelompok->nama_kel,
                    'saldo' => abs($saldo),
                ];
            }
        }

        $totalPendapatan = array_sum(array_column($pendapatan, 'saldo'));
        $totalBeban = array_sum(array_column($beban, 'saldo'));
        $labaRugi = $totalPendapatan - $totalBeban;

        return [
            'title' => 'LAPORAN LABA RUGI',
            'periode' => $periodeStart->format('d F Y') . ' - ' . $periodeEnd->format('d F Y'),
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'total_pendapatan' => $totalPendapatan,
            'total_beban' => $totalBeban,
            'laba_rugi' => $labaRugi,
            'status' => $labaRugi >= 0 ? 'LABA' : 'RUGI',
        ];
    }

    protected function generateArusKas(array $filters): array
    {
        $periodeStart = Carbon::parse($filters['periode_start']);
        $periodeEnd = Carbon::parse($filters['periode_end']);

        // Arus kas dari operasi, investasi, dan pendanaan
        return [
            'title' => 'LAPORAN ARUS KAS',
            'periode' => $periodeStart->format('d F Y') . ' - ' . $periodeEnd->format('d F Y'),
            'operasi' => [],
            'investasi' => [],
            'pendanaan' => [],
        ];
    }

    protected function generatePerubahanModal(array $filters): array
    {
        return [
            'title' => 'LAPORAN PERUBAHAN MODAL',
            'periode' => '',
            'data' => [],
        ];
    }

    protected function generateBukuBesar(array $filters): array
    {
        $periodeStart = Carbon::parse($filters['periode_start']);
        $periodeEnd = Carbon::parse($filters['periode_end']);
        $kelompokFilter = $filters['kelompok_filter'] ?? null;

        // Ambil semua transaksi dalam periode
        $transaksi = $this->getAllTransactions($periodeStart, $periodeEnd);

        // Filter by kelompok jika dipilih
        if ($kelompokFilter) {
            $transaksi = array_filter($transaksi, fn($t) => $t['kelompok_id'] == $kelompokFilter);
        }

        // Kelompokkan per rekening
        $bukuBesarPerRekening = [];

        foreach ($transaksi as $t) {
            $rekeningId = $t['rekening_id'];

            if (!isset($bukuBesarPerRekening[$rekeningId])) {
                // Ambil saldo awal rekening untuk tahun periode
                $saldoAwal = $this->getSaldoAwalRekening($rekeningId, $periodeStart->year);
                
                $bukuBesarPerRekening[$rekeningId] = [
                    'kode' => $t['kode_rekening'],
                    'nama' => $t['nama_rekening'],
                    'transaksi' => [],
                    'saldo_awal' => $saldoAwal,
                    'total_debit' => 0,
                    'total_kredit' => 0,
                ];
            }

            $bukuBesarPerRekening[$rekeningId]['transaksi'][] = [
                'tanggal' => $t['tanggal'],
                'jenis' => $t['jenis'],
                'debit' => $t['posisi'] === 'D' ? $t['rp'] : 0,
                'kredit' => $t['posisi'] === 'K' ? $t['rp'] : 0,
            ];

            if ($t['posisi'] === 'D') {
                $bukuBesarPerRekening[$rekeningId]['total_debit'] += $t['rp'];
            } else {
                $bukuBesarPerRekening[$rekeningId]['total_kredit'] += $t['rp'];
            }
        }

        // Sort transaksi by tanggal untuk setiap rekening
        foreach ($bukuBesarPerRekening as &$rek) {
            usort($rek['transaksi'], fn($a, $b) => strtotime($a['tanggal']) <=> strtotime($b['tanggal']));

            // Hitung saldo berjalan
            $saldo = $rek['saldo_awal'];
            foreach ($rek['transaksi'] as &$tr) {
                $saldo += ($tr['debit'] - $tr['kredit']);
                $tr['saldo'] = $saldo;
            }

            $rek['saldo_akhir'] = $saldo;
        }

        return [
            'title' => 'BUKU BESAR',
            'periode' => $periodeStart->format('d F Y') . ' s/d ' . $periodeEnd->format('d F Y'),
            'data' => array_values($bukuBesarPerRekening),
            'total_rekening' => count($bukuBesarPerRekening),
        ];
    }

    protected function generateTrialBalance(array $filters): array
    {
        $periodeEnd = Carbon::parse($filters['periode_end']);
        $tahun = $periodeEnd->year;
        $transaksi = $this->getAllTransactions(null, $periodeEnd);

        $saldoPerRekening = [];

        foreach ($transaksi as $t) {
            $rekeningId = $t['rekening_id'];
            if (!isset($saldoPerRekening[$rekeningId])) {
                // Include saldo awal
                $saldoAwal = $this->getSaldoAwalRekening($rekeningId, $tahun);
                
                $saldoPerRekening[$rekeningId] = [
                    'kode' => $t['kode_rekening'],
                    'nama' => $t['nama_rekening'],
                    'saldo_awal' => $saldoAwal,
                    'debit' => 0,
                    'kredit' => 0,
                ];
            }

            if ($t['posisi'] === 'D') {
                $saldoPerRekening[$rekeningId]['debit'] += $t['rp'];
            } else {
                $saldoPerRekening[$rekeningId]['kredit'] += $t['rp'];
            }
        }

        return [
            'title' => 'NERACA SALDO',
            'periode' => $periodeEnd->format('d F Y'),
            'data' => array_values($saldoPerRekening),
            'total_debit' => array_sum(array_column($saldoPerRekening, 'debit')),
            'total_kredit' => array_sum(array_column($saldoPerRekening, 'kredit')),
        ];
    }

    protected function getAllTransactions($start = null, $end = null): array
    {
        $allTransactions = [];

        // Jurnal Rekening Air
        $queryRA = JurnalRekeningAir::query()
            ->with(['rekening', 'kelompok'])
            ->when($start, fn($q) => $q->whereDate('tanggal', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('tanggal', '<=', $end))
            ->get();

        foreach ($queryRA as $item) {
            $allTransactions[] = [
                'tanggal' => $item->tanggal,
                'rekening_id' => $item->rekening_id,
                'kode_rekening' => $item->rekening?->kode ?? '',
                'nama_rekening' => $item->rekening?->nama_rek ?? '',
                'kelompok_id' => $item->kelompok_id,
                'posisi' => $item->kode, // D atau K
                'rp' => $item->rp,
                'jenis' => 'Rekening Air',
            ];
        }

        // Jurnal Pemakaian Bahan
        $queryPB = JurnalPemakaianBahan::query()
            ->when($start, fn($q) => $q->whereDate('tanggal', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('tanggal', '<=', $end))
            ->get();

        foreach ($queryPB as $item) {
            // Debit
            if ($item->rekening_debit_id) {
                $allTransactions[] = [
                    'tanggal' => $item->tanggal,
                    'rekening_id' => $item->rekening_debit_id,
                    'kode_rekening' => $item->rekeningDebit?->kode ?? '',
                    'nama_rekening' => $item->rekeningDebit?->nama_rek ?? '',
                    'kelompok_id' => $item->kelompok_debit_id,
                    'posisi' => 'D',
                    'rp' => $item->rp,
                    'jenis' => 'Pemakaian Bahan',
                ];
            }
            // Kredit
            if ($item->rekening_kredit_id) {
                $allTransactions[] = [
                    'tanggal' => $item->tanggal,
                    'rekening_id' => $item->rekening_kredit_id,
                    'kode_rekening' => $item->rekeningKredit?->kode ?? '',
                    'nama_rekening' => $item->rekeningKredit?->nama_rek ?? '',
                    'kelompok_id' => $item->kelompok_kredit_id,
                    'posisi' => 'K',
                    'rp' => $item->rp,
                    'jenis' => 'Pemakaian Bahan',
                ];
            }
        }

        // Jurnal Memorial
        $queryMem = JurnalMemorial::query()
            ->with(['rekening', 'kelompok'])
            ->when($start, fn($q) => $q->whereDate('tanggal', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('tanggal', '<=', $end))
            ->get();

        foreach ($queryMem as $item) {
            $allTransactions[] = [
                'tanggal' => $item->tanggal,
                'rekening_id' => $item->rekening_id,
                'kode_rekening' => $item->rekening?->kode ?? '',
                'nama_rekening' => $item->rekening?->nama_rek ?? '',
                'kelompok_id' => $item->kelompok_id,
                'posisi' => $item->kode,
                'rp' => $item->rp,
                'jenis' => 'Memorial',
            ];
        }

        // TODO: Tambahkan jurnal lainnya (Pembelian, Bayar Kas Bank, Penerimaan Kas)

        return $allTransactions;
    }

    protected function hitungSaldoKelompok($kelompokId, array $transaksi): float
    {
        $saldo = 0;

        foreach ($transaksi as $t) {
            if ($t['kelompok_id'] == $kelompokId) {
                if ($t['posisi'] === 'D') {
                    $saldo += $t['rp'];
                } else {
                    $saldo -= $t['rp'];
                }
            }
        }

        return $saldo;
    }

    public function exportPdf()
    {
        if (!$this->reportData || !$this->reportType) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Tidak ada data')
                ->body('Silakan generate laporan terlebih dahulu')
                ->send();
            return;
        }

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'pdf.laporan-keuangan',
                [
                    'reportType' => $this->reportType,
                    'reportData' => $this->reportData,
                    'title' => $this->reportData['title'] ?? 'Laporan Keuangan',
                ]
            );

            $filename = $this->getFilename('pdf');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Export Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    public function exportExcel()
    {
        if (!$this->reportData || !$this->reportType) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Tidak ada data')
                ->body('Silakan generate laporan terlebih dahulu')
                ->send();
            return;
        }

        try {
            $filename = $this->getFilename('xlsx');

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\LaporanKeuanganExport($this->reportType, $this->reportData),
                $filename
            );
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Export Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getFilename(string $extension): string
    {
        $reportNames = [
            'neraca' => 'Neraca',
            'laba_rugi' => 'Laba-Rugi',
            'arus_kas' => 'Arus-Kas',
            'perubahan_modal' => 'Perubahan-Modal',
            'buku_besar' => 'Buku-Besar',
            'trial_balance' => 'Neraca-Saldo',
        ];

        $reportName = $reportNames[$this->reportType] ?? 'Laporan';
        $date = now()->format('Y-m-d-His');

        return "{$reportName}_{$date}.{$extension}";
    }

    /**
     * Ambil saldo awal per rekening untuk tahun tertentu
     */
    protected function getSaldoAwalRekening(int $rekeningId, int $tahun): float
    {
        $saldoAwal = SaldoAwalRekening::where('rekening_id', $rekeningId)
            ->where('tahun', $tahun)
            ->first();

        if (!$saldoAwal) {
            return 0;
        }

        // Jika posisi Debit = positif, Kredit = negatif
        return $saldoAwal->posisi === 'D' ? $saldoAwal->saldo_awal : -$saldoAwal->saldo_awal;
    }

    /**
     * Ambil total saldo awal untuk satu kelompok (agregasi dari semua rekening di kelompok)
     */
    protected function getSaldoAwalKelompok(int $kelompokId, int $tahun): float
    {
        // Ambil semua rekening di kelompok ini
        $rekening = \App\Models\Rekening::where('kelompok_id', $kelompokId)->pluck('id');

        $totalSaldo = 0;

        foreach ($rekening as $rekeningId) {
            $totalSaldo += $this->getSaldoAwalRekening($rekeningId, $tahun);
        }

        return $totalSaldo;
    }
}
