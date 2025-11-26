<?php

namespace App\Exports;

use App\Models\KodeProyek;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class JurnalPenerimaanKasTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize
{
    public function array(): array
    {
        $sampleKodeProyek = KodeProyek::take(1)->first()?->kode ?? '01';

        return [
            [
                'PKM-001',
                '2024-11-26',
                '10',
                '1101',
                '10',
                '8101',
                '10',
                '5000000',
                'Penerimaan pembayaran rekening air bulan November',
                'Pembayaran pelanggan zona A',
                'PKM-001-01',
                $sampleKodeProyek,
                '3',
            ],
            [
                'PKM-001', // Same transaction
                '2024-11-26',
                '10',
                '1101',
                '10',
                '8102',
                '10',
                '1000000',
                'Penerimaan pembayaran rekening air bulan November',
                'Biaya sambungan baru pelanggan',
                'PKM-001-02',
                '',
                '3',
            ],
            [
                'PKM-002', // New transaction
                '2024-11-26',
                '10',
                '1102',
                '20',
                '8801',
                '10',
                '2500000',
                'Penerimaan bunga deposito bank BPD',
                'Bunga deposito periode November 2024',
                'PKM-002-01',
                '',
                '3',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'nomor_bukti',
            'tanggal',
            'kelompok_kas_bank',
            'rekening_kas_bank',
            'nomor_bantu_kas_bank',
            'rekening_sumber',
            'nomor_bantu_sumber',
            'jumlah',
            'keterangan_umum',
            'keterangan_detail',
            'nomor_bukti_detail',
            'kode_proyek',
            'reff',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFF9800'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // nomor_bukti
            'B' => 12, // tanggal
            'C' => 12, // kelompok_kas_bank (kode)
            'D' => 12, // rekening_kas_bank (kode)
            'E' => 12, // nomor_bantu_kas_bank (kode)
            'F' => 12, // rekening_sumber (kode)
            'G' => 12, // nomor_bantu_sumber (kode)
            'H' => 15, // jumlah
            'I' => 35, // keterangan_umum
            'J' => 30, // keterangan_detail
            'K' => 15, // nomor_bukti_detail
            'L' => 10, // kode_proyek
            'M' => 8,  // reff
        ];
    }
}