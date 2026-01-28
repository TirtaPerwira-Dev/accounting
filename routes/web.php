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

// Jurnal Penerimaan Kas PDF Routes
Route::get('/jurnal-penerimaan-kas/{record}/pdf', function (JurnalPenerimaanKas $record) {
    $record->load(['kasBank.rekening.kelompok']);
    return Pdf::loadView('pdf.jurnal-penerimaan-kas', compact('record'))
        ->download("JPK-{$record->nomor_bukti}.pdf");
})->name('jurnal-penerimaan-kas.pdf');

// Jurnal Rekening Air PDF Routes
Route::get('/jurnal-rekening-air/laporan-pdf', function () {
    $startDate = request('start_date');
    $endDate = request('end_date');
    $status = request('status');

    $query = \App\Models\JurnalRekeningAir::query();

    if ($startDate) {
        $query->whereDate('tanggal', '>=', $startDate);
    }

    if ($endDate) {
        $query->whereDate('tanggal', '<=', $endDate);
    }

    if ($status === 'confirmed') {
        $query->where('is_confirmed', true);
    } elseif ($status === 'pending') {
        $query->where('is_confirmed', false);
    }

    $journals = $query->with([
        'company',
        'details.kelompok',
        'details.rekening.kelompok',
        'details.nomorBantu',
        'details.kodeProyek'
    ])->orderBy('tanggal', 'desc')->get();

    $pdf = Pdf::loadView('reports.jurnal-rekening-air', [
        'journals' => $journals,
        'company' => auth()->user()?->company ?? \App\Models\Company::first(),
        'startDate' => $startDate,
        'endDate' => $endDate,
        'status' => $status,
    ]);

    return response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="jurnal-rekening-air-' . now()->format('Y-m-d-His') . '.pdf"'
    ]);
})->name('jurnal-rekening-air.pdf');

Route::get('/jurnal-rekening-air/{id}/pdf', function ($id) {
    $detail = \App\Models\JurnalRekeningAirDetail::with(['jurnalRekeningAir'])->findOrFail($id);
    $jurnal = $detail->jurnalRekeningAir;
    
    $pdf = Pdf::loadView('reports.jurnal-rekening-air-single', [
        'jurnal' => $jurnal,
        'company' => auth()->user()?->company ?? \App\Models\Company::first(),
    ]);

    return response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="jurnal-rekening-air-' . $jurnal->bukti . '.pdf"'
    ]);
})->name('jurnal-rekening-air.single-pdf');
