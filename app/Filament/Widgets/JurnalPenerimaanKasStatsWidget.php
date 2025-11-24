<?php

namespace App\Filament\Widgets;

use App\Models\JurnalPenerimaanKas;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class JurnalPenerimaanKasStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    // Hide from dashboard but keep for resource pages
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        // Get current month and year
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $lastMonth = now()->subMonth()->month;
        $lastMonthYear = now()->subMonth()->year;

        // Total Penerimaan Bulan Ini
        $currentMonthTotal = JurnalPenerimaanKas::whereMonth('tanggal', $currentMonth)
            ->whereYear('tanggal', $currentYear)
            ->get()
            ->sum(function ($record) {
                return collect($record->detail_penerimaan ?? [])->sum('jumlah');
            });

        // Total Penerimaan Bulan Lalu untuk perbandingan
        $lastMonthTotal = JurnalPenerimaanKas::whereMonth('tanggal', $lastMonth)
            ->whereYear('tanggal', $lastMonthYear)
            ->get()
            ->sum(function ($record) {
                return collect($record->detail_penerimaan ?? [])->sum('jumlah');
            });

        // Hitung persentase perubahan
        $percentageChange = $lastMonthTotal > 0
            ? (($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100
            : 0;

        // Jumlah Transaksi Bulan Ini
        $currentMonthTransactions = JurnalPenerimaanKas::whereMonth('tanggal', $currentMonth)
            ->whereYear('tanggal', $currentYear)
            ->count();

        // Jumlah Transaksi Bulan Lalu
        $lastMonthTransactions = JurnalPenerimaanKas::whereMonth('tanggal', $lastMonth)
            ->whereYear('tanggal', $lastMonthYear)
            ->count();

        // Persentase perubahan transaksi
        $transactionChange = $lastMonthTransactions > 0
            ? (($currentMonthTransactions - $lastMonthTransactions) / $lastMonthTransactions) * 100
            : 0;

        // Total Penerimaan Tahun Ini
        $currentYearTotal = JurnalPenerimaanKas::whereYear('tanggal', $currentYear)
            ->get()
            ->sum(function ($record) {
                return collect($record->detail_penerimaan ?? [])->sum('jumlah');
            });

        // Rata-rata Penerimaan per Transaksi
        $avgPerTransaction = $currentMonthTransactions > 0
            ? $currentMonthTotal / $currentMonthTransactions
            : 0;

        return [
            Stat::make('Total Penerimaan Bulan Ini', 'Rp ' . number_format($currentMonthTotal, 0, ',', '.'))
                ->description(
                    $percentageChange >= 0
                        ? '+' . number_format(abs($percentageChange), 1) . '% dari bulan lalu'
                        : '-' . number_format(abs($percentageChange), 1) . '% dari bulan lalu'
                )
                ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($percentageChange >= 0 ? 'success' : 'danger')
                ->chart($this->getMonthlyChart()),

            Stat::make('Jumlah Transaksi', number_format($currentMonthTransactions))
                ->description(
                    $transactionChange >= 0
                        ? '+' . number_format(abs($transactionChange), 1) . '% transaksi'
                        : '-' . number_format(abs($transactionChange), 1) . '% transaksi'
                )
                ->descriptionIcon($transactionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($transactionChange >= 0 ? 'success' : 'warning')
                ->chart($this->getTransactionChart()),

            Stat::make('Total Tahun ' . $currentYear, 'Rp ' . number_format($currentYearTotal, 0, ',', '.'))
                ->description('Akumulasi penerimaan kas/bank')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart($this->getYearlyChart()),

            Stat::make('Rata-rata per Transaksi', 'Rp ' . number_format($avgPerTransaction, 0, ',', '.'))
                ->description('Penerimaan rata-rata bulan ini')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }

    private function getMonthlyChart(): array
    {
        // Chart untuk 7 hari terakhir
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $total = JurnalPenerimaanKas::whereDate('tanggal', $date)
                ->get()
                ->sum(function ($record) {
                    return collect($record->detail_penerimaan ?? [])->sum('jumlah');
                });
            $data[] = $total / 1000; // Dalam ribuan untuk chart
        }
        return $data;
    }

    private function getTransactionChart(): array
    {
        // Chart jumlah transaksi 7 hari terakhir
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = JurnalPenerimaanKas::whereDate('tanggal', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getYearlyChart(): array
    {
        // Chart bulanan tahun ini
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $total = JurnalPenerimaanKas::whereMonth('tanggal', $month)
                ->whereYear('tanggal', now()->year)
                ->get()
                ->sum(function ($record) {
                    return collect($record->detail_penerimaan ?? [])->sum('jumlah');
                });
            $data[] = $total / 1000000; // Dalam jutaan untuk chart
        }
        return $data;
    }
}
