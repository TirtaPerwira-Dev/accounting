<?php

namespace App\Exports;

use App\Models\NomorBantu;
use App\Models\KodeProyek;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class JurnalRekeningAirTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [
                'RK-001', // bukti
                '2024-11-26', // tanggal
                '01', // kode_proyek (nomor)
                '1301', // rekening (nomor)
                '10', // nomor_bantu (nomor)
                'D', // posisi
                '1000000', // jumlah
                'Tagihan air pelanggan zona A', // keterangan
            ],
            [
                'RK-001', // bukti - same transaction
                '2024-11-26', // tanggal
                '01', // kode_proyek (nomor)
                '8101', // rekening (nomor) - Pendapatan Harga Air
                '10', // nomor_bantu (nomor)
                'K', // posisi
                '900000', // jumlah
                'Pendapatan air bersih', // keterangan
            ],
            [
                'RK-001', // bukti - same transaction
                '2024-11-26', // tanggal
                '', // kode_proyek (kosong)
                '5006', // rekening (nomor) - Utang Pajak PPN
                '10', // nomor_bantu (nomor)
                'K', // posisi
                '100000', // jumlah
                'PPN atas penjualan air', // keterangan
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'bukti',
            'tanggal', 
            'kode_proyek',
            'rekening',
            'nomor_bantu',
            'posisi',
            'jumlah',
            'keterangan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2196F3'],
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
            'C' => 12, // kode_proyek
            'D' => 12, // rekening
            'E' => 12, // nomor_bantu
            'F' => 8,  // posisi
            'G' => 15, // jumlah
            'H' => 30, // keterangan
        ];
    }
}