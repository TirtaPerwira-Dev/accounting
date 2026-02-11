<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $report['title'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 15mm 15mm 15mm;
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
        }

        /* ====== HEADER ====== */
        .kop-surat {
            text-align: center;
            margin-bottom: 5px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 5px;
        }

        .company-name {
            font-size: 10pt;
            font-weight: bold;
            line-height: 1.25;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .header-container {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .report-title {
            font-size: 11pt;
            font-weight: bold;
            margin: 5px 0 3px 0;
            text-transform: uppercase;
        }

        .report-period {
            font-size: 9pt;
            margin-bottom: 2px;
            color: #444;
        }

        /* ====== TABLE STYLES ====== */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 7.5pt;
        }

        .main-table thead tr {
            background-color: #f2f2f2;
        }

        .main-table th {
            border: 0.5px solid #000;
            padding: 5px 4px;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
            text-transform: uppercase;
        }

        .main-table td {
            border: 0.5px solid #333;
            padding: 3px 5px;
            vertical-align: top;
            line-height: 1.3;
        }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; padding-right: 6px !important; }
        .text-left { text-align: left !important; }
        .text-bold { font-weight: bold; }

        /* ====== GROUP HEADER ====== */
        .group-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .grand-total-row {
            background-color: #e6e6e6;
            font-weight: bold;
            font-size: 8pt;
        }

        /* ====== AMOUNTS ====== */
        .amount {
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        /* ====== FOOTER ====== */
        .footer-container {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .footer-table {
            width: 100%;
            border: none;
        }

        .footer-table td {
            border: none;
            width: 33%;
            vertical-align: top;
            text-align: center;
        }

        .signature-line {
            margin-top: 50px;
            display: inline-block;
            width: 80%;
            border-top: 1px solid #000;
            padding-top: 2px;
        }

        .print-info {
            margin-top: 60px;
            text-align: center;
            font-size: 7pt;
            color: #777;
            font-style: italic;
            border-top: 0.5px solid #eee;
            padding-top: 10px;
        }

        /* ====== STATUS BADGE ====== */
        .status-ok { color: green; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }

        /* ====== NO DATA ====== */
        .no-data {
            text-align: center;
            padding: 50px 0;
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>
    <!-- ====== KOP SURAT ====== -->
    <div class="kop-surat">
        @if($company)
            <div class="company-name">{{ $company->name }}</div>
            <div style="font-size: 8.5pt; font-weight: normal; margin-top: 2px;">{{ $company->address }}</div>
            <div style="font-size: 8pt; font-weight: normal;">Telp: {{ $company->phone }} | NPWP: {{ $company->npwp }}</div>
        @else
            <div class="company-name">PERUMDA AIR MINUM TIRTA PERWIRA</div>
            <div style="font-size: 8.5pt; font-weight: normal; margin-top: 2px;">Jalan Letnan Habib Gani No. 12 Purbalingga</div>
        @endif
    </div>

    <!-- ====== JUDUL LAPORAN ====== -->
    <div class="header-container">
        <div class="report-title">{{ $report['title'] }}</div>
        <div class="report-period">Periode: {{ $report['period'] }}</div>
    </div>

    @if(count($report['items']) > 0)
        <!-- ====== MAIN TABLE ====== -->
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 3%;">No</th>
                    <th style="width: 8%;">Tanggal</th>
                    <th style="width: 9%;">No. Reff</th>
                    <th style="width: 9%;">Bukti</th>
                    <th style="width: 10%;">Kode Akun</th>
                    <th style="width: 25%;">Nama Akun / Keterangan</th>
                    <th style="width: 12%;">Debit (Rp)</th>
                    <th style="width: 12%;">Kredit (Rp)</th>
                    <th style="width: 6%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotalDebit = 0;
                    $grandTotalCredit = 0;
                    $i = 1;
                @endphp

                @foreach($report['items'] as $item)
                    <!-- Group Header Row (Main/Balanced Row) -->
                    <tr class="group-row">
                        <td class="text-center">{{ $i++ }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($item['tanggal'])->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $item['no_reff'] }}</td>
                        <td class="text-center">{{ $item['bukti'] ?? '-' }}</td>
                        <td class="text-center">{{ $item['main_account_code'] }}</td>
                        <td>
                            <strong>{{ $item['main_account_name'] }}</strong>
                            <br><span style="font-size: 6.5pt; font-weight: normal; color: #555;">{{ $item['description'] }}</span>
                        </td>
                        <td class="text-right amount">
                            @if($item['main_posisi'] == 'D')
                                {{ number_format($item['total_amount'], 0, ',', '.') }}
                                @php $grandTotalDebit += $item['total_amount']; @endphp
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right amount">
                            @if($item['main_posisi'] == 'K')
                                {{ number_format($item['total_amount'], 0, ',', '.') }}
                                @php $grandTotalCredit += $item['total_amount']; @endphp
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item['status'] == 'OK')
                                <span class="status-ok">OK</span>
                            @else
                                <span class="status-pending">P</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Detail Rows -->
                    @foreach($item['details'] as $detail)
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center" style="font-size: 6.5pt;">{{ $detail['bukti'] ?? '-' }}</td>
                            <td class="text-center">{{ $detail['code'] }}</td>
                            <td style="padding-left: 15px;">
                                {{ $detail['name'] }}
                                @if(!empty($detail['description']) && $detail['description'] !== $item['description'])
                                    <br><span style="font-size: 6.5pt; color: #666;">{{ $detail['description'] }}</span>
                                @endif
                            </td>
                            <td class="text-right amount">
                                @if(($detail['debit'] ?? 0) > 0)
                                    {{ number_format($detail['debit'], 0, ',', '.') }}
                                    @php $grandTotalDebit += $detail['debit']; @endphp
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right amount">
                                @if(($detail['credit'] ?? 0) > 0)
                                    {{ number_format($detail['credit'], 0, ',', '.') }}
                                    @php $grandTotalCredit += $detail['credit']; @endphp
                                @else
                                    -
                                @endif
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach

                <!-- Grand Total -->
                <tr class="grand-total-row">
                    <td colspan="6" class="text-right">TOTAL KESELURUHAN:</td>
                    <td class="text-right amount">{{ number_format($grandTotalDebit, 0, ',', '.') }}</td>
                    <td class="text-right amount">{{ number_format($grandTotalCredit, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>--- Tidak Ada Data Ditemukan ---</h3>
            <p>Silakan sesuaikan filter tanggal atau status Anda.</p>
        </div>
    @endif

    <!-- ====== FOOTER ====== -->
    <div class="footer-container">
        <table class="footer-table">
            <tr>
                <td>
                    <br>
                    <strong>Direktur Utama</strong>
                    <div class="signature-line"></div>
                    <div style="margin-top: 3px; font-weight: bold;">H. SUGENG, S.T., M.Si.</div>
                </td>
                <td></td>
                <td>
                    Purbalingga, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
                    <br>
                    <strong>Kepala Bagian Keuangan</strong>
                    <div class="signature-line"></div>
                    <div style="margin-top: 3px; font-weight: bold;">YUNI SETYOWATI, S.E.</div>
                </td>
            </tr>
        </table>

        <div class="print-info">
            Dicetak otomatis oleh Sistem Akuntansi PDAM Tirta Perwira pada {{ date('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
