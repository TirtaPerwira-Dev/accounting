<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Journal;
use Carbon\Carbon;

class TransactionTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Jenis Transaksi (Bulan Ini)';
    protected static ?int $sort = 8;
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Hitung jumlah transaksi per jenis bulan ini
        $penerimaan = Journal::where('transaction_type', 'penerimaan')
            ->where('status', 'posted')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->count();

        $pengeluaran = Journal::where('transaction_type', 'pengeluaran')
            ->where('status', 'posted')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->count();

        $penyesuaian = Journal::where('transaction_type', 'penyesuaian')
            ->where('status', 'posted')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->count();

        $penutupan = Journal::where('transaction_type', 'penutupan')
            ->where('status', 'posted')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->count();

        // Hitung total untuk persentase
        $total = $penerimaan + $pengeluaran + $penyesuaian + $penutupan;

        // Jika tidak ada data, tampilkan placeholder
        if ($total === 0) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderWidth' => 2,
                        'borderColor' => '#ffffff',
                    ],
                ],
                'labels' => ['Tidak ada transaksi bulan ini'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => [$penerimaan, $pengeluaran, $penyesuaian, $penutupan],
                    'backgroundColor' => [
                        '#10b981', // Hijau untuk penerimaan
                        '#ef4444', // Merah untuk pengeluaran
                        '#f59e0b', // Kuning untuk penyesuaian
                        '#6b7280', // Abu untuk penutupan
                    ],
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => [
                'Penerimaan (' . $penerimaan . ')',
                'Pengeluaran (' . $pengeluaran . ')',
                'Penyesuaian (' . $penyesuaian . ')',
                'Penutupan (' . $penutupan . ')',
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label || "";
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ": " + percentage + "%";
                        }',
                    ],
                ],
            ],
        ];
    }
}
