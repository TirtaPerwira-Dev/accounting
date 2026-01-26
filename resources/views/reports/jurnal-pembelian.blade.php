<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Jurnal Pembelian Barang - PDAM Tirta Perwira</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 30mm 20mm 25mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            line-height: 1.35;
            color: #000;
            padding: 0 5mm;
        }

        /* ====== HEADER ====== */
        .kop-surat {
            text-align: center;
            margin-bottom: 5px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 5px;
            margin-top: 40px;
        }

        .company-name {
            font-size: 9.5pt;
            font-weight: bold;
            line-height: 1.25;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .header-container {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .report-title {
            font-size: 10.5pt;
            font-weight: bold;
            margin: 5px 0 3px 0;
            text-transform: uppercase;
        }

        .report-period {
            font-size: 8.5pt;
            margin-bottom: 2px;
            color: #444;
        }

        /* ====== TABLE STYLES ====== */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 7pt;
        }

        .main-table thead tr {
            background-color: #e8e8e8;
        }

        .main-table th {
            border: 0.5px solid #555;
            padding: 4px 5px;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
            line-height: 1.2;
            font-size: 7pt;
        }

        .main-table td {
            border: 0.5px solid #777;
            padding: 2px 5px;
            vertical-align: top;
            line-height: 1.3;
            font-size: 7pt;
        }

        .main-table .text-center {
            text-align: center;
        }

        .main-table .text-right {
            text-align: right;
            padding-right: 6px;
        }

        .main-table .text-left {
            text-align: left;
        }

        /* ====== STATUS BADGE ====== */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            color: white;
            font-weight: bold;
            font-size: 8.5pt;
        }

        .status-confirmed {
            background-color: #28a745;
        }

        .status-pending {
            background-color: #ff9800;
        }

        /* ====== TABLES ====== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            page-break-inside: auto;
        }

        table thead tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th {
            background-color: #e9ecef;
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 9.5pt;
            vertical-align: middle;
        }

        td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 9.5pt;
        }

        .detail-table td {
            padding: 5px 6px;
        }

        /* ====== AMOUNTS ====== */
        .amount {
            font-family: 'Courier New', monospace;
            white-space: nowrap;
            font-size: 7pt;
        }

        /* ====== GROUP HEADER ====== */
        .group-row {
            background-color: #f8f8f8;
            font-weight: bold;
        }

        /* ====== SUMMARY ====== */
        .summary-row {
            background-color: #ececec;
            font-weight: bold;
        }

        .grand-total-row {
            background-color: #dadada;
            font-weight: bold;
            font-size: 7.5pt;
        }

        /* ====== FOOTER ====== */
        .footer-container {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-section {
            width: 100%;
            margin-top: 12px;
        }

        .signature-box {
            display: inline-block;
            width: 170px;
            text-align: center;
            font-size: 7.5pt;
            float: right;
            line-height: 1.35;
        }

        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            padding-top: 2px;
        }

        .footer-info {
            margin-top: 65px;
            padding-top: 6px;
            border-top: 0.5px solid #aaa;
            text-align: center;
            font-size: 6.5pt;
            color: #666;
            clear: both;
            line-height: 1.3;
        }

        /* ====== NO DATA ====== */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-data h3 {
            font-size: 12pt;
            margin-bottom: 6px;
            color: #666;
        }

        /* ====== UTILITIES ====== */
        .text-bold { font-weight: bold; }
        .nowrap { white-space: nowrap; }
    </style>
</head>

<body>
    <!-- ====== KOP SURAT ====== -->
    <div class="kop-surat">
        <div class="company-name">Pemerintah Kabupaten Purbalingga</div>
        <div class="company-name">Perusahaan Umum Daerah Air Minum Tirta Perwira</div>
        <div class="company-name">Kabupaten Purbalingga</div>
    </div>

    <!-- ====== JUDUL LAPORAN ====== -->
    <div class="header-container">
        <div class="report-title">Laporan Jurnal Pembelian Barang</div>
        <div class="report-period">Periode: {{ $period }}</div>
    </div>

    @if($data->count() > 0)
        @php
            $groupedData = $data->groupBy(function ($item) {
                return $item->group_transaksi ?? 'single_' . $item->id;
            });
            $grandTotalDebit = 0;
            $grandTotalCredit = 0;
        @endphp

        <!-- ====== MAIN TABLE ====== -->
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 2.5%;">No</th>
                    <th style="width: 7%;">No. Reff</th>
                    <th style="width: 6%;">Tanggal</th>
                    <th style="width: 7.5%;">Bukti</th>
                    <th style="width: 7.5%;">Kode<br>Akun</th>
                    <th style="width: 29%;">Nama Akun</th>
                    <th style="width: 23%;">Keterangan</th>
                    <th style="width: 4.5%;">Status</th>
                    <th style="width: 13%;">Debit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedData as $groupKey => $groupItems)
                    @php
                        $jurnal = $groupItems->first();
                        $totalGroupAmount = $groupItems->sum('rp');
                        $loopNum = $loop->iteration;
                    @endphp

                    <!-- Group Header Row (Akun Kredit) -->
                    <tr class="group-row">
                        <td class="text-center">{{ $loopNum }}</td>
                        <td class="text-center">{{ $jurnal->no_reff }}</td>
                        <td class="text-center">{{ $jurnal->tanggal->format('d/m/Y') }}</td>
                        <td colspan="3" style="padding-left: 6px;">
                            <strong>KREDIT:</strong> {{ $jurnal->kode_sakep_kredit }} - {{ $jurnal->nama_akun_kredit }}
                            @if($jurnal->kodeProyek)
                                <br><span style="font-size: 6pt; font-weight: normal;">Proyek : {{ $jurnal->kodeProyek->name }}</span>
                            @endif
                        </td>
                        <td style="font-size: 6.5pt; padding-left: 4px;">{{ $jurnal->keterangan ?: '-' }}</td>
                        <td class="text-center">
                            @if($jurnal->is_confirmed)
                                <span style="color: green; font-size: 8pt;">✓</span>
                            @else
                                <span style="color: orange; font-size: 8pt;">?</span>
                            @endif
                        </td>
                        <td class="text-right amount">{{ number_format($totalGroupAmount, 0, ',', '.') }}</td>
                    </tr>

                    <!-- Detail Rows (Akun Debit) -->
                    @foreach($groupItems as $item)
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td style="font-size: 6.5pt; padding-left: 4px;">{{ $item->bukti ?: '-' }}</td>
                            <td class="text-center">{{ $item->kode_sakep_debit }}</td>
                            <td style="padding-left: 10px;">{{ $item->nama_akun_debit }}</td>
                            <td style="font-size: 6.5pt; padding-left: 4px;">{{ $item->keterangan ?: '-' }}</td>
                            <td></td>
                            <td class="text-right amount">{{ number_format($item->rp, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    @php
                        $grandTotalDebit += $totalGroupAmount;
                        $grandTotalCredit += $totalGroupAmount;
                    @endphp
                @endforeach

                <!-- Grand Total -->
                <tr class="grand-total-row">
                    <td colspan="8" class="text-right" style="padding-right: 8px;">TOTAL KESELURUHAN:</td>
                    <td class="text-right amount" style="font-size: 7.5pt;">{{ number_format($grandTotalDebit, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Summary Info -->
        <table style="width: 40%; margin-top: 6px; border: none; font-size: 7pt;">
            <tr style="border: none;">
                <td style="border: none; padding: 1px 4px; font-weight: bold; width: 38%;">Total Transaksi:</td>
                <td style="border: none; padding: 1px 4px;">{{ $groupedData->count() }} Jurnal</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none; padding: 1px 4px; font-weight: bold;">Total Debit:</td>
                <td style="border: none; padding: 1px 4px;" class="amount">Rp {{ number_format($grandTotalDebit, 0, ',', '.') }}</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none; padding: 1px 4px; font-weight: bold;">Total Kredit:</td>
                <td style="border: none; padding: 1px 4px;" class="amount">Rp {{ number_format($grandTotalCredit, 0, ',', '.') }}</td>
            </tr>
        </table>

    @else
        <!-- No Data Message -->
        <div class="no-data">
            <h3>— TIDAK ADA DATA —</h3>
            <p style="font-size: 8pt;">Tidak ditemukan transaksi pembelian pada periode yang dipilih.</p>
        </div>
    @endif

    <!-- ====== FOOTER ====== -->
    <div class="footer-container">
        <div class="signature-section">
            <div class="signature-box">
                <div>Purbalingga, {{ \Carbon\Carbon::parse($generatedAt)->locale('id')->translatedFormat('d F Y') }}</div>
                <div style="margin-top: 3px;"><strong>Kepala Bagian Keuangan</strong></div>
                <div class="signature-line">
                    <strong>Yuni Setyowati, S.E</strong><br>
                    <span style="font-size: 6.5pt;">NIPPAM: ....................</span>
                </div>
            </div>
        </div>

        <div class="footer-info">
            Dicetak: {{ \Carbon\Carbon::parse($generatedAt)->locale('id')->translatedFormat('d F Y, H:i') }} WIB<br>
            <strong>Sistem Akuntansi Air Minum - Perumda Air Minum Tirta Perwira Kabupaten Purbalingga</strong>
        </div>
    </div>
</body>
</html>
