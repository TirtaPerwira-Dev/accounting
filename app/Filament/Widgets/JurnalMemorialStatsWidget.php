<?php

namespace App\Filament\Widgets;

use App\Models\JurnalMemorial;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JurnalMemorialStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id ?? 1;
        $baseQuery = JurnalMemorial::where('company_id', $companyId);

        $thisMonth = $baseQuery->whereYear('tanggal', date('Y'))->whereMonth('tanggal', date('m'));
        $totalThisMonth = $thisMonth->sum('rp');
        $countThisMonth = $thisMonth->count();
        $pending = $baseQuery->where('is_confirmed', false)->count();

        return [
            Stat::make('Total Memorial Bulan Ini', 'Rp ' . number_format($totalThisMonth, 0, ',', '.'))
                ->description('Bulan ' . date('F Y'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Jumlah Transaksi', $countThisMonth)
                ->description('Transaksi bulan ini')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Belum Dikonfirmasi', $pending)
                ->description($pending > 0 ? 'Perlu konfirmasi' : 'Semua sudah dikonfirmasi')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'success'),
        ];
    }
}
