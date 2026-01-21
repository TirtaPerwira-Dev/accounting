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

class JurnalMemorialTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize
{
    public function array(): array
    {
        $sampleKodeProyek = KodeProyek::first()?->kode ?? '01';

        return [
            [
                'MEM-001', // bukti
                '2024-11-26', // tanggal
                '30', // kelompok
                '3101', // rekening
                '10', // nomor_bantu
                'D', // kode (D/K uppercase)
                '15000000', // jumlah
                'Penyesuaian nilai aset tanah', // keterangan
                $sampleKodeProyek, // kode_proyek
            ],
            [
                'MEM-001', // bukti (same transaction)
                '2024-11-26', // tanggal
                '70', // kelompok
                '7005', // rekening - Selisih Penilaian Kembali
                '10', // nomor_bantu
                'K', // kode (D/K uppercase)
                '15000000', // jumlah
                'Selisih penilaian kembali aset', // keterangan
                $sampleKodeProyek, // kode_proyek
            ],
            [
                'MEM-002', // bukti (new transaction)
                '2024-11-27', // tanggal
                '96', // kelompok
                '9691', // rekening - Biaya Penyusutan
                '10', // nomor_bantu
                'D', // kode
                '2500000', // jumlah
                'Penyusutan aset bulan November', // keterangan
                '', // kode_proyek (kosong)
            ],
            [
                'MEM-002', // bukti (same transaction)
                '2024-11-27', // tanggal
                '30', // kelompok
                '3190', // rekening - Akumulasi Penyusutan
                '90', // nomor_bantu
                'K', // kode
                '2500000', // jumlah
                'Akumulasi penyusutan keseluruhan', // keterangan
                '', // kode_proyek
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'bukti',
            'tanggal',
            'kelompok',
            'rekening',
            'nomor_bantu',
            'kode',
            'jumlah',
            'keterangan',
            'kode_proyek',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF9C27B0'],
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
            'C' => 12, // kelompok
            'D' => 12, // rekening
            'E' => 15, // nomor_bantu
            'F' => 8,  // kode
            'G' => 15, // jumlah
            'H' => 40, // keterangan
            'I' => 15, // kode_proyek
        ];
    }
}
