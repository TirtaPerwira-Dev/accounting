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
}
