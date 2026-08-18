<?php

namespace App\Filament\Accounting\Resources\JurnalPembelianResource\Widgets;

use App\Models\JurnalPembelian;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class JurnalPembelianStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id ?? 1;
        $today = Carbon::today();
        // Total transaksi hari ini
        $todayCount = JurnalPembelian::query()
            ->where('company_id', $companyId)
            ->whereDate('tanggal', $today)
            ->count();

        // Total transaksi bulan ini
        $monthCount = JurnalPembelian::query()
            ->where('company_id', $companyId)
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->count();

        // Total uang bulan ini
        $monthTotal = JurnalPembelian::query()
            ->where('company_id', $companyId)
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->sum('rp');

        // Baris transaksi belum diposting
        $pendingCount = JurnalPembelian::query()
            ->where('company_id', $companyId)
            ->where('is_posted', false)
            ->count();
        $pendingTotal = JurnalPembelian::query()
            ->where('company_id', $companyId)
            ->where('is_posted', false)
            ->sum('rp');

        return [
            Stat::make('Transaksi Hari Ini', $todayCount)
                ->description('Jurnal pembelian hari ini')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Transaksi Bulan Ini', $monthCount)
                ->description('Total transaksi bulan ' . Carbon::now()->format('M Y'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->chart([3, 5, 2, 8, 12, 15, 20])
                ->color('primary'),

            Stat::make('Total Nilai Bulan Ini', 'Rp ' . number_format($monthTotal, 0, ',', '.'))
                ->description('Total pembelian bulan ini')
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart([10, 20, 15, 35, 25, 40, 50])
                ->color('warning'),

            Stat::make('Belum Diposting', $pendingCount)
                ->description('Nilai: Rp ' . number_format($pendingTotal, 0, ',', '.'))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    protected function getPollingInterval(): ?string
    {
        return '30s'; // Refresh every 30 seconds
    }
}
