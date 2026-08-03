<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\NomorBantuExportController;
use App\Http\Controllers\PdfPreviewController;
use App\Models\JurnalPenerimaanKas;
use Barryvdh\DomPDF\Facade\Pdf;

Route::redirect('/', '/login');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/auth/login', [LoginController::class, 'authenticate'])->name('auth.custom.login');

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

// Jurnal Penerimaan Kas Bulk PDF Preview Route
Route::get('/jurnal-penerimaan-kas/pdf/preview', [PdfPreviewController::class, 'jurnalPenerimaanKasBulk'])
    ->name('jurnal-penerimaan-kas.pdf.preview');

// Jurnal Rekening Air PDF Routes
Route::get('/jurnal-rekening-air/{id}/pdf', function ($id) {
    $jurnal = \App\Models\JurnalRekeningAir::with([
        'company',
        'details.rekening.kelompok',
        'details.nomorBantu',
        'details.kodeProyek',
        'createdBy'
    ])->findOrFail($id);
    
    $voucher = [
        'title' => 'BUKTI JURNAL REKENING AIR',
        'number' => $jurnal->bukti,
        'date' => $jurnal->tanggal,
        'reference' => $jurnal->no_reff,
        'description' => $jurnal->keterangan,
        'payee' => 'Internal / Rekening Air',
        'created_by' => $jurnal->createdBy?->name,
        'items' => $jurnal->details->map(function ($item) {
            $code = '-';
            if ($item->rekening) {
                $code = ($item->rekening->kelompok->no_kel ?? '') . 
                        ($item->rekening->no_rek ?? '') . 
                        ($item->nomorBantu->no_bantu ?? '');
            }
            return [
                'code' => $code,
                'name' => $item->rekening->nama_rek ?? '-',
                'description' => $item->keterangan,
                'debit' => $item->position === 'debit' ? $item->jumlah : 0,
                'credit' => $item->position === 'kredit' ? $item->jumlah : 0,
            ];
        }),
    ];

    $pdf = Pdf::loadView('pdf.voucher', [
        'voucher' => $voucher,
        'company' => $jurnal->company ?? \App\Models\Company::first(),
    ])->setPaper('a4', 'portrait');

    return response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="voucher-rekening-air-' . $jurnal->bukti . '.pdf"'
    ]);
})->name('jurnal-rekening-air.single-pdf');

// Unified Periodic Report Route
Route::get('/report/periodic-pdf', [ReportExportController::class, 'periodicReportPdf'])->name('report.periodic-pdf');
