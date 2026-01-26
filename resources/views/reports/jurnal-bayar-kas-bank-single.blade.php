<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Bayar Kas/Bank - {{ $jurnal->no_reff }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 30px;
            color: #212529;
            line-height: 1.5;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; }
        .kop-text { font-size: 14px; font-weight: 500; margin: 0; line-height: 1.3; }
        .kop-perumda { font-size: 16px; font-weight: 700; color: #007bff; margin: 2px 0; }
        .kop-divider { border: none; height: 3px; background-color: #333; margin-top: 5px; margin-bottom: 10px; }
        .document-title { font-size: 18px; font-weight: 700; text-align: center; margin: 20px 0 10px 0; text-transform: uppercase; }
        .jurnal-info-wrapper { padding: 15px; margin-bottom: 25px; border: 1px solid #dee2e6; background-color: #f8f9fa; border-radius: 4px; }
        .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .info-table td { padding: 0; vertical-align: top; border: none; }
        .info-table td:first-child { border-right: 1px solid #dee2e6; padding-right: 20px; }
        .info-table td:last-child { padding-left: 20px; }
        .info-label { font-weight: 600; color: #495057; font-size: 10px; width: 140px; }
        .info-value { font-weight: 500; font-size: 11px; }
        .total-value { font-size: 15px; font-weight: 700; color: #28a745; }
        .status-badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 700; display: inline-block; }
        .status-confirmed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .keterangan-box { margin-top: 15px; padding: 10px; border: 1px dashed #ced4da; border-radius: 4px; background-color: white; font-style: italic; color: #495057; font-size: 11px; }
        .section-title { font-size: 13px; font-weight: 700; margin: 25px 0 10px 0; padding: 5px 10px; background-color: #e9ecef; color: #28a745; border-left: 4px solid #28a745; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px 10px; text-align: left; vertical-align: top; font-size: 11px; }
        th { background-color: #28a745; color: white; font-weight: 600; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .amount { font-family: 'Consolas', 'Courier New', monospace; font-weight: 700; color: #28a745; font-size: 12px; }
        .kode-sakep { font-family: 'Consolas', 'Courier New', monospace; font-weight: 700; color: #007bff; background-color: #e6f0ff; padding: 1px 4px; border-radius: 3px; font-size: 10px; display: inline-block; }
        .total-row { background-color: #28a745 !important; color: white; font-weight: 700; font-size: 12px; }
        .total-row td { border-color: white; }
        .total-row .amount { color: white; }
        .footer-signature { margin-top: 50px; text-align: right; }
        .signature-box { width: 250px; margin-left: auto; text-align: center; }
        .signature-name { font-weight: 700; font-size: 12px; margin-top: 70px; text-decoration: underline; }
        .page-footer { position: fixed; bottom: 10px; left: 30px; right: 30px; border-top: 1px solid #ddd; padding-top: 5px; font-size: 9px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p class="kop-text">Pemerintah Kabupaten Purbalingga</p>
            <p class="kop-perumda">Perusahaan Umum Daerah Air Minum Tirta Perwira</p>
            <hr class="kop-divider">
        </div>

        <div class="document-title">JURNAL BAYAR KAS/BANK</div>

        <div class="jurnal-info-wrapper">
            <table class="info-table">
                <tr>
                    <td width="50%">
                        <div><span class="info-label">NO. REFERENSI :</span><span class="info-value"><strong>{{ $jurnal->no_reff }}</strong></span></div>
                        <div><span class="info-label">TANGGAL :</span><span class="info-value">{{ $jurnal->tanggal->format('d M Y') }}</span></div>
                        <div><span class="info-label">AKUN KAS/BANK :</span><span class="info-value">{{ $jurnal->rekening->nama_rek ?? '-' }}</span></div>
                        <div><span class="info-label">KODE AKUN :</span><span class="info-value"><span class="kode-sakep">{{ $jurnal->rekening->kelompok->no_kel ?? '' }}{{ $jurnal->rekening->no_rek ?? '' }}{{ $jurnal->nomorBantu->no_bantu ?? '' }}</span></span></div>
                    </td>
                    <td width="50%">
                        <div><span class="info-label">TOTAL PEMBAYARAN:</span><span class="info-value total-value">Rp {{ number_format($jurnal->rp, 0, ',', '.') }}</span></div>
                        <div><span class="info-label">DIBAYAR KEPADA:</span><span class="info-value">{{ $jurnal->dibayar_kepada ?? '-' }}</span></div>
                        <div><span class="info-label">STATUS :</span><span class="info-value">
                            @if($jurnal->is_confirmed)
                                <span class="status-badge status-confirmed">DIKONFIRMASI</span>
                            @else
                                <span class="status-badge status-pending">PENDING</span>
                            @endif
                        </span></div>
                    </td>
                </tr>
            </table>
        </div>

        @if($jurnal->keterangan)
        <div class="keterangan-box">
            <strong>Keterangan:</strong> {{ $jurnal->keterangan }}
        </div>
        @endif

        <div class="section-title">DETAIL TRANSAKSI</div>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="45%">Rekening & Keterangan</th>
                    <th width="20%">Kode Akun</th>
                    <th width="15%">Debit (Rp)</th>
                    <th width="15%">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @php $totalDebit = 0; $totalKredit = 0; @endphp
                {{-- Detail entries could come from a relationship if it was a header-detail, but here it's simple --}}
                {{-- If this model is simple, we show the main record. If it has details relationship, we show them. --}}
                @if($jurnal->details && $jurnal->details->count() > 0)
                    @foreach($jurnal->details as $index => $detail)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $detail->rekening->nama_rek ?? '-' }}</strong><br>
                                <small>{{ $detail->keterangan }}</small>
                            </td>
                            <td class="text-center"><span class="kode-sakep">{{ $detail->rekening->kelompok->no_kel ?? '' }}{{ $detail->rekening->no_rek ?? '' }}{{ $detail->nomorBantu->no_bantu ?? '' }}</span></td>
                            <td class="text-right amount">{{ number_format($detail->debit, 0, ',', '.') }}</td>
                            <td class="text-right amount">{{ number_format($detail->credit, 0, ',', '.') }}</td>
                        </tr>
                        @php $totalDebit += $detail->debit; $totalKredit += $detail->credit; @endphp
                    @endforeach
                @else
                    {{-- Fallback if no details relationship yet --}}
                    <tr>
                        <td class="text-center">1</td>
                        <td><strong>{{ $jurnal->rekening->nama_rek ?? '-' }}</strong></td>
                        <td class="text-center"><span class="kode-sakep">{{ $jurnal->rekening->kelompok->no_kel ?? '' }}{{ $jurnal->rekening->no_rek ?? '' }}{{ $jurnal->nomorBantu->no_bantu ?? '' }}</span></td>
                        <td class="text-right amount">{{ $jurnal->kode == 'D' ? number_format($jurnal->rp, 0, ',', '.') : '-' }}</td>
                        <td class="text-right amount">{{ $jurnal->kode == 'K' ? number_format($jurnal->rp, 0, ',', '.') : '-' }}</td>
                    </tr>
                    @php 
                        $totalDebit = $jurnal->kode == 'D' ? $jurnal->rp : 0; 
                        $totalKredit = $jurnal->kode == 'K' ? $jurnal->rp : 0; 
                    @endphp
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right">TOTAL</td>
                    <td class="text-right amount">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                    <td class="text-right amount">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer-signature">
            <div class="signature-box">
                <div>Purbalingga, {{ date('d M Y') }}</div>
                <div style="font-weight: bold; margin-top: 5px;">Kepala Bagian Keuangan</div>
                <div class="signature-name">Yuni Setyowati, S.E</div>
            </div>
        </div>
    </div>
    <div class="page-footer">
        <div>Sistem Akuntansi Tirta Perwira | Dicetak: {{ date('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
