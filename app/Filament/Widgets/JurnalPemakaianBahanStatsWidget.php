<?php

namespace App\Filament\Widgets;

use App\Models\JurnalPemakaianBahan;
use App\Models\JurnalPemakaianBahanDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JurnalPemakaianBahanStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isDiscovered = false;
    protected int $columns = 5;

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id ?? 1;

        // Total Debit bulan ini
        $totalDebit = JurnalPemakaianBahanDetail::whereHas('jurnalPemakaianBahan', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereYear('tanggal', date('Y'))
              ->whereMonth('tanggal', date('m'));
        })->whereNotNull('rekening_debit_id')->sum('jumlah');

        // Total Kredit bulan ini
        $totalKredit = JurnalPemakaianBahanDetail::whereHas('jurnalPemakaianBahan', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereYear('tanggal', date('Y'))
              ->whereMonth('tanggal', date('m'));
        })->whereNotNull('rekening_kredit_id')->sum('jumlah');

        // Jumlah transaksi bulan ini
        $countThisMonth = JurnalPemakaianBahan::where('company_id', $companyId)
            ->whereYear('tanggal', date('Y'))
            ->whereMonth('tanggal', date('m'))
            ->count();

        // Belum dikonfirmasi
        $pending = JurnalPemakaianBahan::where('company_id', $companyId)
            ->where('is_confirmed', false)
            ->count();

        // Belum diposting
        $notPosted = JurnalPemakaianBahan::where('company_id', $companyId)
            ->where('is_confirmed', true)
            ->where('is_posted', false)
            ->count();

        return [
            Stat::make('Total Debit Bulan Ini', 'Rp ' . number_format($totalDebit, 0, ',', '.'))
                ->description('Bulan ' . date('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Total Kredit Bulan Ini', 'Rp ' . number_format($totalKredit, 0, ',', '.'))
                ->description('Bulan ' . date('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('success'),

            Stat::make('Jumlah Transaksi', $countThisMonth)
                ->description('Transaksi bulan ini')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Belum Dikonfirmasi', $pending)
                ->description($pending > 0 ? 'Perlu konfirmasi' : 'Semua sudah dikonfirmasi')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Belum Diposting', $notPosted)
                ->description($notPosted > 0 ? 'Perlu posting ke Buku Besar' : 'Semua sudah diposting')
                ->descriptionIcon($notPosted > 0 ? 'heroicon-m-arrow-up-tray' : 'heroicon-m-check-badge')
                ->color($notPosted > 0 ? 'danger' : 'success'),
        ];
    }
}
