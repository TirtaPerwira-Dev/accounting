<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\JurnalPenerimaanKas;
use Carbon\Carbon;
use Illuminate\Support\Number;

class JurnalPenerimaanKasWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 5;

    // Hide from dashboard but keep for resource pages
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        // Total bulan ini
        $thisMonth = JurnalPenerimaanKas::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->get();

        $totalThisMonth = $thisMonth->sum(function ($record) {
            return collect($record->detail_penerimaan ?? [])->sum('jumlah');
        });

        // Total bulan lalu
        $lastMonth = JurnalPenerimaanKas::whereYear('tanggal', now()->subMonth()->year)
            ->whereMonth('tanggal', now()->subMonth()->month)
            ->get();

        $totalLastMonth = $lastMonth->sum(function ($record) {
            return collect($record->detail_penerimaan ?? [])->sum('jumlah');
        });

        // Total tahun ini
        $thisYear = JurnalPenerimaanKas::whereYear('tanggal', now()->year)
            ->get();

        $totalThisYear = $thisYear->sum(function ($record) {
            return collect($record->detail_penerimaan ?? [])->sum('jumlah');
        });

        // Hitung trend
        $monthlyTrend = $totalLastMonth > 0
            ? (($totalThisMonth - $totalLastMonth) / $totalLastMonth * 100)
            : ($totalThisMonth > 0 ? 100 : 0);

        // Rata-rata per hari bulan ini
        $daysInMonth = now()->daysInMonth;
        $currentDay = now()->day;
        $avgPerDay = $currentDay > 0 ? $totalThisMonth / $currentDay : 0;
        $projectedMonth = $avgPerDay * $daysInMonth;

        return [
            // Penerimaan Kas Bulan Ini
            Stat::make('💰 Penerimaan Kas Bulan Ini', 'Rp ' . Number::format($totalThisMonth, 0))
                ->description(
                    $monthlyTrend >= 0
                        ? sprintf('↗️ Naik %.1f%% dari bulan lalu', abs($monthlyTrend))
                        : sprintf('↘️ Turun %.1f%% dari bulan lalu', abs($monthlyTrend))
                )
                ->descriptionIcon($monthlyTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getMonthlyChart()),

            // Transaksi Bulan Ini
            Stat::make('📊 Transaksi JPK Bulan Ini', $thisMonth->count() . ' transaksi')
                ->description('Total transaksi penerimaan kas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            // Total Tahunan
            Stat::make('📈 Total Penerimaan Tahun ' . now()->year, 'Rp ' . Number::format($totalThisYear, 0))
                ->description('Akumulasi penerimaan kas tahun ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            // Proyeksi Bulan Ini
            Stat::make('🎯 Proyeksi Akhir Bulan', 'Rp ' . Number::format($projectedMonth, 0))
                ->description(sprintf('Berdasarkan rata-rata Rp %s/hari', Number::format($avgPerDay, 0)))
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('warning'),
        ];
    }

    /**
     * Get chart data untuk 7 hari terakhir
     */
    private function getMonthlyChart(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $dayTotal = JurnalPenerimaanKas::whereDate('tanggal', $date->toDateString())
                ->get()
                ->sum(function ($record) {
                    return collect($record->detail_penerimaan ?? [])->sum('jumlah');
                });

            $data[] = (int) ($dayTotal / 1000000); // Dalam jutaan untuk chart
        }

        return $data;
    }

    public function getDisplayName(): string
    {
        return 'Statistik Jurnal Penerimaan Kas';
    }
}
