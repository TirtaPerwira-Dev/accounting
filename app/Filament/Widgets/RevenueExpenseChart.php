<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan vs Pengeluaran';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = '6months';

    protected function getFilters(): ?array
    {
        return [
            '3months' => '3 Bulan Terakhir',
            '6months' => '6 Bulan Terakhir',
            '12months' => '1 Tahun Terakhir',
        ];
    }

    protected function getData(): array
    {
        $months = [];
        $pendapatan = [];
        $pengeluaran = [];

        // Determine period based on filter
        $period = match ($this->filter) {
            '3months' => 3,
            '12months' => 12,
            default => 6,
        };

        // Get data for selected period
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;

            $months[] = $date->format('M Y');

            // Pendapatan (Credit di akun 8xxx)
            $pendapatanBulan = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
                ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
                ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
                ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
                ->where('kelompoks.no_kel', 'LIKE', '8%')
                ->where('journals.status', 'posted')
                ->whereYear('journals.transaction_date', $year)
                ->whereMonth('journals.transaction_date', $month)
                ->sum('journal_details.credit');

            // Pengeluaran (Debit di akun 9xxx)
            $pengeluaranBulan = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
                ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
                ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
                ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
                ->where('kelompoks.no_kel', 'LIKE', '9%')
                ->where('journals.status', 'posted')
                ->whereYear('journals.transaction_date', $year)
                ->whereMonth('journals.transaction_date', $month)
                ->sum('journal_details.debit');

            $pendapatan[] = (float) $pendapatanBulan;
            $pengeluaran[] = (float) $pengeluaranBulan;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $pendapatan,
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b98120',
                    'fill' => true,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $pengeluaran,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => '#ef444420',
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
        ];
    }
}
