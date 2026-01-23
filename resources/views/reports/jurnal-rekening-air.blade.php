<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Jurnal Rekening Air & Non Air</title>
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

        /* Filter Info */
        .filter-info {
            background-color: #f8f9fa;
            padding: 8px 10px;
            margin-bottom: 15px;
            border-left: 3px solid #007bff;
            font-size: 10px;
        }

        .filter-info strong {
            color: #007bff;
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
            padding: 6px 4px;
        }

        .detail-table .total-debit {
            background-color: #f8d7da;
            color: #721c24;
        }

        .detail-table .total-kredit {
            background-color: #d4edda;
            color: #155724;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
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
            padding-top: 5px;
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
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 10mm 10mm 15mm 10mm;
            }
            .journal-entry {
                page-break-inside: avoid;
            }
            .signature-section {
                page-break-inside: avoid;
            }
        }

        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>

    {{-- Header Perusahaan --}}
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'PDAM KABUPATEN PURBALINGGA' }}</div>
        <div class="company-address">{{ $company->address ?? 'Jl. Letjen Soeprapto No.1, Purbalingga' }}</div>
        <div class="company-contact">Telp: {{ $company->phone ?? '(0281) 891234' }} | Email: {{ $company->email ?? 'info@pdampurbalingga.co.id' }}</div>
    </div>

    {{-- Report Title & Period (Setelah garis header) --}}
    <div class="report-title">LAPORAN JURNAL REKENING AIR & NON AIR</div>
    <div class="report-period">
        Periode: {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d F Y') : 'Semua' }}
        s.d {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d F Y') : 'Semua' }}
    </div>

    {{-- Summary --}}
    <div class="summary-box">
        <div class="summary-header">RINGKASAN LAPORAN</div>
        <div class="summary-content">
            <div class="summary-row">
                <div class="summary-label">Total Transaksi</div>
                <div class="summary-value">{{ $journals->count() }} transaksi</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Sudah Dikonfirmasi</div>
                <div class="summary-value">{{ $journals->where('is_confirmed', true)->count() }} transaksi</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Belum Dikonfirmasi</div>
                <div class="summary-value">{{ $journals->where('is_confirmed', false)->count() }} transaksi</div>
            </div>
            <div class="summary-row summary-total">
                <div class="summary-label">Total Nilai Debit</div>
                <div class="summary-value">Rp {{ number_format($journals->sum(function($j) { return $j->details->where('position', 'debit')->sum('jumlah'); }), 0, ',', '.') }}</div>
            </div>
            <div class="summary-row summary-total">
                <div class="summary-label">Total Nilai Kredit</div>
                <div class="summary-value">Rp {{ number_format($journals->sum(function($j) { return $j->details->where('position', 'kredit')->sum('jumlah'); }), 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Detail Jurnal Entries --}}
    @forelse($journals as $journalIndex => $journal)
    <div class="journal-entry">
        <div class="journal-header clearfix">
            <span class="journal-no">No. Jurnal: {{ $journal->no_reff }}</span>
            <span class="journal-date">{{ $journal->tanggal->format('d F Y') }}</span>
            <div class="journal-status">
                @if($journal->is_confirmed)
                    <span class="status-confirmed">● Dikonfirmasi @if($journal->confirmed_at)({{ $journal->confirmed_at->format('d/m/Y H:i') }})@endif</span>
                @else
                    <span class="status-pending">● Belum Dikonfirmasi</span>
                @endif
            </div>
        </div>

        <div class="journal-body">
            {{-- Informasi Jurnal --}}
            <table class="info-table">
                <tr>
                    <td class="label">No. Bukti</td>
                    <td class="colon">:</td>
                    <td class="value text-bold">{{ $journal->bukti ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Keterangan</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $journal->keterangan ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Total Debit</td>
                    <td class="colon">:</td>
                    <td class="value text-bold">Rp {{ number_format($journal->details->where('position', 'debit')->sum('jumlah'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Total Kredit</td>
                    <td class="colon">:</td>
                    <td class="value text-bold">Rp {{ number_format($journal->details->where('position', 'kredit')->sum('jumlah'), 0, ',', '.') }}</td>
                </tr>
            </table>

            {{-- Detail Transaksi --}}
            @if($journal->details && $journal->details->count() > 0)
            <table class="detail-table">
                <thead>
                    <tr>
                        <th style="width: 4%">No</th>
                        <th style="width: 13%">Kode Akun</th>
                        <th style="width: 35%">Nama Akun</th>
                        <th style="width: 8%">Posisi</th>
                        <th style="width: 18%">Jumlah (Rp)</th>
                        <th style="width: 22%">Kode Proyek</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->details as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center account-code">
                            {{ str_pad($detail->kelompok->no_kel ?? '0', 2, '0', STR_PAD_LEFT) }}.{{ str_pad($detail->rekening->no_rek ?? '0', 4, '0', STR_PAD_LEFT) }}@if($detail->nomorBantu).{{ str_pad($detail->nomorBantu->no_bantu ?? '0', 2, '0', STR_PAD_LEFT) }}@endif
                        </td>
                        <td class="account-name">
                            <strong>{{ $detail->rekening->nama_rek ?? '-' }}</strong>
                            @if($detail->nomorBantu)
                                <br><small style="color: #666;">{{ $detail->nomorBantu->nm_bantu }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($detail->position === 'debit')
                                <span class="position-debit">D</span>
                            @else
                                <span class="position-kredit">K</span>
                            @endif
                        </td>
                        <td class="text-right">
                            {{ number_format($detail->jumlah ?? 0, 0, ',', '.') }}
                        </td>
                        <td style="font-size: 9px;">{{ $detail->kodeProyek ? $detail->kodeProyek->kode . ' - ' . $detail->kodeProyek->name : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-debit">
                        <td colspan="4" class="text-right">TOTAL DEBIT:</td>
                        <td class="text-right">{{ number_format($journal->details->where('position', 'debit')->sum('jumlah'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    <tr class="total-kredit">
                        <td colspan="4" class="text-right">TOTAL KREDIT:</td>
                        <td class="text-right">{{ number_format($journal->details->where('position', 'kredit')->sum('jumlah'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
    </div>
    @empty
    <div style="text-align: center; padding: 40px; border: 1px dashed #ccc;">
        <p style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">DATA TIDAK DITEMUKAN</p>
        <p style="font-size: 10px; color: #666;">Tidak ada data jurnal rekening air untuk periode yang dipilih.</p>
    </div>
    @endforelse

    {{-- Signature Section --}}
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-title">Dibuat Oleh,</div>
                    <div class="signature-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
                    <div class="signature-position">Staff Administrasi</div>
                </td>
                <td>
                    <div class="signature-title">Diperiksa Oleh,</div>
                    <div class="signature-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
                    <div class="signature-position">Kepala Bagian Keuangan</div>
                </td>
                <td>
                    <div class="signature-title">Disetujui Oleh,</div>
                    <div class="signature-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
                    <div class="signature-position">Direktur</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div style="margin-bottom: 5px;">Dicetak pada: {{ now()->format('d F Y, H:i:s') }} WIB</div>
        <div>Dokumen ini digenerate secara otomatis oleh Sistem Akuntansi Air Minum SAKEP v1.0</div>
        <div>{{ $company->name ?? 'PDAM Kabupaten Purbalingga' }} &copy; {{ now()->format('Y') }}</div>
    </div>

</body>
</html>
