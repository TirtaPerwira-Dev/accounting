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

// Jurnal Penerimaan Kas PDF Routes
Route::get('/jurnal-penerimaan-kas/{record}/pdf', function (JurnalPenerimaanKas $record) {
    $record->load(['kasBank.rekening.kelompok']);
    return Pdf::loadView('pdf.jurnal-penerimaan-kas', compact('record'))
        ->download("JPK-{$record->nomor_bukti}.pdf");
})->name('jurnal-penerimaan-kas.pdf');
