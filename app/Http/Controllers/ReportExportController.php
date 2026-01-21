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
     * Generate PDF for Jurnal Pembelian report
     */
    public function jurnalPembelianPdf(Request $request)
    {
        $filters = [
            'dari_tanggal' => $request->input('dari_tanggal'),
            'sampai_tanggal' => $request->input('sampai_tanggal'),
            'kode_hutang' => $request->input('kode_hutang'),
            'status' => $request->input('status', 'all'),
        ];

        // Get data
        $query = \App\Models\JurnalPembelian::query();
        
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

        $data = $query->with(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek'])
            ->orderBy('tanggal', 'desc')
            ->get();

        // Clean data untuk UTF-8
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

        // Stream PDF untuk preview di browser
        $filename = 'laporan-jurnal-pembelian-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public');
    }

    /**
     * Generate PDF for single Jurnal Pembelian
     */
    public function jurnalPembelianSinglePdf($id)
    {
        $record = \App\Models\JurnalPembelian::with(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('reports.jurnal-pembelian-single', [
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
}
