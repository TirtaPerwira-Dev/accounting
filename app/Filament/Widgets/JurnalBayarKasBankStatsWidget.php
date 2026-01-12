<?php

namespace App\Filament\Widgets;

use App\Models\JurnalBayarKasBank;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class JurnalBayarKasBankStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id ?? 1;
        $baseQuery = JurnalBayarKasBank::where('company_id', $companyId);

        $thisMonth = $baseQuery->whereYear('tanggal', date('Y'))->whereMonth('tanggal', date('m'));
        $totalThisMonth = $thisMonth->sum('rp');
        $countThisMonth = $thisMonth->count();
        $pending = $baseQuery->where('is_confirmed', false)->count();

        return [
            Stat::make('Total Bayar Kas/Bank Bulan Ini', 'Rp ' . number_format($totalThisMonth, 0, ',', '.'))
                ->description('Bulan ' . date('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
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
