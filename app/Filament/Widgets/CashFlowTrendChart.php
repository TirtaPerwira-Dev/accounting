<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashFlowTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Saldo Kas & Bank';
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = '60s';
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = '6months';

    protected function getFilters(): ?array
    {
        return [
            '3months' => '3 Bulan',
            '6months' => '6 Bulan',
            '12months' => '12 Bulan',
        ];
    }

    protected function getData(): array
    {
        $months = [];
        $saldoKas = [];

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

            // Calculate cumulative cash balance up to this month
            $saldoBulan = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
                ->join('nomor_bantus', 'journal_details.nomor_bantu_id', '=', 'nomor_bantus.id')
                ->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
                ->where('rekenings.no_rek', '1101') // Kas
                ->where('journals.status', 'posted')
                ->where(function ($query) use ($year, $month) {
                    $query->where(
                        'journals.transaction_date',
                        '<=',
                        Carbon::create($year, $month)->endOfMonth()
                    );
                })
                ->sum(DB::raw('journal_details.debit - journal_details.credit'));

            $saldoKas[] = (float) $saldoBulan;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Saldo Kas & Bank',
                    'data' => $saldoKas,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f620',
                    'fill' => true,
                    'tension' => 0.4,
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
