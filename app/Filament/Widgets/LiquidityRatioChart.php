<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\JournalDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LiquidityRatioChart extends ChartWidget
{
    protected static ?string $heading = 'Rasio Likuiditas';
    protected static ?int $sort = 7;
    protected static ?string $pollingInterval = '60s';
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
        // Hitung Aktiva Lancar (kelompok 10)
        $aktivaLancar = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
            ->where('kelompoks.no_kel', '10')
            ->where('journals.status', 'posted')
            ->sum(DB::raw('journal_details.debit - journal_details.credit'));

        // Hitung Kewajiban Jangka Pendek (kelompok 50)
        $kewajibanPendek = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
            ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
            ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
            ->where('kelompoks.no_kel', '50')
            ->where('journals.status', 'posted')
            ->sum(DB::raw('journal_details.credit - journal_details.debit'));

        // Hindari pembagian dengan nol
        $kewajibanPendek = $kewajibanPendek > 0 ? $kewajibanPendek : 1;

        // Hitung rasio
        $currentRatio = $aktivaLancar / $kewajibanPendek;
        $idealRatio = 2.0; // Rasio ideal untuk PDAM

        // Data untuk donut chart
        $liquidAssets = max(0, $aktivaLancar);
        $shortTermLiabilities = max(0, $kewajibanPendek);

        return [
            'datasets' => [
                [
                    'data' => [$liquidAssets, $shortTermLiabilities],
                    'backgroundColor' => [
                        '#10b981', // Hijau untuk aktiva lancar
                        '#ef4444', // Merah untuk kewajiban pendek
                    ],
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => [
                'Aktiva Lancar (Rp ' . number_format($liquidAssets, 0, ',', '.') . ')',
                'Kewajiban Pendek (Rp ' . number_format($shortTermLiabilities, 0, ',', '.') . ')',
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
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label || "";
                            const value = context.parsed;
                            const percentage = ((value / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1);
                            return label + ": " + percentage + "%";
                        }',
                    ],
                ],
            ],
        ];
    }
}
