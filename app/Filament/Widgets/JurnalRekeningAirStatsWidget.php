<?php

namespace App\Filament\Widgets;

use App\Models\JurnalRekeningAir;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JurnalRekeningAirStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    // Hide from dashboard but keep for resource pages
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id ?? 1;

        // Query dasar - gunakan closure untuk clone setiap kali
        $baseQuery = fn() => JurnalRekeningAir::where('company_id', $companyId);

        // Total piutang air bulan ini
        $totalPiutangAir = $baseQuery()
            ->whereYear('tanggal', date('Y'))
            ->whereMonth('tanggal', date('m'))
            ->sum('rp');

        // Total bulan lalu
        $lastMonth = date('m') == 1 ? 12 : date('m') - 1;
        $lastMonthYear = date('m') == 1 ? date('Y') - 1 : date('Y');
        $totalPiutangLastMonth = $baseQuery()
            ->whereYear('tanggal', $lastMonthYear)
            ->whereMonth('tanggal', $lastMonth)
            ->sum('rp');

        // Total transaksi air tahun ini
        $totalTransaksiTahun = $baseQuery()
            ->whereYear('tanggal', date('Y'))
            ->count();

        // Count dan sum untuk rata-rata bulan ini
        $countThisMonth = $baseQuery()
            ->whereYear('tanggal', date('Y'))
            ->whereMonth('tanggal', date('m'))
            ->count();
        $avgValueThisMonth = $countThisMonth > 0 ? $totalPiutangAir / $countThisMonth : 0;

        // Transaksi air yang belum dikonfirmasi (pending)
        $pendingAir = $baseQuery()->where('is_confirmed', false)->count();

        return [
            Stat::make('Total Piutang Air Bulan Ini', 'Rp ' . number_format($totalPiutangAir, 0, ',', '.'))
                ->description($this->getChangeDescription($totalPiutangAir, $totalPiutangLastMonth, 'dari bulan lalu'))
                ->descriptionIcon($this->getChangeIcon($totalPiutangAir, $totalPiutangLastMonth))
                ->color($this->getChangeColor($totalPiutangAir, $totalPiutangLastMonth))
                ->chart($this->getPiutangChart()),

            Stat::make('Transaksi Air Tahun Ini', $totalTransaksiTahun)
                ->description('Total rekening air ' . date('Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart($this->getYearlyChart()),

            Stat::make('Rata-rata per Transaksi', 'Rp ' . number_format($avgValueThisMonth, 0, ',', '.'))
                ->description('Bulan ' . date('F Y'))
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),

            Stat::make('Belum Dikonfirmasi', $pendingAir)
                ->description($pendingAir > 0 ? 'Rekening air perlu konfirmasi' : 'Semua sudah dikonfirmasi')
                ->descriptionIcon($pendingAir > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pendingAir > 0 ? 'warning' : 'success'),
        ];
    }

    private function getChangeDescription($current, $previous, $suffix = ''): string
    {
        if ($previous == 0) {
            return $current > 0 ? "Baru $suffix" : "Belum ada data";
        }

        $change = $current - $previous;
        $percentage = round(($change / $previous) * 100, 1);

        if ($change > 0) {
            return "+{$percentage}% dari bulan lalu";
        } elseif ($change < 0) {
            return "{$percentage}% dari bulan lalu";
        } else {
            return "Sama dengan bulan lalu";
        }
    }

    private function getChangeIcon($current, $previous): string
    {
        if ($previous == 0) {
            return 'heroicon-m-plus';
        }

        return $current > $previous ? 'heroicon-m-arrow-trending-up' : ($current < $previous ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus');
    }

    private function getChangeColor($current, $previous): string
    {
        if ($previous == 0) {
            return 'info';
        }

        return $current > $previous ? 'success' : ($current < $previous ? 'danger' : 'gray');
    }

    private function getPiutangChart(): array
    {
        $data = [];
        $companyId = auth()->user()?->company_id ?? 1;

        // Data 7 hari terakhir untuk nilai piutang
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $value = JurnalRekeningAir::where('company_id', $companyId)
                ->whereDate('tanggal', $date)
                ->sum('rp') / 1000000; // Dalam jutaan
            $data[] = round($value, 2);
        }

        return $data;
    }

    private function getYearlyChart(): array
    {
        $data = [];
        $companyId = auth()->user()?->company_id ?? 1;

        // Data 12 bulan terakhir untuk trend tahunan
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = JurnalRekeningAir::where('company_id', $companyId)
                ->whereYear('tanggal', $date->year)
                ->whereMonth('tanggal', $date->month)
                ->count();
            $data[] = $count;
        }

        return $data;
    }
}
