<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Journal;
use App\Models\JournalDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LiquidityRatioChart extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan Pendapatan vs Pengeluaran';
    protected static ?int $sort = 7;
    protected static ?string $pollingInterval = '30s';

    protected static ?array $options = [
        'scales' => [
            'x' => [
                'display' => false,
            ],
            'y' => [
                'display' => false,
            ],
        ],
    ];
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

        // Hitung Pendapatan (Credit di akun pendapatan 8xxx)
        $pendapatan = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
            ->where('kelompoks.no_kel', 'LIKE', '8%') // Pendapatan
            ->where('journals.status', 'posted')
            ->where('journals.transaction_date', '>=', $startDate)
            ->where('journals.transaction_date', '<=', now())
            ->sum('journal_details.credit');

        // Hitung Pengeluaran (Debit di akun beban 9xxx)
        $pengeluaran = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
            ->where('kelompoks.no_kel', 'LIKE', '9%') // Beban/Biaya
            ->where('journals.status', 'posted')
            ->where('journals.transaction_date', '>=', $startDate)
            ->where('journals.transaction_date', '<=', now())
            ->sum('journal_details.debit');

        $pendapatan = max(0, $pendapatan);
        $pengeluaran = max(0, $pengeluaran);

        // Jika tidak ada data sama sekali
        if ($pendapatan === 0 && $pengeluaran === 0) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderWidth' => 2,
                        'borderColor' => '#ffffff',
                    ],
                ],
                'labels' => ['Tidak ada data untuk periode ini'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => [$pendapatan, $pengeluaran],
                    'backgroundColor' => [
                        '#10b981', // Hijau untuk pendapatan
                        '#ef4444', // Merah untuk pengeluaran
                    ],
                    'borderWidth' => 0, // Hilangkan border
                    'borderColor' => 'transparent',
                    'hoverBorderWidth' => 0,
                ],
            ],
            'labels' => [
                'Pendapatan (Rp ' . number_format($pendapatan / 1000000, 1, ',', '.') . 'M)',
                'Pengeluaran (Rp ' . number_format($pengeluaran / 1000000, 1, ',', '.') . 'M)',
            ],
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
