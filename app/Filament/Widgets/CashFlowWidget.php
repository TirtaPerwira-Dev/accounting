<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;

class CashFlowWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {

        // Penjualan Air (akun 8101xxx - Pendapatan Penjualan Air)
        $penjualanAir = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->where('rekenings.no_rek', '8101') // Pendapatan Penjualan Air
            ->where('journals.status', 'posted')
            ->whereYear('journals.transaction_date', now()->year)
            ->whereMonth('journals.transaction_date', now()->month)
            ->sum('journal_details.credit');

        // Piutang Usaha (akun 1301xxx - Piutang Rekening Air)
        $piutangUsaha = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->where('rekenings.no_rek', '1301') // Piutang Rekening Air
            ->where('journals.status', 'posted')
            ->sum(DB::raw('journal_details.debit - journal_details.credit'));

        // Kas Masuk bulan ini (Debit ke akun kas 1101xxx)
        $kasMasuk = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->where('rekenings.no_rek', '1101') // Kas
            ->where('journals.status', 'posted')
            ->whereYear('journals.transaction_date', now()->year)
            ->whereMonth('journals.transaction_date', now()->month)
            ->sum('journal_details.debit');

        // Kas Keluar bulan ini (Credit dari akun kas 1101xxx)
        $kasKeluar = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->where('rekenings.no_rek', '1101') // Kas
            ->where('journals.status', 'posted')
            ->whereYear('journals.transaction_date', now()->year)
            ->whereMonth('journals.transaction_date', now()->month)
            ->sum('journal_details.credit');

        // Kas bulan lalu untuk perbandingan
        $kasMasukLastMonth = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->where('rekenings.no_rek', '1101')
            ->where('journals.status', 'posted')
            ->whereYear('journals.transaction_date', now()->subMonth()->year)
            ->whereMonth('journals.transaction_date', now()->subMonth()->month)
            ->sum('journal_details.debit');

        // Hitung saldo kas bersih
        $kasBersih = $kasMasuk - $kasKeluar;
        $persentaseKas = $kasMasukLastMonth > 0 ? (($kasMasuk - $kasMasukLastMonth) / $kasMasukLastMonth) * 100 : 0;

        return [ //

        ];
    }
}
