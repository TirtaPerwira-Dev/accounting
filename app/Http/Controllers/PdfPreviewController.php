<?php

namespace App\Http\Controllers;

use App\Models\JurnalPenerimaanKas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfPreviewController extends Controller
{
    public function jurnalPenerimaanKasBulk(Request $request)
    {
        $query = JurnalPenerimaanKas::with([
            'kasBank.rekening.kelompok',
            'details.rekening.kelompok',
            'details.nomorBantu',
            'details.kodeProyek'
        ])
            ->whereDate('tanggal', '>=', $request->from)
            ->whereDate('tanggal', '<=', $request->to);

        if (!empty($request->kas_bank)) {
            $query->where('kas_bank_id', $request->kas_bank);
        }

        $records = $query->orderBy('tanggal', 'asc')->get();
        
        $title = 'Laporan JPK ' . \Carbon\Carbon::parse($request->from)->format('d/m/Y') .
            ' - ' . \Carbon\Carbon::parse($request->to)->format('d/m/Y');

        $pdf = Pdf::loadView('pdf.jurnal-penerimaan-kas-bulk', [
            'records' => $records,
            'title' => $title
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('JPK-' . $request->from . '_' . $request->to . '.pdf');
    }
}
