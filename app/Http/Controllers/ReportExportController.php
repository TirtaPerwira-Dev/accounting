<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportExportController extends Controller
{
    public function exportPdf(Request $request)
    {
        // Get data from session
        $reportData = session('export_report_data');
        $reportType = session('export_report_type');
        $companyId = session('export_company_id');
        $periodData = session('export_period_data');

        if (!$reportData || !$reportType) {
            abort(404, 'Report data not found');
        }

        // Get company name for filename
        $company = Company::find($companyId);
        $companyName = $company ? str_replace(' ', '_', $company->name) : 'PDAM';

        // Generate filename
        $reportTypeNames = [
            'trial_balance' => 'Neraca_Saldo',
            'balance_sheet' => 'Neraca',
            'income_statement' => 'Laba_Rugi',
            'cash_flow' => 'Arus_Kas',
            'general_ledger' => 'Buku_Besar',
        ];

        $reportName = $reportTypeNames[$reportType] ?? 'Laporan';
        $date = now()->format('Y-m-d');
        $filename = "{$companyName}_{$reportName}_{$date}";

        // Get period text
        $period = $this->getPeriodText($reportType, $periodData);

        // Prepare data for PDF
        $pdfData = [
            'reportData' => $reportData,
            'reportType' => $reportType,
            'company' => $company,
            'generatedAt' => now()->format('d F Y H:i:s'),
            'reportTitle' => match ($reportType) {
                'trial_balance' => 'Neraca Saldo',
                'balance_sheet' => 'Neraca',
                'income_statement' => 'Laporan Laba Rugi',
                'cash_flow' => 'Laporan Arus Kas',
                'general_ledger' => 'Buku Besar',
                default => 'Laporan Keuangan'
            },
            'period' => $period
        ];

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('filament.reports.pdf.financial-report', $pdfData)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'debugKeepTemp' => false,
                'debugCss' => false,
                'debugLayout' => false,
                'debugLayoutLines' => false,
                'debugLayoutBlocks' => false,
                'debugLayoutInline' => false,
                'debugLayoutPaddingBox' => false,
            ]);

        // Clear session data
        session()->forget([
            'export_report_data',
            'export_report_type',
            'export_company_id',
            'export_period_data'
        ]);

        // Return PDF file for download
        return $pdf->download($filename . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        // TODO: Implement Excel export
        return response()->json(['message' => 'Excel export coming soon'], 501);
    }

    private function getPeriodText(string $reportType, array $periodData): string
    {
        if (in_array($reportType, ['trial_balance', 'balance_sheet'])) {
            $date = $periodData['as_of_date'] ?? now()->toDateString();
            return 'Per ' . Carbon::parse($date)->format('d F Y');
        } else {
            $fromDate = $periodData['from_date'] ?? now()->startOfMonth()->toDateString();
            $toDate = $periodData['to_date'] ?? now()->endOfMonth()->toDateString();
            return Carbon::parse($fromDate)->format('d M Y') . ' - ' . Carbon::parse($toDate)->format('d M Y');
        }
    }

    /**
     * Generate PDF for single Jurnal Pembelian
     */
    public function jurnalPembelianSinglePdf($id)
    {
        // Get company data
        $company = \App\Models\Company::first();
        
        $record = \App\Models\JurnalPembelian::with([
                'nomorBantuKredit.rekening.kelompok',
                'kodeProyek',
                'details.nomorBantuDebit.rekening.kelompok',
                'details.kodeProyek',
                'confirmedBy'
            ])
            ->findOrFail($id);

        $pdf = Pdf::loadView('reports.jurnal-pembelian-single', [
            'company' => $company,
            'jurnal' => $record,
            'generatedAt' => now()->format('d M Y H:i'),
        ])->setPaper('a4', 'portrait')
          ->setOption('isHtml5ParserEnabled', true)
          ->setOption('isRemoteEnabled', true);

        // Sanitize filename
        $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->no_reff);
        $filename = 'jurnal-pembelian-' . $safeFilename . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public');
    }
    /**
     * Generate PDF for Periodic Journal report (Standardized)
     */
    public function periodicReportPdf(Request $request)
    {
        $type = $request->input('type');
        $fromDate = $request->input('dari_tanggal') ?? $request->input('start_date');
        $toDate = $request->input('sampai_tanggal') ?? $request->input('end_date');
        $status = $request->input('status', 'all');
        $company = Company::first();

        if (!$type || !$fromDate || !$toDate) {
            abort(400, 'Missing required parameters: type, dari_tanggal, sampai_tanggal');
        }

        $reportTitle = '';
        $items = [];

        switch ($type) {
            case 'pembelian':
                $reportTitle = 'Laporan Jurnal Pembelian Barang';
                $query = \App\Models\JurnalPembelian::with(['nomorBantuKredit.rekening.kelompok', 'details.nomorBantuDebit.rekening.kelompok'])
                    ->whereBetween('tanggal', [$fromDate, $toDate]);
                if ($status === 'confirmed') $query->where('is_confirmed', true);
                elseif ($status === 'pending') $query->where('is_confirmed', false);
                if ($request->filled('kode_hutang')) $query->where('nomor_bantu_kredit_id', $request->kode_hutang);
                
                $journals = $query->orderBy('tanggal', 'desc')->get();
                foreach ($journals as $j) {
                    $details = [];
                    foreach ($j->details as $d) {
                        $details[] = [
                            'code' => $d->nomor_bantu_debit_id ? $d->nomorBantuDebit->rekening->kelompok->no_kel . $d->nomorBantuDebit->rekening->no_rek . str_pad($d->nomorBantuDebit->no_bantu, 2, '0', STR_PAD_LEFT) : '-',
                            'name' => $d->nomorBantuDebit->nm_bantu ?? '-',
                            'debit' => $d->jumlah,
                            'credit' => 0,
                            'description' => $d->keterangan,
                            'bukti' => $d->bukti,
                        ];
                    }
                    $nb = $j->nomorBantuKredit;
                    $items[] = [
                        'tanggal' => $j->tanggal,
                        'no_reff' => $j->no_reff,
                        'bukti' => $j->bukti,
                        'main_account_code' => $nb ? $nb->rekening->kelompok->no_kel . $nb->rekening->no_rek . str_pad($nb->no_bantu, 2, '0', STR_PAD_LEFT) : '-',
                        'main_account_name' => $nb->nm_bantu ?? '-',
                        'main_posisi' => 'K',
                        'total_amount' => $j->details->sum('jumlah'),
                        'description' => $j->keterangan,
                        'status' => $j->is_confirmed ? 'OK' : 'P',
                        'details' => $details
                    ];
                }
                break;

            case 'rekening_air':
                $reportTitle = 'Laporan Jurnal Rekening Air & Non Air';
                $query = \App\Models\JurnalRekeningAir::with(['details.rekening.kelompok', 'details.nomorBantu'])
                    ->whereBetween('tanggal', [$fromDate, $toDate]);
                if ($status === 'confirmed') $query->where('is_confirmed', true);
                elseif ($status === 'pending') $query->where('is_confirmed', false);

                $journals = $query->orderBy('tanggal', 'desc')->get();
                foreach ($journals as $j) {
                    $debitRows = $j->details->where('position', 'debit');
                    $creditRows = $j->details->where('position', 'kredit');
                    
                    $details = [];
                    // We'll take debits as main if there's only one, or just list everything underneath a dummy header
                    foreach ($j->details as $d) {
                        $details[] = [
                            'code' => $d->rekening ? $d->rekening->kelompok->no_kel . $d->rekening->no_rek . ($d->nomorBantu ? str_pad($d->nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) : '') : '-',
                            'name' => $d->nomorBantu ? $d->nomorBantu->nm_bantu : ($d->rekening->nama_rek ?? '-'),
                            'debit' => $d->position === 'debit' ? $d->jumlah : 0,
                            'credit' => $d->position === 'kredit' ? $d->jumlah : 0,
                            'description' => $d->keterangan,
                            'bukti' => null,
                        ];
                    }
                    $items[] = [
                        'tanggal' => $j->tanggal,
                        'no_reff' => $j->no_reff,
                        'bukti' => $j->bukti,
                        'main_account_code' => '---',
                        'main_account_name' => 'Jurnal Rekening Air',
                        'main_posisi' => 'D',
                        'total_amount' => 0,
                        'description' => $j->keterangan,
                        'status' => $j->is_confirmed ? 'OK' : 'P',
                        'details' => $details
                    ];
                }
                break;

            case 'bayar_kas_bank':
                $reportTitle = 'Laporan Jurnal Pembayaran Kas/Bank';
                $query = \App\Models\JurnalBayarKasBank::with(['rekening.kelompok', 'nomorBantu', 'details.rekening.kelompok', 'details.nomorBantu'])
                    ->whereBetween('tanggal', [$fromDate, $toDate]);
                if ($status === 'confirmed') $query->where('is_confirmed', true);
                elseif ($status === 'pending') $query->where('is_confirmed', false);

                $journals = $query->orderBy('tanggal', 'desc')->get();
                foreach ($journals as $j) {
                    $details = [];
                    foreach ($j->details as $d) {
                        $details[] = [
                            'code' => $d->rekening ? $d->rekening->kelompok->no_kel . $d->rekening->no_rek . ($d->nomorBantu ? str_pad($d->nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) : '') : '-',
                            'name' => $d->nomorBantu ? $d->nomorBantu->nm_bantu : ($d->rekening->nama_rek ?? '-'),
                            'debit' => $d->jumlah,
                            'credit' => 0,
                            'description' => $d->keterangan,
                            'bukti' => $d->bukti,
                        ];
                    }
                    $nb = $j->nomorBantu;
                    $items[] = [
                        'tanggal' => $j->tanggal,
                        'no_reff' => $j->no_reff,
                        'bukti' => $j->bukti,
                        'main_account_code' => $nb ? $nb->rekening->kelompok->no_kel . $nb->rekening->no_rek . str_pad($nb->no_bantu, 2, '0', STR_PAD_LEFT) : '-',
                        'main_account_name' => $nb->nm_bantu ?? $j->rekening->nama_rek ?? '-',
                        'main_posisi' => 'K',
                        'total_amount' => $j->details->sum('jumlah'),
                        'description' => $j->keterangan,
                        'status' => $j->is_confirmed ? 'OK' : 'P',
                        'details' => $details
                    ];
                }
                break;

            case 'penerimaan_kas':
                $reportTitle = 'Laporan Jurnal Penerimaan Kas';
                $query = \App\Models\JurnalPenerimaanKas::with(['kasBank.rekening.kelompok', 'details.rekening.kelompok', 'details.nomorBantu'])
                    ->whereBetween('tanggal', [$fromDate, $toDate]);
                if ($status === 'confirmed') $query->where('is_confirmed', true);
                elseif ($status === 'pending') $query->where('is_confirmed', false);
                if ($request->filled('kas_bank')) $query->where('nomor_bantu_id', $request->kas_bank);

                $journals = $query->orderBy('tanggal', 'desc')->get();
                foreach ($journals as $j) {
                    $details = [];
                    foreach ($j->details as $d) {
                        $details[] = [
                            'code' => $d->rekening ? $d->rekening->kelompok->no_kel . $d->rekening->no_rek . ($d->nomorBantu ? str_pad($d->nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) : '') : '-',
                            'name' => $d->nomorBantu ? $d->nomorBantu->nm_bantu : ($d->rekening->nama_rek ?? '-'),
                            'debit' => 0,
                            'credit' => $d->jumlah,
                            'description' => $d->keterangan_item,
                            'bukti' => $d->nomor_bukti,
                        ];
                    }
                    $nb = $j->kasBank;
                    $items[] = [
                        'tanggal' => $j->tanggal,
                        'no_reff' => $j->no_reff,
                        'bukti' => $j->nomor_bukti,
                        'main_account_code' => $nb ? $nb->rekening->kelompok->no_kel . $nb->rekening->no_rek . str_pad($nb->no_bantu, 2, '0', STR_PAD_LEFT) : '-',
                        'main_account_name' => $nb->nm_bantu ?? '-',
                        'main_posisi' => 'D',
                        'total_amount' => $j->details->sum('jumlah'),
                        'description' => $j->keterangan,
                        'status' => $j->is_confirmed ? 'OK' : 'P',
                        'details' => $details
                    ];
                }
                break;

            case 'memorial':
                $reportTitle = 'Laporan Jurnal Memorial';
                $query = \App\Models\JurnalMemorial::with(['details.rekening.kelompok', 'details.nomorBantu'])
                    ->whereBetween('tanggal', [$fromDate, $toDate]);
                if ($status === 'confirmed') $query->where('is_confirmed', true);
                elseif ($status === 'pending') $query->where('is_confirmed', false);

                $journals = $query->orderBy('tanggal', 'desc')->get();
                foreach ($journals as $j) {
                    $details = [];
                    foreach ($j->details as $d) {
                        $details[] = [
                            'code' => $d->rekening ? $d->rekening->kelompok->no_kel . $d->rekening->no_rek . ($d->nomorBantu ? str_pad($d->nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) : '') : '-',
                            'name' => $d->nomorBantu ? $d->nomorBantu->nm_bantu : ($d->rekening->nama_rek ?? '-'),
                            'debit' => $d->posisi === 'D' ? $d->jumlah : 0,
                            'credit' => $d->posisi === 'K' ? $d->jumlah : 0,
                            'description' => $d->keterangan,
                            'bukti' => null,
                        ];
                    }
                    $items[] = [
                        'tanggal' => $j->tanggal,
                        'no_reff' => $j->no_reff,
                        'bukti' => $j->bukti,
                        'main_account_code' => '---',
                        'main_account_name' => 'Jurnal Memorial',
                        'main_posisi' => 'D',
                        'total_amount' => 0,
                        'description' => $j->keterangan,
                        'status' => $j->is_confirmed ? 'OK' : 'P',
                        'details' => $details
                    ];
                }
                break;

            case 'pemakaian_bahan':
                $reportTitle = 'Laporan Jurnal Pemakaian Bahan';
                $query = \App\Models\JurnalPemakaianBahan::with(['details.rekeningDebit.kelompok', 'details.rekeningKredit.kelompok', 'details.nomorBantuDebit', 'details.nomorBantuKredit'])
                    ->whereBetween('tanggal', [$fromDate, $toDate]);
                if ($status === 'confirmed') $query->where('is_confirmed', true);
                elseif ($status === 'pending') $query->where('is_confirmed', false);

                $journals = $query->orderBy('tanggal', 'desc')->get();
                foreach ($journals as $j) {
                    $details = [];
                    foreach ($j->details as $d) {
                        if ($d->rekening_debit_id) {
                            $details[] = [
                                'code' => ($d->rekeningDebit->kelompok->no_kel ?? '') . ($d->rekeningDebit->no_rek ?? '') . ($d->nomorBantuDebit->no_bantu ?? ''),
                                'name' => $d->nomorBantuDebit->nm_bantu ?? $d->rekeningDebit->nama_rek ?? '-',
                                'debit' => $d->jumlah,
                                'credit' => 0,
                                'description' => $d->keterangan,
                                'bukti' => $d->bukti,
                            ];
                        }
                        if ($d->rekening_kredit_id) {
                            $details[] = [
                                'code' => ($d->rekeningKredit->kelompok->no_kel ?? '') . ($d->rekeningKredit->no_rek ?? '') . ($d->nomorBantuKredit->no_bantu ?? ''),
                                'name' => $d->nomorBantuKredit->nm_bantu ?? $d->rekeningKredit->nama_rek ?? '-',
                                'debit' => 0,
                                'credit' => $d->jumlah,
                                'description' => $d->keterangan,
                                'bukti' => $d->bukti,
                            ];
                        }
                    }
                    $items[] = [
                        'tanggal' => $j->tanggal,
                        'no_reff' => $j->no_reff,
                        'bukti' => $j->bukti,
                        'main_account_code' => '---',
                        'main_account_name' => 'Jurnal Pemakaian Bahan',
                        'main_posisi' => 'D',
                        'total_amount' => 0,
                        'description' => $j->keterangan,
                        'status' => $j->is_confirmed ? 'OK' : 'P',
                        'details' => $details
                    ];
                }
                break;
        }

        $report = [
            'type' => $type,
            'title' => $reportTitle,
            'period' => Carbon::parse($fromDate)->format('d/m/Y') . ' - ' . Carbon::parse($toDate)->format('d/m/Y'),
            'items' => $items,
        ];

        $pdf = Pdf::loadView('pdf.periodic-report', compact('report', 'company'))
            ->setPaper('a4', 'landscape');

        $filename = 'laporan-' . str_replace('_', '-', $type) . '-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
