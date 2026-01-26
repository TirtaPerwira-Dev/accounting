<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Jurnal Penerimaan Kas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            padding: 15mm 15mm 20mm 15mm;
        }

        /* Header Perusahaan */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px double #000;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }

        .company-address {
            font-size: 10px;
            margin-bottom: 2px;
        }

        .company-contact {
            font-size: 9px;
            color: #333;
            margin-bottom: 8px;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 15px 0 5px 0;
            letter-spacing: 1px;
            text-align: center;
        }

        .report-period {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }

        .print-date {
            font-size: 9px;
            color: #555;
            font-style: italic;
        }

        /* Summary Box */
        .summary-box {
            border: 1px solid #ddd;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .summary-header {
            background-color: #f0f0f0;
            padding: 6px 10px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            font-size: 11px;
        }

        .summary-content {
            padding: 8px 10px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .summary-label {
            display: table-cell;
            width: 70%;
            font-size: 10px;
        }

        .summary-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: bold;
            font-size: 10px;
        }

        .summary-total {
            border-top: 2px solid #333;
            padding-top: 6px;
            margin-top: 6px;
        }

        .summary-total .summary-label {
            font-weight: bold;
        }

        /* Journal Entry */
        .journal-entry {
            border: 1px solid #333;
            margin-bottom: 20px;
            page-break-inside: avoid;
            background-color: #fff;
        }

        .journal-header {
            background-color: #e9ecef;
            padding: 6px 10px;
            border-bottom: 1px solid #333;
            font-weight: bold;
            font-size: 11px;
        }

        .journal-no {
            float: left;
        }

        .journal-date {
            float: right;
        }

        .journal-status {
            clear: both;
            font-size: 9px;
            margin-top: 2px;
            font-weight: normal;
        }

        .status-confirmed {
            color: #28a745;
        }

        .status-pending {
            color: #ffc107;
        }

        .journal-body {
            padding: 10px;
        }

        /* Journal Info Table */
        .info-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 10px;
        }

        .info-table td {
            padding: 3px 5px;
            vertical-align: top;
        }

        .info-table .label {
            width: 30%;
            font-weight: bold;
        }

        .info-table .colon {
            width: 3%;
        }

        .info-table .value {
            width: 67%;
        }

        /* Detail Transaction Table */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 10px;
        }

        .detail-table th {
            background-color: #6c757d;
            color: #fff;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #495057;
        }

        .detail-table td {
            padding: 5px 4px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }

        .detail-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .detail-table .account-code {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            white-space: nowrap;
        }

        .detail-table .account-name {
            font-size: 9px;
            line-height: 1.3;
        }

        .detail-table .position-debit {
            color: #dc3545;
            font-weight: bold;
        }

        .detail-table .position-kredit {
            color: #28a745;
            font-weight: bold;
        }

        .detail-table tfoot td {
            font-weight: bold;
            background-color: #e9ecef;
            border-top: 2px solid #333;
        }

        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .signature-row {
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 10px;
        }

        .signature-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 50px;
        }

        .signature-name {
            font-size: 10px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 3px;
            display: inline-block;
            min-width: 150px;
        }

        .signature-position {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 15mm;
            right: 15mm;
            text-align: right;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Page Break */
        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                padding: 0;
            }

            .journal-entry {
                page-break-inside: avoid;
            }

            .signature-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'PDAM Tirta Bening Purbalingga' }}</div>
        <div class="company-address">{{ $company->address ?? 'Jl. Jend. Sudirman No. 1 Purbalingga' }}</div>
        <div class="company-contact">
            Telp: {{ $company->phone ?? '0281-891234' }} | 
            Email: {{ $company->email ?? 'pdam@purbalingga.go.id' }} | 
            Website: {{ $company->website ?? 'www.pdampurbalingga.go.id' }}
        </div>
    </div>

    <!-- Report Title & Period -->
    <div class="report-title">LAPORAN JURNAL PENERIMAAN KAS</div>
    <div class="report-period">
        @if($startDate && $endDate)
            Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
        @else
            Periode: Semua Transaksi
        @endif
    </div>

    <!-- Summary Box -->
    @php
        $totalJurnal = $journals->count();
        $totalDebit = 0;
        $totalKredit = 0;
        $jumlahKonfirmasi = $journals->where('is_confirmed', true)->count();
        $jumlahPending = $journals->where('is_confirmed', false)->count();

        foreach($journals as $journal) {
            // Total debit adalah jumlah kas/bank yang diterima
            $totalDebit += $journal->total_amount ?? 0;
            // Total kredit adalah jumlah dari semua detail
            foreach($journal->details as $detail) {
                $totalKredit += $detail->jumlah ?? 0;
            }
        }
    @endphp

    <div class="summary-box">
        <div class="summary-header">RINGKASAN LAPORAN</div>
        <div class="summary-content">
            <div class="summary-row">
                <div class="summary-label">Jumlah Transaksi</div>
                <div class="summary-value">{{ number_format($totalJurnal, 0, ',', '.') }} transaksi</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Transaksi Terkonfirmasi</div>
                <div class="summary-value">{{ number_format($jumlahKonfirmasi, 0, ',', '.') }} transaksi</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Transaksi Pending</div>
                <div class="summary-value">{{ number_format($jumlahPending, 0, ',', '.') }} transaksi</div>
            </div>
            <div class="summary-row summary-total">
                <div class="summary-label">Total Debit</div>
                <div class="summary-value">Rp {{ number_format($totalDebit, 2, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Kredit</div>
                <div class="summary-value">Rp {{ number_format($totalKredit, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Journal Entries -->
    @foreach($journals as $journal)
        <div class="journal-entry">
            <div class="journal-header clearfix">
                <span class="journal-no">No. Jurnal: {{ $journal->no_jurnal }}</span>
                <span class="journal-date">{{ \Carbon\Carbon::parse($journal->tanggal)->format('d F Y') }}</span>
                <div class="journal-status">
                    Status: 
                    @if($journal->is_confirmed)
                        <span class="status-confirmed">✓ Dikonfirmasi</span>
                    @else
                        <span class="status-pending">⚠ Pending</span>
                    @endif
                </div>
            </div>

            <div class="journal-body">
                <!-- Info Table -->
                <table class="info-table">
                    <tr>
                        <td class="label">Kas/Bank (Debit)</td>
                        <td class="colon">:</td>
                        <td class="value">
                            @if($journal->kasBank)
                                {{ $journal->kasBank->kelompok->no_kel ?? '' }}-{{ $journal->kasBank->rekening->no_rek ?? '' }}-{{ $journal->kasBank->no_bantu ?? '' }}
                                - {{ $journal->kasBank->nm_bantu }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Jumlah Diterima</td>
                        <td class="colon">:</td>
                        <td class="value">Rp {{ number_format($journal->total_amount ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Keterangan</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $journal->keterangan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nomor Bukti</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $journal->nomor_bukti ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Referensi</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $journal->reff ?? '-' }}</td>
                    </tr>
                </table>

                <!-- Detail Transaction Table -->
                @php
                    $journalDebit = $journal->total_amount ?? 0;
                    $journalKredit = 0;
                @endphp

                <table class="detail-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 20%;">Kode Akun</th>
                            <th style="width: 35%;">Nama Akun</th>
                            <th style="width: 10%;">Posisi</th>
                            <th style="width: 30%;">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Baris pertama: Kas/Bank (Debit) -->
                        <tr style="background-color: #fff3cd;">
                            <td style="text-align: center;">1</td>
                            <td class="account-code">
                                @if($journal->kasBank)
                                    {{ $journal->kasBank->kelompok->no_kel ?? '' }}-{{ $journal->kasBank->rekening->no_rek ?? '' }}-{{ $journal->kasBank->no_bantu ?? '' }}
                                @else
                                    {{ $journal->kelompok->no_kel ?? '' }}-{{ $journal->rekening->no_rek ?? '' }}
                                @endif
                            </td>
                            <td class="account-name">
                                <strong>{{ $journal->kasBank->nm_bantu ?? $journal->rekening->nama_rek ?? 'Kas/Bank' }}</strong>
                            </td>
                            <td style="text-align: center;">
                                <span class="position-debit" style="font-weight: bold;">D</span>
                            </td>
                            <td class="amount" style="font-weight: bold;">
                                {{ number_format($journal->total_amount ?? 0, 2, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Detail kredit -->
                        @foreach($journal->details as $index => $detail)
                            @php
                                $journalKredit += $detail->jumlah ?? 0;
                            @endphp
                            <tr>
                                <td style="text-align: center;">{{ $index + 2 }}</td>
                                <td class="account-code">
                                    {{ $detail->kelompok->no_kel ?? '' }}-{{ $detail->rekening->no_rek ?? '' }}-{{ $detail->nomorBantu->no_bantu ?? '' }}
                                </td>
                                <td class="account-name">
                                    {{ $detail->nomorBantu->nm_bantu ?? $detail->rekening->nama_rek ?? '-' }}
                                    @if($detail->kodeProyek)
                                        <br><small style="color: #666;">[{{ $detail->kodeProyek->kode_proyek }} - {{ $detail->kodeProyek->nama_proyek }}]</small>
                                    @endif
                                    @if($detail->keterangan_item)
                                        <br><small style="color: #666; font-style: italic;">{{ $detail->keterangan_item }}</small>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="position-kredit">K</span>
                                </td>
                                <td class="amount">
                                    {{ number_format($detail->jumlah ?? 0, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: right; padding-right: 10px;">TOTAL DEBIT & KREDIT</td>
                            <td class="amount">
                                {{ number_format($journalDebit, 2, ',', '.') }} = {{ number_format($journalKredit, 2, ',', '.') }}
                            </td>
                        </tr>
                        @if(abs($journalDebit - $journalKredit) > 0.01)
                            <tr>
                                <td colspan="5" style="text-align: center; color: #dc3545; font-weight: bold; background-color: #f8d7da;">
                                    ⚠ PERINGATAN: Debit dan Kredit tidak seimbang! Selisih: Rp {{ number_format(abs($journalDebit - $journalKredit), 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach

    @if($journals->isEmpty())
        <div style="text-align: center; padding: 40px; background-color: #f8f9fa; border: 1px dashed #dee2e6; margin-top: 20px;">
            <p style="font-size: 12px; color: #6c757d;">Tidak ada data jurnal penerimaan kas untuk periode yang dipilih.</p>
        </div>
    @endif

    <!-- Signature Section -->
    @if(!$journals->isEmpty())
        <div class="signature-section">
            <div class="signature-row">
                <div class="signature-box">
                    <div class="signature-title">Dibuat Oleh,</div>
                    <div class="signature-name">____________________</div>
                    <div class="signature-position">Staf Keuangan</div>
                </div>
                <div class="signature-box">
                    <div class="signature-title">Diperiksa Oleh,</div>
                    <div class="signature-name">____________________</div>
                    <div class="signature-position">Kepala Bagian Keuangan</div>
                </div>
                <div class="signature-box">
                    <div class="signature-title">Disetujui Oleh,</div>
                    <div class="signature-name">____________________</div>
                    <div class="signature-position">Direktur</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div style="margin-bottom: 5px;">Dicetak pada: {{ now()->format('d F Y, H:i:s') }} WIB</div>
        <div>Dokumen ini digenerate secara otomatis oleh Sistem Akuntansi Air Minum SAKEP v1.0</div>
        <div>{{ $company->name ?? 'PDAM Kabupaten Purbalingga' }} &copy; {{ now()->format('Y') }}</div>
    </div>
</body>
</html>
