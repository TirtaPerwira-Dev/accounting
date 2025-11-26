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

class JurnalPembelianTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize
{
    public function array(): array
    {
        // Get sample data using IDs not codes
        $sampleRekeningKredit = \App\Models\Rekening::whereHas('kelompok', function($q) {
            $q->where('no_kel', '10'); // Kas/Bank
        })->first();
        
        $sampleNomorBantuKredit = $sampleRekeningKredit ? 
            \App\Models\NomorBantu::where('rekening_id', $sampleRekeningKredit->id)->first() : null;
            
        $sampleNomorBantuDebit1 = \App\Models\NomorBantu::whereHas('rekening', function($q) {
            $q->where('no_rek', '1501'); // Persediaan
        })->first();
        
        $sampleNomorBantuDebit2 = \App\Models\NomorBantu::whereHas('rekening', function($q) {
            $q->where('no_rek', '1502'); // Persediaan lain
        })->first();
        
        $sampleKodeProyek = KodeProyek::first();

        return [
            [
                '2024-11-26', // tanggal
                $sampleRekeningKredit?->id ?? '1', // rekening_kredit (ID)
                $sampleNomorBantuKredit?->id ?? '1', // nomor_bantu_kredit (ID)
                $sampleNomorBantuKredit?->nm_bantu ?? 'Bank BPD Cabang Utama', // nama_nomor_bantu_kredit
                'BK-001', // bukti
                'Kaporit untuk pengolahan air', // keterangan
                $sampleKodeProyek?->id ?? '1', // kode_proyek (ID)
                $sampleNomorBantuDebit1?->id ?? '1', // nomor_bantu_debit (ID)
                '1000000', // jumlah
            ],
            [
                '2024-11-26', // tanggal - same transaction
                $sampleRekeningKredit?->id ?? '1', // rekening_kredit (ID) - same
                $sampleNomorBantuKredit?->id ?? '1', // nomor_bantu_kredit (ID) - same
                $sampleNomorBantuKredit?->nm_bantu ?? 'Bank BPD Cabang Utama', // nama_nomor_bantu_kredit
                'BK-001', // bukti - same
                'Chlorine untuk desinfeksi', // keterangan
                $sampleKodeProyek?->id ?? '1', // kode_proyek (ID)
                $sampleNomorBantuDebit2?->id ?? '2', // nomor_bantu_debit (ID) - different
                '500000', // jumlah
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'tanggal',
            'rekening_kredit',
            'nomor_bantu_kredit',
            'nama_nomor_bantu_kredit',
            'bukti',
            'keterangan',
            'kode_proyek',
            'nomor_bantu_debit',
            'jumlah',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4CAF50'],
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
            'A' => 12, // tanggal
            'B' => 15, // rekening_kredit
            'C' => 18, // nomor_bantu_kredit
            'D' => 25, // nama_nomor_bantu_kredit
            'E' => 15, // bukti
            'F' => 30, // keterangan
            'G' => 15, // kode_proyek
            'H' => 18, // nomor_bantu_debit
            'I' => 15, // jumlah
        ];
    }
}