<?php

namespace App\Filament\Admin\Widgets;

use Spatie\Activitylog\Models\Activity;
use Filament\Widgets\ChartWidget;

class ActivityLogEventChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Activity Log by Event';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $eventCounts = Activity::query()
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Activity Events',
                    'data' => array_values($eventCounts),
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',   // green - created
                        'rgba(251, 146, 60, 0.8)',  // orange - updated
                        'rgba(239, 68, 68, 0.8)',   // red - deleted
                        'rgba(59, 130, 246, 0.8)',  // blue - other
                        'rgba(168, 85, 247, 0.8)',  // purple
                        'rgba(236, 72, 153, 0.8)',  // pink
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(251, 146, 60)',
                        'rgb(239, 68, 68)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_map('ucfirst', array_keys($eventCounts)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }
}
