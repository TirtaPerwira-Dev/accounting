<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Journal;
use App\Models\JournalDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Pengeluaran Per Kategori';
    protected static ?int $sort = 8;
    protected static ?string $pollingInterval = '30s';
    // protected int | string | array $columnSpan = [
    //     'md' => 6,
    //     'xl' => 6,
    // ];

    public ?string $filter = '1_bulan';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return [
            '1_bulan' => '1 Bulan',
            '3_bulan' => '3 Bulan',
            '6_bulan' => '6 Bulan',
            '1_tahun' => '1 Tahun',
        ];
    }

    protected function getData(): array
    {
        $periods = [
            '1_bulan' => now()->subMonth(),
            '3_bulan' => now()->subMonths(3),
            '6_bulan' => now()->subMonths(6),
            '1_tahun' => now()->subYear(),
        ];

        $startDate = $periods[$this->filter] ?? $periods['1_bulan'];

        // Kategori pengeluaran berdasarkan kelompok
        $categories = [
            '91' => 'Biaya Sumber Air',
            '92' => 'Biaya Pengolahan Air',
            '93' => 'Biaya Transmisi & Distribusi',
            '96' => 'Biaya Administrasi & Umum',
            '98' => 'Biaya Diluar Usaha',
        ];

        $data = [];
        $labels = [];
        $colors = ['#ef4444', '#f97316', '#eab308', '#06d6a0', '#1e40af'];
        $backgroundColors = [];
        $totalPengeluaran = 0;

        foreach ($categories as $kelompokNo => $categoryName) {
            // Hitung Pengeluaran per kategori
            $pengeluaran = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
                ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
                ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
                ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
                ->where('kelompoks.no_kel', $kelompokNo)
                ->where('journals.status', 'posted')
                ->where('journals.transaction_date', '>=', $startDate)
                ->where('journals.transaction_date', '<=', now())
                ->sum('journal_details.debit');

            $pengeluaran = max(0, $pengeluaran);

            if ($pengeluaran > 0) {
                $data[] = $pengeluaran;
                $labels[] = $categoryName . ' (Rp ' . number_format($pengeluaran / 1000000, 1, ',', '.') . 'M)';
                $backgroundColors[] = $colors[count($data) - 1] ?? '#6b7280';
                $totalPengeluaran += $pengeluaran;
            }
        }

        // Jika tidak ada data, tampilkan placeholder
        if ($totalPengeluaran === 0) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderWidth' => 2,
                        'borderColor' => '#ffffff',
                    ],
                ],
                'labels' => ['Tidak ada pengeluaran untuk periode ini'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 0, // Hilangkan border
                    'borderColor' => 'transparent',
                    'hoverBorderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '50%', // Membuat donut hole
            'layout' => [
                'padding' => 0,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                        'boxWidth' => 12,
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#ffffff',
                    'borderColor' => 'transparent',
                    'borderWidth' => 0,
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label || "";
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            const amount = (value / 1000000).toFixed(1);
                            return label.split(" (")[0] + ": " + percentage + "% (Rp " + amount + "M)";
                        }',
                    ],
                ],
                'datalabels' => [
                    'display' => false, // Hilangkan data labels
                ],
            ],
            'animation' => [
                'animateRotate' => true,
                'animateScale' => false,
                'duration' => 1000,
            ],
        ];
    }
}
