<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\NomorBantuExportController;

Route::get('/', function () {
    return view('welcome');
});

// Export Routes
Route::get('/report/export/pdf', [ReportExportController::class, 'exportPdf'])->name('report.export.pdf');
Route::get('/report/export/excel', [ReportExportController::class, 'exportExcel'])->name('report.export.excel');

// Nomor Bantu Export Routes
Route::get('/nomor-bantu/export/pdf', [NomorBantuExportController::class, 'exportPdf'])->name('nomor-bantu.export-pdf');
