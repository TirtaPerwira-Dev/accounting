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
        return [
            [
                'VCH-001', // bukti
                '2024-11-26', // tanggal
                '2024-11-26', // tanggal_check
                'Bank BPD', // nama_bank
                'CHK-12345', // no_cek
                '10', // kelompok (NO_KEL)
                '1102', // rekening (NO_REK)
                '20', // nomor_bantu (NO_BANTU)
                'D', // kode (D/K)
                '5000000', // jumlah (rp)
                'PT Supplier ABC', // dibayar_kepada
                'Operasional', // beban_bagian
                'Pembayaran pembelian bahan kimia', // keterangan
                '01', // kode_proyek (kode)
            ],
            [
                'VCH-002', // bukti
                '2024-11-27', // tanggal
                '2024-11-27', // tanggal_check
                'Bank BRI', // nama_bank
                'CHK-12346', // no_cek
                '10', // kelompok
                '1101', // rekening
                '10', // nomor_bantu
                'K', // kode
                '2500000', // jumlah
                'CV Teknik Jaya', // dibayar_kepada
                'Administrasi', // beban_bagian
                'Pembayaran jasa perawatan pompa', // keterangan
                '', // kode_proyek (kosong jika tidak ada)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'bukti',
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
            'beban_bagian',
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
            'A' => 15, // bukti
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
            'L' => 20, // beban_bagian
            'M' => 35, // keterangan
            'N' => 15, // kode_proyek
        ];
    }
}
