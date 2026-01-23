<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Jurnal Rekening Air</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }

        .report-period {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .filter-info {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 11px;
        }

        .summary {
            background-color: #e8f5e8;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #4caf50;
        }

        .summary h3 {
            margin: 0 0 10px 0;
            color: #2e7d32;
            font-size: 14px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .summary-total {
            border-top: 1px solid #4caf50;
            margin-top: 10px;
            padding-top: 5px;
            font-weight: bold;
            color: #1b5e20;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #4caf50;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-nowrap { white-space: nowrap; }

        .journal-entry {
            background-color: #f9fbe7;
            margin-bottom: 15px;
            border: 1px solid #8bc34a;
            border-radius: 5px;
        }

        .journal-header {
            background-color: #8bc34a;
            color: white;
            padding: 10px;
            font-weight: bold;
        }

        .journal-body {
            padding: 15px;
        }

        .journal-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .info-group {
            background-color: #ffffff;
            padding: 10px;
            border-radius: 3px;
            border: 1px solid #e0e0e0;
        }

        .info-label {
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 5px;
        }

        .items-table {
            margin-top: 15px;
        }

        .items-table th {
            background-color: #689f38;
            font-size: 11px;
        }

        .items-table td {
            font-size: 10px;
        }

        .account-code {
            font-family: monospace;
            background-color: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 10px;
        }

        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .signature-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            text-align: center;
        }

        .signature-box {
            border: 1px solid #ddd;
            padding: 15px;
            min-height: 80px;
            background-color: #fafafa;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 50px;
            color: #333;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }

        @media print {
            body { margin: 10px; }
            .no-print { display: none; }
            .journal-entry { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'PDAM Kabupaten Purbalingga' }}</div>
        <div>{{ $company->address ?? 'Alamat Perusahaan' }}</div>
        <div class="report-title">LAPORAN JURNAL REKENING AIR & NON AIR</div>
        <div class="report-period">
            Periode: {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d F Y') : 'Semua' }}
            s/d {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d F Y') : 'Semua' }}
        </div>
        <div>Dicetak pada: {{ now()->format('d F Y H:i:s') }}</div>
    </div>

    {{-- Filter Info --}}
    @if($startDate || $endDate || $status)
    <div class="filter-info">
        <strong>Filter yang Diterapkan:</strong>
        @if($startDate) Dari Tanggal: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} | @endif
        @if($endDate) Sampai Tanggal: {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }} | @endif
        @if($status === 'confirmed') Status: Sudah Dikonfirmasi @endif
        @if($status === 'pending') Status: Belum Dikonfirmasi @endif
    </div>
    @endif

    {{-- Summary --}}
    <div class="summary">
        <h3>📊 Ringkasan Laporan</h3>
        <div class="summary-item">
            <span>Total Transaksi:</span>
            <span>{{ $journals->count() }} transaksi</span>
        </div>
        <div class="summary-item">
            <span>Sudah Dikonfirmasi:</span>
            <span>{{ $journals->where('is_confirmed', true)->count() }} transaksi</span>
        </div>
        <div class="summary-item">
            <span>Belum Dikonfirmasi:</span>
            <span>{{ $journals->where('is_confirmed', false)->count() }} transaksi</span>
        </div>
        <div class="summary-item summary-total">
            <span>Total Nilai Transaksi (Debit):</span>
            <span>Rp {{ number_format($journals->sum(function($j) { return $j->details->where('position', 'debit')->sum('jumlah'); }), 0, ',', '.') }}</span>
        </div>
        <div class="summary-item summary-total">
            <span>Total Nilai Transaksi (Kredit):</span>
            <span>Rp {{ number_format($journals->sum(function($j) { return $j->details->where('position', 'kredit')->sum('jumlah'); }), 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Jurnal Entries --}}
    @forelse($journals as $journal)
    <div class="journal-entry">
        <div class="journal-header">
            🧾 {{ $journal->no_reff }} - {{ $journal->tanggal->format('d F Y') }}
            @if($journal->is_confirmed)
                ✅ <small>(Dikonfirmasi)</small>
            @else
                ⏳ <small>(Belum Dikonfirmasi)</small>
            @endif
        </div>

        <div class="journal-body">
            {{-- Journal Info --}}
            <div class="journal-info">
                <div class="info-group">
                    <div class="info-label">Informasi Dasar</div>
                    <div><strong>No. Bukti:</strong> {{ $journal->bukti ?: '-' }}</div>
                    <div><strong>Tanggal:</strong> {{ $journal->tanggal->format('d/m/Y') }}</div>
                    <div><strong>Total Debit:</strong> Rp {{ number_format($journal->details->where('position', 'debit')->sum('jumlah'), 0, ',', '.') }}</div>
                    <div><strong>Total Kredit:</strong> Rp {{ number_format($journal->details->where('position', 'kredit')->sum('jumlah'), 0, ',', '.') }}</div>
                </div>

                <div class="info-group">
                    <div class="info-label">Status</div>
                    <div><strong>Status:</strong> 
                        @if($journal->is_confirmed)
                            <span style="color: green;">✅ Dikonfirmasi</span>
                        @else
                            <span style="color: orange;">⏳ Belum Dikonfirmasi</span>
                        @endif
                    </div>
                    @if($journal->confirmed_at)
                        <div><strong>Dikonfirmasi pada:</strong> {{ $journal->confirmed_at->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>

            {{-- Keterangan --}}
            @if($journal->keterangan)
            <div class="info-group" style="margin-bottom: 15px;">
                <div class="info-label">Keterangan</div>
                <div>{{ $journal->keterangan }}</div>
            </div>
            @endif

            {{-- Items Table - From Details Relation --}}
            @if($journal->details && $journal->details->count() > 0)
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th style="width: 15%">Kode Akun</th>
                        <th style="width: 30%">Nama Akun</th>
                        <th style="width: 10%">Posisi</th>
                        <th style="width: 15%">Jumlah</th>
                        <th style="width: 25%">Proyek</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->details as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="account-code text-center">
                            {{ $detail->kelompok->no_kel ?? '' }}-{{ $detail->rekening->no_rek ?? '' }}@if($detail->nomorBantu)-{{ $detail->nomorBantu->no_bantu }}@endif
                        </td>
                        <td>
                            <div><strong>{{ $detail->kelompok->nama_kel ?? '' }}</strong></div>
                            <div>{{ $detail->rekening->nama_rek ?? '' }}</div>
                            @if($detail->nomorBantu)
                                <div><small>{{ $detail->nomorBantu->nm_bantu }}</small></div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($detail->position === 'debit')
                                <span style="color: red; font-weight: bold;">DEBIT</span>
                            @else
                                <span style="color: green; font-weight: bold;">KREDIT</span>
                            @endif
                        </td>
                        <td class="text-right">
                            Rp {{ number_format($detail->jumlah ?? 0, 0, ',', '.') }}
                        </td>
                        <td>{{ $detail->kodeProyek->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #ffebee;">
                        <td colspan="4" class="text-center" style="font-weight: bold; color: red;">TOTAL DEBIT</td>
                        <td class="text-right" style="font-weight: bold; color: red;">
                            Rp {{ number_format($journal->details->where('position', 'debit')->sum('jumlah'), 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                    <tr style="background-color: #e8f5e9;">
                        <td colspan="4" class="text-center" style="font-weight: bold; color: green;">TOTAL KREDIT</td>
                        <td class="text-right" style="font-weight: bold; color: green;">
                            Rp {{ number_format($journal->details->where('position', 'kredit')->sum('jumlah'), 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
    </div>
    @empty
    <div style="text-align: center; padding: 40px; color: #666;">
        <h3>📭 Tidak ada data jurnal rekening air</h3>
        <p>Tidak ditemukan data jurnal rekening air untuk periode yang dipilih.</p>
    </div>
    @endforelse

    {{-- Signature Section --}}
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">Dibuat Oleh</div>
            <div class="signature-line">Staff Administrasi</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Diperiksa Oleh</div>
            <div class="signature-line">Kepala Bagian Keuangan</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Disetujui Oleh</div>
            <div class="signature-line">Direktur</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div>Laporan ini digenerate otomatis oleh Sistem Akuntansi Air Minum SAKEP v1.0</div>
        <div>{{ $company->name ?? 'PDAM Kabupaten Purbalingga' }} - {{ now()->format('Y') }}</div>
    </div>

</body>
</html>
