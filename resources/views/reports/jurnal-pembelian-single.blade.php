<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Pembelian Barang - {{ $jurnal->no_reff }}</title>
    <style>
        /* BASE STYLES */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 30px;
            color: #212529;
            line-height: 1.5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* HEADER (KOP SURAT) */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }

        .kop-text {
            font-size: 14px;
            font-weight: 500;
            margin: 0;
            line-height: 1.3;
        }

        .kop-perumda {
            font-size: 16px;
            font-weight: 700;
            color: #007bff;
            margin: 2px 0;
        }

        .kop-divider {
            border: none;
            height: 3px;
            background-color: #333;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .document-title {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            margin: 20px 0 10px 0;
            text-transform: uppercase;
        }

        /* INFO BOX (Disederhanakan menggunakan TABLE) */
        .jurnal-info-wrapper {
            padding: 15px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table td {
            padding: 0;
            vertical-align: top;
            border: none;
        }

        /* KUNCI: Border pemisah kolom dan padding untuk kerapian */
        .info-table td:first-child {
            border-right: 1px solid #dee2e6;
            padding-right: 20px;
        }

        .info-table td:last-child {
            padding-left: 20px;
        }

        /* Set lebar kolom label */
        .info-label {
            font-weight: 600;
            color: #495057;
            font-size: 10px;
            width: 140px;
            padding-right: 10px;
        }

        .info-value {
            font-weight: 500;
            font-size: 11px;
        }

        .total-value {
            font-size: 15px;
            font-weight: 700;
            color: #dc3545;
        }

        /* STATUS & KETERANGAN */
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
        }

        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .keterangan-box {
            margin-top: 15px;
            padding: 10px;
            border: 1px dashed #ced4da;
            border-radius: 4px;
            background-color: white;
            font-style: italic;
            color: #495057;
            font-size: 11px;
        }

        /* SECTION TITLE & TABLES */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 25px 0 10px 0;
            padding: 5px 10px;
            background-color: #e9ecef;
            color: #007bff;
            border-left: 4px solid #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
            font-size: 11px;
        }

        th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .amount {
            font-family: 'Consolas', 'Courier New', monospace;
            font-weight: 700;
            color: #dc3545;
            font-size: 12px;
        }

        .kode-sakep {
            font-family: 'Consolas', 'Courier New', monospace;
            font-weight: 700;
            color: #007bff;
            background-color: #e6f0ff;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 10px;
            display: inline-block;
        }

        /* JURNAL ENTRY TABLE SPECIFIC */
        .debit-row {
            background-color: #ffffff;
        }

        .kredit-row td:nth-child(2) {
            padding-left: 30px;
        }

        .total-row {
            background-color: #007bff !important;
            color: white;
            font-weight: 700;
            font-size: 12px;
        }

        .total-row td {
            border-color: white;
        }

        .total-row .amount {
            color: white;
        }

        /* FOOTER & SIGNATURE */
        .footer-signature {
            margin-top: 50px;
            text-align: right;
            padding-right: 20px;
        }

        .signature-box {
            width: 300px;
            margin-left: auto;
            text-align: center;
            /* KUNCI: Rata tengah untuk semua elemen tanda tangan */
        }

        .signature-label {
            font-size: 11px;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .signature-name {
            font-weight: 700;
            font-size: 12px;
            margin-top: 70px;
            text-decoration: underline;
        }

        .signature-nippam {
            font-size: 10px;
            margin-top: 3px;
        }

        /* FOOTER HALAMAN */
        .page-footer {
            position: fixed;
            bottom: 10px;
            left: 30px;
            right: 30px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            font-size: 9px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
        }

        /* Penambahan untuk Nomor Halaman */
        .page-number::before {
            content: counter(page);
        }

        /* WATERMARK */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(255, 0, 0, 0.08);
            font-weight: 700;
            z-index: -1;
            pointer-events: none;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="container">
        @if(!$jurnal->is_confirmed)
        <div class="watermark">BELUM VALIDASI</div>
        @endif

        <div class="header">
            <p class="kop-text">Pemerintah Kabupaten Purbalingga</p>
            <p class="kop-perumda">Perusahaan Umum Daerah Air Minum Tirta Perwira</p>
            <p class="kop-text">Kabupaten Purbalingga</p>
            <hr class="kop-divider">
        </div>

        <div class="document-title">JURNAL PEMBELIAN BARANG</div>

        <div class="jurnal-info-wrapper">
            <table class="info-table">
                <tr>
                    <td width="50%">
                        <div style="margin-bottom: 5px; display: flex;">
                            <span class="info-label">NO. REFERENSI :</span>
                            <span class="info-value"><strong>{{ $jurnal->no_reff }}</strong></span>
                        </div>
                        <div style="margin-bottom: 5px; display: flex;">
                            <span class="info-label">TANGGAL :</span>
                            <span class="info-value">{{ $jurnal->tanggal->format('d M Y') }}</span>
                        </div>
                        <div style="margin-bottom: 5px; display: flex;">
                            <span class="info-label">AKUN :</span>
                            <span class="info-value">
                                <span class="info-value">{{ $jurnal->kode_sakep_kredit }}</span> - {{
                                $jurnal->nama_akun_kredit }}
                            </span>
                        </div>
                        @if($jurnal->kodeProyek)
                        <div style="margin-bottom: 5px; display: flex;">
                            <span class="info-label">KODE PROYEK:</span>
                            <span class="info-value">{{ $jurnal->kodeProyek->name }}</span>
                        </div>
                        @endif
                    </td>
                    <td width="50%">
                        <div style="margin-bottom: 5px; display: flex;">
                            <span class="info-label">TOTAL NILAI TRANSAKSI:</span>
                            <span class="info-value total-value">Rp {{ number_format($jurnal->rp, 0, ',', '.') }}</span>
                        </div>
                        <div style="margin-bottom: 5px; display: flex;">
                            <span class="info-label">STATUS JURNAL:</span>
                            <span class="info-value">
                                @if($jurnal->is_confirmed)
                                <span class="status-badge status-confirmed">DIKONFIRMASI</span>
                                @else
                                <span class="status-badge status-pending">PENDING</span>
                                @endif
                            </span>
                        </div>
                        @if($jurnal->is_confirmed)
                        <div style="margin-bottom: 5px; display: flex;">
                            <span class="info-label">WAKTU KONFIRMASI:</span>
                            <span class="info-value">{{ $jurnal->confirmed_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @if($jurnal->keterangan)
        <div class="keterangan-box">
            <span style="font-weight: 600; font-style: normal;">Keterangan Transaksi:</span> {{ $jurnal->keterangan }}
        </div>
        @endif

        <div class="section-title">PERINCIAN TRANSAKSI DEBIT</div>
        @php
            $groupItems = $jurnal->group_transaksi ?
                \App\Models\JurnalPembelian::where('group_transaksi', $jurnal->group_transaksi)
                    ->orderBy('item_sequence')
                    ->get() :
                collect([$jurnal]);
        @endphp

        @if($groupItems->count() > 0)
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">No. Bukti</th>
                    <th width="25%">Keterangan</th>
                    <th width="20%">Akun Debit</th>
                    <th width="20%">Kode Akun</th>
                    <th width="15%">Jumlah (Debit)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">
                        <span style="font-family: 'Courier New', monospace; font-weight: bold;">{{ $item->bukti ?? '-' }}</span>
                    </td>
                    <td>
                        {{ $item->keterangan ?? 'Item pembelian' }}
                        @if($item->kodeProyek)
                        <br><small style="color: #6c757d;">Proyek: {{ $item->kodeProyek->name }}</small>
                        @endif
                    </td>
                    <td>{{ $item->nama_akun_debit }}</td>
                    <td class="text-center">
                        <span class="kode-sakep">{{ $item->kode_sakep_debit }}</span>
                    </td>
                    <td class="text-right amount">Rp {{ number_format($item->rp ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right"><strong>TOTAL DEBIT (HARUS SAMA DENGAN KREDIT):</strong></td>
                    <td class="text-right amount">Rp {{ number_format($jurnal->total_pembelian, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        @else
        <div
            style="text-align: center; padding: 10px; color: #6c757d; font-style: italic; border: 1px dashed #ced4da; border-radius: 4px;">
            Tidak ada perincian transaksi Debit (Item Pembelian) yang tercatat.
        </div>
        @endif

        <div class="section-title">RINGKASAN JURNAL AKUNTANSI (DEBIT/KREDIT)</div>
        <table class="debit-kredit-table">
            <thead>
                <tr>
                    <th width="15%">Tanggal</th>
                    <th width="35%">Akun & Keterangan</th>
                    <th width="25%">Debit (Rp)</th>
                    <th width="25%">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupItems as $item)
                <tr class="debit-row">
                    <td class="text-center">{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td>
                        <span class="kode-sakep">{{ $item->kode_sakep_debit }}</span> - <strong>{{ $item->nama_akun_debit }}</strong><br>
                        <small style="color: #6c757d;">{{ $item->keterangan ?? 'Item pembelian' }}</small>
                    </td>
                    <td class="text-right amount">{{ number_format($item->rp ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endforeach

                <tr class="kredit-row">
                    <td class="text-center">{{ $jurnal->tanggal->format('d/m/Y') }}</td>
                    <td>
                        <span class="kode-sakep">{{ $jurnal->kode_sakep_kredit }}</span> - <strong>{{
                            $jurnal->nama_akun_kredit }}</strong><br>
                        <small style="color: #6c757d;">{{ $jurnal->bukti ? 'No. Bukti: ' . $jurnal->bukti : 'Hutang
                            pembelian' }}</small>
                    </td>
                    <td class="text-right">-</td>
                    <td class="text-right amount">{{ number_format($jurnal->total_pembelian, 0, ',', '.') }}</td>
                </tr>

                <tr class="total-row">
                    <td colspan="2" class="text-right"><strong>TOTAL AKUN BERIMBANG:</strong></td>
                    <td class="text-right amount">Rp {{ number_format($jurnal->total_pembelian, 0, ',', '.') }}</td>
                    <td class="text-right amount">Rp {{ number_format($jurnal->total_pembelian, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer-signature">
            <div class="signature-box">
                <div style="text-align: center; margin-bottom: 5px;">Purbalingga, {{ date('d M Y') }}</div>
                <div class="signature-label" style="font-weight: bold;">
                    Perusahaan Umum Daerah Air Minum<br>
                    Tirta Perwira
                </div>
                <div class="signature-label">Kepala Bagian Keuangan</div>

                <div class="signature-name">Yuni Setyowati, S.E</div>
                <div class="signature-nippam">Nippam : .....................</div>
            </div>
        </div>

    </div>

    <div class="page-footer">
        <div>Sistem Akuntansi Tirta Perwira</div>
        <div>Tanggal Cetak : {{ date('d/m/Y H:i:s') }}</div>

        <div style="text-align: right;">
            <div class="page-number" style="font-weight: 700;">
            {{-- <div style="visibility: hidden; height: 1.5em;">Kosong</div> --}}
        </div>
    </div>
</body>

</html>
