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

class JurnalPemakaianBahanTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize
{
    public function array(): array
    {
        $sampleKodeProyek = KodeProyek::first()?->kode ?? '01';

        return [
            [
                'JPBIK-001', // bukti
                '2024-11-26', // tanggal
                '91', // kelompok_debit
                '9101', // rekening_debit
                '10', // nomor_bantu_debit
                '10', // kelompok_kredit
                '1501', // rekening_kredit
                '10', // nomor_bantu_kredit
                'd', // kode (d/k lowercase)
                '1500000', // jumlah
                'Pemakaian kaporit bulan November', // keterangan
                $sampleKodeProyek, // kode_proyek
                'Operasional', // beban_bagian
            ],
            [
                'JPBIK-002', // bukti
                '2024-11-26', // tanggal
                '92', // kelompok_debit
                '9201', // rekening_debit
                '20', // nomor_bantu_debit
                '10', // kelompok_kredit
                '1502', // rekening_kredit
                '20', // nomor_bantu_kredit
                'k', // kode (d/k lowercase)
                '800000', // jumlah
                'Pemakaian bahan pembantu pengolahan', // keterangan
                '', // kode_proyek (kosong)
                'Pengolahan Air', // beban_bagian
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'bukti',
            'tanggal',
            'kelompok_debit',
            'rekening_debit',
            'nomor_bantu_debit',
            'kelompok_kredit',
            'rekening_kredit',
            'nomor_bantu_kredit',
            'kode',
            'jumlah',
            'keterangan',
            'kode_proyek',
            'beban_bagian',
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
            'A' => 15, // bukti
            'B' => 12, // tanggal
            'C' => 15, // kelompok_debit
            'D' => 15, // rekening_debit
            'E' => 18, // nomor_bantu_debit
            'F' => 15, // kelompok_kredit
            'G' => 15, // rekening_kredit
            'H' => 18, // nomor_bantu_kredit
            'I' => 8,  // kode
            'J' => 15, // jumlah
            'K' => 35, // keterangan
            'L' => 15, // kode_proyek
            'M' => 20, // beban_bagian
        ];
    }
}
