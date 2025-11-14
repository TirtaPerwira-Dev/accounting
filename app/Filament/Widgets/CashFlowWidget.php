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

        // Collection Rate (Kas Masuk / Penjualan Air)
        $collectionRate = $penjualanAir > 0 ? ($kasMasuk / $penjualanAir) * 100 : 0;

        // Growth rate kas masuk
        $growthRate = $kasMasukLastMonth > 0 ? (($kasMasuk - $kasMasukLastMonth) / $kasMasukLastMonth) * 100 : 0;

        return [
            Stat::make('Penjualan Air Bulan Ini', 'Rp ' . number_format($penjualanAir, 0, ',', '.'))
                ->description('Revenue dari penjualan air')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('info')
                ->chart([950000, 1100000, 1200000, $penjualanAir]),

            Stat::make('Kas Masuk Bulan Ini', 'Rp ' . number_format($kasMasuk, 0, ',', '.'))
                ->description(($growthRate >= 0 ? '+' : '') . number_format($growthRate, 1) . '% dari bulan lalu')
                ->descriptionIcon($growthRate >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                ->color($growthRate >= 0 ? 'success' : 'danger')
                ->chart([800000, 950000, 1050000, $kasMasuk]),

            Stat::make('Kas Keluar Bulan Ini', 'Rp ' . number_format($kasKeluar, 0, ',', '.'))
                ->description('Pembayaran operasional')
                ->descriptionIcon('heroicon-m-arrow-down-on-square')
                ->color('warning')
                ->chart([600000, 750000, 850000, $kasKeluar]),

            Stat::make('Total Piutang Usaha', 'Rp ' . number_format($piutangUsaha, 0, ',', '.'))
                ->description('Outstanding receivables')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($piutangUsaha > 1000000 ? 'warning' : 'success'),

            Stat::make('Collection Rate', number_format($collectionRate, 1) . '%')
                ->description('Efektivitas penagihan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($collectionRate >= 80 ? 'success' : ($collectionRate >= 60 ? 'warning' : 'danger'))
        ];
    }
}
