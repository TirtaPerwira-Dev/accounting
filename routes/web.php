<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\NomorBantuExportController;
use App\Models\JurnalPenerimaanKas;
use Barryvdh\DomPDF\Facade\Pdf;

// Default Laravel welcome removed - Filament admin panel is now at '/'
// Route::get('/', function () {
//     return view('welcome');
// });

// Export Routes
Route::get('/report/export/pdf', [ReportExportController::class, 'exportPdf'])->name('report.export.pdf');
Route::get('/report/export/excel', [ReportExportController::class, 'exportExcel'])->name('report.export.excel');

// Nomor Bantu Export Routes
Route::get('/nomor-bantu/export/pdf', [NomorBantuExportController::class, 'exportPdf'])->name('nomor-bantu.export-pdf');

// Jurnal Pembelian PDF Routes
Route::get('/jurnal-pembelian/laporan-pdf', [ReportExportController::class, 'jurnalPembelianPdf'])->name('jurnal-pembelian.pdf');
Route::get('/jurnal-pembelian/{id}/pdf', [ReportExportController::class, 'jurnalPembelianSinglePdf'])->name('jurnal-pembelian.single-pdf');

// Jurnal Penerimaan Kas PDF Routes - Single Record
Route::get('/jurnal-penerimaan-kas/{record}/pdf', function (JurnalPenerimaanKas $record) {
    $record->load(['kasBank.rekening.kelompok']);
    return Pdf::loadView('pdf.jurnal-penerimaan-kas', compact('record'))
        ->download("JPK-{$record->nomor_bukti}.pdf");
})->name('jurnal-penerimaan-kas.single-pdf');

// Jurnal Rekening Air PDF Routes - Report
Route::get('/jurnal-rekening-air/laporan-pdf', [ReportExportController::class, 'jurnalRekeningAirPdf'])->name('jurnal-rekening-air.pdf');

// Jurnal Penerimaan Kas PDF Routes - Report
Route::get('/jurnal-penerimaan-kas/laporan-pdf', [ReportExportController::class, 'jurnalPenerimaanKasPdf'])->name('jurnal-penerimaan-kas.pdf');
