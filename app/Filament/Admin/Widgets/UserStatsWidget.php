<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = User::whereNull('email_verified_at')->count();

        return [
            Stat::make('Total Pengguna', $totalUsers)
                ->description('Semua pengguna yang terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pengguna Terverifikasi', $verifiedUsers)
                ->description('Pengguna dengan email terverifikasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pengguna Belum Terverifikasi', $unverifiedUsers)
                ->description('Pengguna belum verifikasi email')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }
}
