<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LaporanKeuanganExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected string $reportType;
    protected array $reportData;

    public function __construct(string $reportType, array $reportData)
    {
        $this->reportType = $reportType;
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return match ($this->reportType) {
            'neraca' => $this->collectionNeraca(),
            'laba_rugi' => $this->collectionLabaRugi(),
            'trial_balance' => $this->collectionTrialBalance(),
            'buku_besar' => $this->collectionBukuBesar(),
            default => collect([]),
        };
    }

    public function headings(): array
    {
        $title = $this->reportData['title'] ?? 'LAPORAN KEUANGAN';
        $periode = $this->reportData['periode'] ?? '';

        return match ($this->reportType) {
            'neraca' => [
                [$title],
                [$periode],
                [],
                ['Kode', 'Akun', 'Saldo'],
            ],
            'laba_rugi' => [
                [$title],
                [$periode],
                [],
                ['Kode', 'Akun', 'Saldo'],
            ],
            'trial_balance' => [
                [$title],
                [$periode],
                [],
                ['Kode', 'Nama Rekening', 'Debit', 'Kredit'],
            ],
            'buku_besar' => [
                [$title],
                [$periode],
                [],
            ],
            default => [[$title], [$periode]],
        };
    }

    protected function collectionNeraca()
    {
        $data = collect();

        // AKTIVA
        $data->push(['', 'AKTIVA', '']);
        foreach ($this->reportData['aktiva'] ?? [] as $item) {
            $data->push([
                $item['kode'],
                $item['nama'],
                number_format($item['saldo'], 0, ',', '.')
            ]);
        }
        $data->push([
            '',
            'TOTAL AKTIVA',
            number_format($this->reportData['total_aktiva'] ?? 0, 0, ',', '.')
        ]);

        // Spacing
        $data->push(['', '', '']);

        // PASIVA
        $data->push(['', 'PASIVA', '']);
        foreach ($this->reportData['pasiva'] ?? [] as $item) {
            $data->push([
                $item['kode'],
                $item['nama'],
                number_format($item['saldo'], 0, ',', '.')
            ]);
        }
        $data->push([
            '',
            'TOTAL PASIVA',
            number_format($this->reportData['total_pasiva'] ?? 0, 0, ',', '.')
        ]);

        return $data;
    }

    protected function collectionLabaRugi()
    {
        $data = collect();

        // PENDAPATAN
        $data->push(['', 'PENDAPATAN', '']);
        foreach ($this->reportData['pendapatan'] ?? [] as $item) {
            $data->push([
                $item['kode'],
                $item['nama'],
                number_format($item['saldo'], 0, ',', '.')
            ]);
        }
        $data->push([
            '',
            'Total Pendapatan',
            number_format($this->reportData['total_pendapatan'] ?? 0, 0, ',', '.')
        ]);

        // Spacing
        $data->push(['', '', '']);

        // BEBAN
        $data->push(['', 'BEBAN', '']);
        foreach ($this->reportData['beban'] ?? [] as $item) {
            $data->push([
                $item['kode'],
                $item['nama'],
                number_format($item['saldo'], 0, ',', '.')
            ]);
        }
        $data->push([
            '',
            'Total Beban',
            number_format($this->reportData['total_beban'] ?? 0, 0, ',', '.')
        ]);

        // Spacing
        $data->push(['', '', '']);

        // LABA/RUGI
        $data->push([
            '',
            $this->reportData['status'] ?? 'LABA/RUGI BERSIH',
            number_format(abs($this->reportData['laba_rugi'] ?? 0), 0, ',', '.')
        ]);

        return $data;
    }

    protected function collectionTrialBalance()
    {
        $data = collect();

        foreach ($this->reportData['data'] ?? [] as $item) {
            $data->push([
                $item['kode'],
                $item['nama'],
                $item['debit'] > 0 ? number_format($item['debit'], 0, ',', '.') : '-',
                $item['kredit'] > 0 ? number_format($item['kredit'], 0, ',', '.') : '-',
            ]);
        }

        // Total
        $data->push([
            '',
            'TOTAL',
            number_format($this->reportData['total_debit'] ?? 0, 0, ',', '.'),
            number_format($this->reportData['total_kredit'] ?? 0, 0, ',', '.'),
        ]);

        return $data;
    }

    protected function collectionBukuBesar()
    {
        $data = collect();

        foreach ($this->reportData['data'] ?? [] as $rekening) {
            // Header Rekening
            $data->push([
                $rekening['kode'],
                $rekening['nama'],
                '',
                '',
                'Saldo Akhir: ' . number_format($rekening['saldo_akhir'] ?? 0, 0, ',', '.'),
            ]);
            
            // Header Tabel
            $data->push([
                'Tanggal',
                'Jenis Transaksi',
                'Debit',
                'Kredit',
                'Saldo',
            ]);
            
            // Transaksi
            foreach ($rekening['transaksi'] ?? [] as $tr) {
                $data->push([
                    \Carbon\Carbon::parse($tr['tanggal'])->format('d/m/Y'),
                    $tr['jenis'],
                    $tr['debit'] > 0 ? number_format($tr['debit'], 0, ',', '.') : '-',
                    $tr['kredit'] > 0 ? number_format($tr['kredit'], 0, ',', '.') : '-',
                    number_format($tr['saldo'], 0, ',', '.'),
                ]);
            }
            
            // Total
            $data->push([
                '',
                'TOTAL',
                number_format($rekening['total_debit'] ?? 0, 0, ',', '.'),
                number_format($rekening['total_kredit'] ?? 0, 0, ',', '.'),
                number_format($rekening['saldo_akhir'] ?? 0, 0, ',', '.'),
            ]);
            
            // Spacing
            $data->push(['', '', '', '', '']);
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Title styling (Row 1)
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Periode styling (Row 2)
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => [
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Header styling (Row 4)
        $sheet->getStyle('A4:D4')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // All data borders
        $sheet->getStyle("A4:D{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 40,
            'C' => 20,
            'D' => 20,
        ];
    }

    public function title(): string
    {
        return match ($this->reportType) {
            'neraca' => 'Neraca',
            'laba_rugi' => 'Laba Rugi',
            'trial_balance' => 'Trial Balance',
            'buku_besar' => 'Buku Besar',
            default => 'Laporan',
        };
    }
}
