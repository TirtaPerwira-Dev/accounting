<?php

namespace App\Filament\Widgets;

use App\Models\JurnalRekeningAir;
use App\Models\JurnalRekeningAirDetail;
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

        $headerQuery = fn() => JurnalRekeningAir::where('company_id', $companyId);

        $totalDebit = JurnalRekeningAirDetail::query()
            ->where('position', 'debit')
            ->whereHas('jurnalRekeningAir', fn($q) => $q->where('company_id', $companyId))
            ->sum('jumlah');

        $totalKredit = JurnalRekeningAirDetail::query()
            ->where('position', 'kredit')
            ->whereHas('jurnalRekeningAir', fn($q) => $q->where('company_id', $companyId))
            ->sum('jumlah');

        $totalBulanan = $headerQuery()
            ->whereYear('tanggal', date('Y'))
            ->whereMonth('tanggal', date('m'))
            ->count();

        $totalTahunan = $headerQuery()
            ->whereYear('tanggal', date('Y'))
            ->count();

        $nominalBulanan = $headerQuery()
            ->whereYear('tanggal', date('Y'))
            ->whereMonth('tanggal', date('m'))
            ->sum('rp');

        $avgPerTransaksi = $totalBulanan > 0 ? ($nominalBulanan / $totalBulanan) : 0;

        $totalUnpostedTahunan = $headerQuery()
            ->whereYear('tanggal', date('Y'))
            ->where('is_posted', false)
            ->count();

        $formatCurrency = fn(float $value): string => 'Rp ' . number_format($value, 0, ',', '.');

        return [
            Stat::make('Total Debit vs Kredit', $formatCurrency((float) $totalDebit))
                ->description('Kredit: ' . $formatCurrency((float) $totalKredit))
                ->descriptionIcon('heroicon-m-scale')
                ->color('primary'),

            Stat::make('Transaksi Bulan/Tahun', number_format((float) $totalBulanan, 0, ',', '.') . ' / ' . number_format((float) $totalTahunan, 0, ',', '.') . ' transaksi')
                ->description('Bulan berjalan dibanding total tahun ini')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Rata-rata per Transaksi', $formatCurrency((float) $avgPerTransaksi))
                ->description('Rata-rata nominal bulan berjalan')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),

            Stat::make('Status Posting', number_format((float) $totalTahunan, 0, ',', '.') . ' / ' . number_format((float) $totalUnpostedTahunan, 0, ',', '.'))
                ->description('Tahunan Terinput / Belum Diposting')
                ->descriptionIcon($totalUnpostedTahunan > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($totalUnpostedTahunan > 0 ? 'warning' : 'success'),
        ];
    }

}
