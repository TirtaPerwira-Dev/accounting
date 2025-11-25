<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {

        // Total Pendapatan (Credit di akun pendapatan 8xxx)
        $totalPendapatan = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
            ->where('kelompoks.no_kel', 'LIKE', '8%') // Pendapatan
            ->where('journals.status', 'posted')
            ->whereYear('journals.transaction_date', now()->year)
            ->whereMonth('journals.transaction_date', now()->month)
            ->sum('journal_details.credit');

        // Total Pengeluaran (Debit di akun beban 9xxx)
        $totalPengeluaran = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
            ->where('kelompoks.no_kel', 'LIKE', '9%') // Beban/Biaya
            ->where('journals.status', 'posted')
            ->whereYear('journals.transaction_date', now()->year)
            ->whereMonth('journals.transaction_date', now()->month)
            ->sum('journal_details.debit');

        // Saldo Kas total (Debit - Credit di akun kas 1101xxx)
        $saldoKas = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->where('rekenings.no_rek', '1101') // Kas
            ->where('journals.status', 'posted')
            ->sum(DB::raw('journal_details.debit - journal_details.credit'));

        // Piutang Usaha total (akun 1301xxx)
        $piutangUsaha = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->where('rekenings.no_rek', '1301') // Piutang Rekening Air
            ->where('journals.status', 'posted')
            ->sum(DB::raw('journal_details.debit - journal_details.credit'));

        // Net Income bulan ini
        $netIncome = $totalPendapatan - $totalPengeluaran;

        // Jurnal Draft yang menunggu approval
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
                ->color($draftJournals > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.jurnal-umum.index')),
        ];
    }
}
