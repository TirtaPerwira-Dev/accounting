<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '60s'; // Increased to reduce load

    protected function getStats(): array
    {
        // Cache key based on current month
        $cacheKey = 'financial_overview_' . now()->format('Y_m');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            // Optimize: Single query for pendapatan and pengeluaran
            $financialSummary = JournalDetail::select(
                DB::raw('SUM(CASE WHEN k.no_kel LIKE "8%" THEN jd.credit ELSE 0 END) as total_pendapatan'),
                DB::raw('SUM(CASE WHEN k.no_kel LIKE "9%" THEN jd.debit ELSE 0 END) as total_pengeluaran')
            )
                ->from('journal_details as jd')
                ->join('journals as j', 'jd.journal_id', '=', 'j.id')
                ->join('nomor_bantus as nb', 'jd.nomor_bantu_id', '=', 'nb.id')
                ->join('rekenings as r', 'nb.rekening_id', '=', 'r.id')
                ->join('kelompoks as k', 'r.kelompok_id', '=', 'k.id')
                ->where('j.status', 'posted')
                ->whereYear('j.transaction_date', now()->year)
                ->whereMonth('j.transaction_date', now()->month)
                ->first();

            $totalPendapatan = $financialSummary->total_pendapatan ?? 0;
            $totalPengeluaran = $financialSummary->total_pengeluaran ?? 0;

            // Optimize: Single query for kas and piutang
            $balances = JournalDetail::select(
                DB::raw('SUM(CASE WHEN r.no_rek = "1101" THEN jd.debit - jd.credit ELSE 0 END) as saldo_kas'),
                DB::raw('SUM(CASE WHEN r.no_rek = "1301" THEN jd.debit - jd.credit ELSE 0 END) as piutang_usaha')
            )
                ->from('journal_details as jd')
                ->join('journals as j', 'jd.journal_id', '=', 'j.id')
                ->join('nomor_bantus as nb', 'jd.nomor_bantu_id', '=', 'nb.id')
                ->join('rekenings as r', 'nb.rekening_id', '=', 'r.id')
                ->where('j.status', 'posted')
                ->first();

            $saldoKas = $balances->saldo_kas ?? 0;
            $piutangUsaha = $balances->piutang_usaha ?? 0;

            // Net Income bulan ini
            $netIncome = $totalPendapatan - $totalPengeluaran;

            // Jurnal Draft yang menunggu approval (no cache for real-time data)
            $draftJournals = Journal::where('status', 'draft')->count();

            return [
                Stat::make('Pendapatan Bulan Ini', 'Rp ' . number_format($totalPendapatan, 0, ',', '.'))
                    ->description('Revenue bulan ' . now()->format('M Y'))
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),

                Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($totalPengeluaran, 0, ',', '.'))
                    ->description('Biaya operasional & lainnya')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color('danger'),

                Stat::make('Laba Bersih', 'Rp ' . number_format($netIncome, 0, ',', '.'))
                    ->description($netIncome >= 0 ? 'Profit bulan ini' : 'Loss bulan ini')
                    ->descriptionIcon($netIncome >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                    ->color($netIncome >= 0 ? 'success' : 'danger'),

                Stat::make('Saldo Kas & Bank', 'Rp ' . number_format($saldoKas, 0, ',', '.'))
                    ->description('Posisi likuiditas saat ini')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color($saldoKas >= 0 ? 'success' : 'danger'),

                Stat::make('Total Piutang', 'Rp ' . number_format($piutangUsaha, 0, ',', '.'))
                    ->description('Outstanding receivables')
                    ->descriptionIcon('heroicon-m-clipboard-document-list')
                    ->color($piutangUsaha > 5000000 ? 'warning' : 'success'),

                Stat::make('Jurnal Draft', $draftJournals)
                    ->description('Menunggu persetujuan')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($draftJournals > 0 ? 'warning' : 'success'),
            ];
        });
    }
}
