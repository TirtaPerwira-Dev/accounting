<?php

namespace App\Filament\Resources\JournalResource\Widgets;

use App\Models\Journal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class JournalStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Query untuk jurnal umum (bukan penerimaan atau pengeluaran)
        $generalJournalQuery = Journal::where(function ($query) {
            $query->whereNull('transaction_type')
                ->orWhere('transaction_type', '')
                ->orWhereNotIn('transaction_type', [Journal::TYPE_PENERIMAAN, Journal::TYPE_PENGELUARAN]);
        });

        // Total semua transaksi jurnal umum
        $totalJournals = (clone $generalJournalQuery)->count();

        $totalAmount = (clone $generalJournalQuery)->sum('total_amount') ?? 0;

        // Transaksi bulan ini
        $monthlyJournals = (clone $generalJournalQuery)
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->count();

        $monthlyAmount = (clone $generalJournalQuery)
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('total_amount') ?? 0;

        // Transaksi hari ini
        $dailyJournals = (clone $generalJournalQuery)
            ->whereDate('transaction_date', now()->toDateString())
            ->count();

        $dailyAmount = (clone $generalJournalQuery)
            ->whereDate('transaction_date', now()->toDateString())
            ->sum('total_amount') ?? 0;

        // Hitung persentase pertumbuhan bulan ini vs bulan lalu
        $lastMonthJournals = (clone $generalJournalQuery)
            ->whereMonth('transaction_date', now()->subMonth()->month)
            ->whereYear('transaction_date', now()->subMonth()->year)
            ->count();

        $monthlyGrowth = $lastMonthJournals > 0
            ? (($monthlyJournals - $lastMonthJournals) / $lastMonthJournals) * 100
            : ($monthlyJournals > 0 ? 100 : 0);

        // Draft journals count
        $draftJournals = (clone $generalJournalQuery)
            ->where('status', 'draft')
            ->count();

        // Posted journals count
        $postedJournals = (clone $generalJournalQuery)
            ->where('status', 'posted')
            ->count();

        return [
            Stat::make('Total Jurnal Umum', $totalJournals)
                ->description('Semua Jurnal Umum (All Time)')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->chartColor('primary'),

            Stat::make('Bulan Ini', $monthlyJournals)
                ->description(now()->format('F Y') . ' (' . ($monthlyGrowth > 0 ? '+' : '') . number_format($monthlyGrowth, 1) . '%)')
                ->descriptionIcon($monthlyGrowth >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($monthlyGrowth >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->chartColor($monthlyGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Draft', $draftJournals)
                ->description('Jurnal belum di-post')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->chart([3, 7, 5, 12, 8, 9, 6])
                ->chartColor('warning'),

            Stat::make('Posted', $postedJournals)
                ->description('Jurnal sudah di-post')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([10, 15, 20, 8, 12, 18, 25])
                ->chartColor('success'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }

    public function getDisplayName(): string
    {
        return 'Ringkasan Jurnal Umum';
    }

    protected static ?string $pollingInterval = '30s';
}
