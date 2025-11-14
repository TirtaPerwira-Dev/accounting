<?php

namespace App\Filament\Resources\PengeluaranJournalResource\Widgets;

use App\Models\Journal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PengeluaranStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Query untuk jurnal pengeluaran
        $pengeluaranQuery = Journal::where('transaction_type', Journal::TYPE_PENGELUARAN);

        // Draft Monthly (bulan ini)
        $draftMonthly = (clone $pengeluaranQuery)
            ->where('status', 'draft')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->count();

        // Posted Monthly (bulan ini)
        $postedMonthly = (clone $pengeluaranQuery)
            ->where('status', 'posted')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->count();

        // Draft Daily (hari ini)
        $draftDaily = (clone $pengeluaranQuery)
            ->where('status', 'draft')
            ->whereDate('transaction_date', now()->toDateString())
            ->count();

        // Posted Daily (hari ini)
        $postedDaily = (clone $pengeluaranQuery)
            ->where('status', 'posted')
            ->whereDate('transaction_date', now()->toDateString())
            ->count();

        // Amount calculations
        $draftMonthlyAmount = (clone $pengeluaranQuery)
            ->where('status', 'draft')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('total_amount') ?? 0;

        $postedMonthlyAmount = (clone $pengeluaranQuery)
            ->where('status', 'posted')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('total_amount') ?? 0;

        $draftDailyAmount = (clone $pengeluaranQuery)
            ->where('status', 'draft')
            ->whereDate('transaction_date', now()->toDateString())
            ->sum('total_amount') ?? 0;

        $postedDailyAmount = (clone $pengeluaranQuery)
            ->where('status', 'posted')
            ->whereDate('transaction_date', now()->toDateString())
            ->sum('total_amount') ?? 0;

        return [
            Stat::make('Draft (Monthly)', $draftMonthly)
                ->description(now()->format('F Y') . ' - Rp ' . number_format($draftMonthlyAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->chart([3, 5, 2, 7, 4, 6, 8])
                ->chartColor('warning'),

            Stat::make('Posted (Monthly)', $postedMonthly)
                ->description(now()->format('F Y') . ' - Rp ' . number_format($postedMonthlyAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('danger')
                ->chart([25, 20, 18, 15, 12, 10, 8])
                ->chartColor('danger'),

            Stat::make('Draft (Daily)', $draftDaily)
                ->description(now()->format('d M Y') . ' - Rp ' . number_format($draftDailyAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('gray')
                ->chart([1, 2, 0, 3, 1, 2, 4])
                ->chartColor('gray'),

            Stat::make('Posted (Daily)', $postedDaily)
                ->description(now()->format('d M Y') . ' - Rp ' . number_format($postedDailyAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('orange')
                ->chart([15, 12, 10, 8, 6, 4, 2])
                ->chartColor('orange'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }

    public function getDisplayName(): string
    {
        return 'Ringkasan Jurnal Pengeluaran';
    }

    protected static ?string $pollingInterval = '30s';
}
