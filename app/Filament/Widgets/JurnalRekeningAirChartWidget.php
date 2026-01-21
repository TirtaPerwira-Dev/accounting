<?php

namespace App\Filament\Widgets;

use App\Models\JurnalRekeningAir;
use Filament\Widgets\ChartWidget;

class JurnalRekeningAirChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Trend Jurnal Rekening Air (6 Bulan Terakhir)';

    protected static string $color = 'info';

    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 3;

    // Hide from dashboard but keep for resource pages
    protected static bool $isDiscovered = false;

    protected function getData(): array
    {
        $companyId = auth()->user()?->company_id ?? 1;

        $data = [];
        $labels = [];

        // Data 6 bulan terakhir
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $count = JurnalRekeningAir::where('company_id', $companyId)
                ->whereYear('tanggal', $date->year)
                ->whereMonth('tanggal', $date->month)
                ->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Transaksi',
                    'data' => $data,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
