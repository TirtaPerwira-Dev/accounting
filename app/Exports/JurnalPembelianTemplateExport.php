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
        // Get sample data using codes not IDs
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
                $sampleRekeningKredit?->no_rek ?? '1101', // rekening_kredit (CODE)
                $sampleNomorBantuKredit?->no_bantu ?? '10', // nomor_bantu_kredit (CODE)
                $sampleNomorBantuKredit?->nm_bantu ?? 'Bank BPD Cabang Utama', // nama_nomor_bantu_kredit
                'BK-001', // bukti
                'Kaporit untuk pengolahan air', // keterangan
                $sampleKodeProyek?->kode ?? '01', // kode_proyek (CODE)
                $sampleNomorBantuDebit1?->no_bantu ?? '10', // nomor_bantu_debit (CODE)
                '1000000', // jumlah
            ],
            [
                '2024-11-26', // tanggal - same transaction
                $sampleRekeningKredit?->no_rek ?? '1101', // rekening_kredit (CODE) - same
                $sampleNomorBantuKredit?->no_bantu ?? '10', // nomor_bantu_kredit (CODE) - same
                $sampleNomorBantuKredit?->nm_bantu ?? 'Bank BPD Cabang Utama', // nama_nomor_bantu_kredit
                'BK-001', // bukti - same
                'Chlorine untuk desinfeksi', // keterangan
                $sampleKodeProyek?->kode ?? '01', // kode_proyek (CODE)
                $sampleNomorBantuDebit2?->no_bantu ?? '20', // nomor_bantu_debit (CODE) - different
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