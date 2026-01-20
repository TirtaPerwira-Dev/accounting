<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserRoleChartWidget extends ChartWidget
{
    protected static ?string $heading = 'User Distribution by Role';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        // Get user counts by role
        $roleCounts = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'name')
            ->toArray();

        // Format role names for display
        $labels = array_map(function ($role) {
            return str_replace('_', ' ', ucwords($role, '_'));
        }, array_keys($roleCounts));

        return [
            'datasets' => [
                [
                    'label' => 'Users by Role',
                    'data' => array_values($roleCounts),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',   // red - super admin
                        'rgba(251, 146, 60, 0.8)',  // orange - direktur
                        'rgba(250, 204, 21, 0.8)',  // yellow - kabag
                        'rgba(34, 197, 94, 0.8)',   // green - kasub
                        'rgba(59, 130, 246, 0.8)',  // blue - staff
                        'rgba(168, 85, 247, 0.8)',  // purple
                        'rgba(236, 72, 153, 0.8)',  // pink
                        'rgba(156, 163, 175, 0.8)', // gray
                        'rgba(20, 184, 166, 0.8)',  // teal
                        'rgba(245, 158, 11, 0.8)',  // amber
                    ],
                    'borderColor' => [
                        'rgb(239, 68, 68)',
                        'rgb(251, 146, 60)',
                        'rgb(250, 204, 21)',
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)',
                        'rgb(156, 163, 175)',
                        'rgb(20, 184, 166)',
                        'rgb(245, 158, 11)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
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
