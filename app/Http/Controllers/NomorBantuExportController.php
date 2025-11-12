<?php

namespace App\Http\Controllers;

use App\Models\NomorBantu;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NomorBantuExportController extends Controller
{
    public function exportPdf(Request $request)
    {
        // Increase memory limit untuk PDF generation
        ini_set('memory_limit', '1024M');

        // Ambil parameter filter
        $kelompokId = $request->get('kelompok_id');
        $rekeningId = $request->get('rekening_id');
        $kode = $request->get('kode');
        $kel = $request->get('kel');
        $ids = $request->get('ids');

        // Query builder dengan filter
        $query = NomorBantu::with(['rekening.kelompok']);

        if ($ids) {
            // Export selected records
            $idsArray = explode(',', $ids);
            $query->whereIn('id', $idsArray);
            $title = 'Daftar Nomor Bantu Terpilih';
        } else {
            $title = 'Daftar Nomor Bantu';

            // Apply filters
            if ($kelompokId) {
                $query->whereHas('rekening.kelompok', function ($q) use ($kelompokId) {
                    $q->where('id', $kelompokId);
                });

                $kelompokName = \App\Models\Kelompok::find($kelompokId)->nama_kel ?? '';
                $title .= ' - Kelompok: ' . $kelompokName;
            }

            if ($rekeningId) {
                $query->where('rekening_id', $rekeningId);

                $rekeningName = \App\Models\Rekening::find($rekeningId)->nama_rek ?? '';
                $title .= ' - Rekening: ' . $rekeningName;
            }

            if ($kode) {
                $query->where('kode', $kode);
                $title .= ' - Saldo: ' . ($kode === 'D' ? 'Debet' : 'Kredit');
            }

            if ($kel) {
                $query->where('kel', $kel);
                $kategori = match ($kel) {
                    '1' => 'Aktiva',
                    '2' => 'Kewajiban',
                    '3' => 'Pendapatan',
                    '4' => 'Biaya Operasional',
                    '5' => 'Biaya Administrasi',
                    '6' => 'Biaya Luar Usaha',
                    default => $kel,
                };
                $title .= ' - Kategori: ' . $kategori;
            }
        }

        // Get records with pagination to prevent memory issues
        $records = $query->orderBy('created_at')->limit(500)->get();

        // Ambil data perusahaan untuk header
        $company = Company::first();

        // Data untuk PDF
        $data = [
            'title' => $title,
            'company' => $company,
            'records' => $records,
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'generated_by' => Auth::check() ? Auth::user()->name : 'System',
            'filters' => [
                'kelompok_id' => $kelompokId,
                'kelompok_name' => $kelompokId ? \App\Models\Kelompok::find($kelompokId)->nama_kel ?? '' : '',
                'rekening_id' => $rekeningId,
                'rekening_name' => $rekeningId ? \App\Models\Rekening::find($rekeningId)->nama_rek ?? '' : '',
                'kode' => $kode,
                'kel' => $kel,
            ]
        ];

        try {
            // Generate PDF dengan optimasi
            $pdf = Pdf::loadView('pdf.nomor-bantu', $data);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'dpi' => 96,
            ]);

            // Download file dengan nama yang sesuai
            $filename = 'nomor-bantu-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat generate PDF: ' . $e->getMessage());
        }
    }
}
