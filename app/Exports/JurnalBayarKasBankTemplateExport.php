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

class JurnalBayarKasBankTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize
{
    public function array(): array
    {
        $sampleKodeProyek = KodeProyek::first()?->kode ?? '01';

        return [
            [
                'VCH-001', // no_voucher
                '2024-11-26', // tanggal
                '2024-11-26', // tanggal_check
                'Bank BPD', // nama_bank
                'CHK-12345', // no_cek
                '10', // kelompok
                '1102', // rekening
                '20', // nomor_bantu
                'D', // kode (D/K)
                '5000000', // jumlah
                'PT Supplier ABC', // dibayar_kepada
                'Pembayaran pembelian bahan kimia', // keterangan
                $sampleKodeProyek, // kode_proyek
            ],
            [
                'VCH-002', // no_voucher
                '2024-11-27', // tanggal
                '2024-11-27', // tanggal_check
                'Bank BRI', // nama_bank
                'CHK-12346', // no_cek
                '10', // kelompok
                '1101', // rekening
                '10', // nomor_bantu
                'K', // kode (D/K)
                '2500000', // jumlah
                'CV Teknik Jaya', // dibayar_kepada
                'Pembayaran jasa perawatan pompa', // keterangan
                '', // kode_proyek (kosong)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'no_voucher',
            'tanggal',
            'tanggal_check',
            'nama_bank',
            'no_cek',
            'kelompok',
            'rekening',
            'nomor_bantu',
            'kode',
            'jumlah',
            'dibayar_kepada',
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
            'A' => 15, // no_voucher
            'B' => 12, // tanggal
            'C' => 12, // tanggal_check
            'D' => 20, // nama_bank
            'E' => 15, // no_cek
            'F' => 12, // kelompok
            'G' => 12, // rekening
            'H' => 15, // nomor_bantu
            'I' => 8,  // kode
            'J' => 15, // jumlah
            'K' => 25, // dibayar_kepada
            'L' => 35, // keterangan
            'M' => 15, // kode_proyek
        ];
    }
}
