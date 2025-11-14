<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class WelcomeWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    protected static ?string $pollingInterval = '10s';
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $user = Auth::user();

        // Get greeting based on time
        $hour = $now->hour;
        $greeting = match (true) {
            $hour >= 5 && $hour < 11 => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 18 => 'Selamat Sore',
            default => 'Selamat Malam'
        };

        // Format date and time in Indonesian
        $hari = $now->locale('id')->dayName;
        $tanggal = $now->format('d');
        $bulan = $now->locale('id')->monthName;
        $tahun = $now->format('Y');
        $jam = $now->format('H:i:s');

        $fullDateTime = "{$hari}, {$tanggal} {$bulan} {$tahun} • {$jam} WIB";
        $greetingMessage = $greeting . ', ' . $user->name . '!';

        return [
            Stat::make($fullDateTime, $greetingMessage)
                ->description('Selamat bekerja!! Kerja!! Kerja!! Kerja!!')
                ->color('success')
                ->extraAttributes([
                    'class' => 'text-center'
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 1;
    }
}
