<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RoleStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Super Admin
        $superAdminCount = User::role('super_admin')->count();

        // Direktur (Direktur Utama + Direktur Umum)
        $direkturCount = User::role(['direktur_utama', 'direktur_umum'])->count();

        // Kepala Bagian & Sub Bagian
        $kepalaBagianCount = User::role(['kepala_bagian', 'kepala_sub_bagian', 'kepala_sub_bagian_anggaran_pendapatan', 'kepala_sub_bagian_verifikasi_pembukuan'])->count();

        // Staff
        $staffCount = User::role(['staff', 'staff_anggaran_pendapatan', 'staff_verifikasi_pembukuan'])->count();

        return [
            Stat::make('Super Admin', $superAdminCount)
                ->description('Pengguna dengan role Super Admin')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger'),

            Stat::make('Direktur', $direkturCount)
                ->description('Direktur Utama & Direktur Umum')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('warning'),

            Stat::make('Kepala Bagian/Sub', $kepalaBagianCount)
                ->description('Kepala Bagian & Kepala Sub Bagian')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Staff', $staffCount)
                ->description('Semua Staff')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),
        ];
    }
}
