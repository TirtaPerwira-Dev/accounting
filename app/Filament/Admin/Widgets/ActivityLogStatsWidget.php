<?php

namespace App\Filament\Admin\Widgets;

use Spatie\Activitylog\Models\Activity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ActivityLogStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalLogs = Activity::count();

        // Activity log bulan ini
        $monthlyLogs = Activity::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Activity log hari ini
        $dailyLogs = Activity::whereDate('created_at', Carbon::today())->count();

        return [
            Stat::make('Total Activity Log', $totalLogs)
                ->description('Total semua aktivitas yang tercatat')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Activity Log Bulan Ini', $monthlyLogs)
                ->description('Aktivitas bulan ' . Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Activity Log Hari Ini', $dailyLogs)
                ->description('Aktivitas hari ' . Carbon::now()->translatedFormat('d F Y'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
