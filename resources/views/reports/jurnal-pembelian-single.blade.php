<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Pembelian Barang - {{ $jurnal->no_reff }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
            line-height: 1.4;
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

        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }

        .jurnal-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            display: inline-block;
        }

        .info-value {
            flex: 1;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding: 8px;
            background-color: #e9ecef;
            border-left: 4px solid #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .amount {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #d32f2f;
        }

        .kode-sakep {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #1976d2;
            background-color: #e3f2fd;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-confirmed {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        .jurnal-entry {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .debit-kredit-table {
            margin: 20px 0;
        }

        .debit-row {
            background-color: #e8f5e8;
        }

        .kredit-row {
            background-color: #ffe8e8;
        }

        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 11px;
        }

        .print-info {
            font-size: 10px;
            color: #666;
            text-align: center;
            margin-top: 20px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(255, 0, 0, 0.1);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>
    @if(!$jurnal->is_confirmed)
        <div class="watermark">BELUM DIKONFIRMASI</div>
    @endif

    <!-- Header -->
    <div class="header">
        <div class="company-name">SISTEM AKUNTANSI AIR MINUM - SAKEP</div>
        <div class="document-title">JURNAL PEMBELIAN BARANG</div>
        <div style="font-size: 12px; color: #666;">No. Referensi: <strong>{{ $jurnal->no_reff }}</strong></div>
    </div>

    <!-- Informasi Jurnal -->
    <div class="jurnal-info">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div class="info-row">
                    <span class="info-label">No. Referensi:</span>
                    <span class="info-value"><strong>{{ $jurnal->no_reff }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal:</span>
                    <span class="info-value">{{ $jurnal->tanggal->format('d M Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. Bukti:</span>
                    <span class="info-value">{{ $jurnal->bukti ?: '-' }}</span>
                </div>
                @if($jurnal->kodeProyek)
                <div class="info-row">
                    <span class="info-label">Kode Proyek:</span>
                    <span class="info-value">{{ $jurnal->kodeProyek->name }}</span>
                </div>
                @endif
            </div>
            <div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        @if($jurnal->is_confirmed)
                            <span class="status-badge status-confirmed">✓ Dikonfirmasi</span>
                        @else
                            <span class="status-badge status-pending">⏱ Belum Dikonfirmasi</span>
                        @endif
                    </span>
                </div>
                @if($jurnal->is_confirmed)
                <div class="info-row">
                    <span class="info-label">Dikonfirmasi:</span>
                    <span class="info-value">{{ $jurnal->confirmed_at->format('d M Y H:i') }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Total Nilai:</span>
                    <span class="info-value amount" style="font-size: 14px;">Rp {{ number_format($jurnal->rp, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        @if($jurnal->keterangan)
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <span class="info-label">Keterangan:</span><br>
            <div style="margin-top: 5px; font-style: italic; background-color: #fff; padding: 8px; border-radius: 3px;">
                {{ $jurnal->keterangan }}
            </div>
        </div>
        @endif
    </div>

    <!-- Akun Hutang/Kredit -->
    <div class="section-title">AKUN HUTANG/KREDIT</div>
    <table>
        <tr>
            <td width="20%" style="font-weight: bold;">Kode SAKEP:</td>
            <td width="30%"><span class="kode-sakep">{{ $jurnal->kode_sakep_kredit }}</span></td>
            <td width="20%" style="font-weight: bold;">Nama Akun:</td>
            <td width="30%">{{ $jurnal->nama_akun_kredit }}</td>
        </tr>
    </table>

    <!-- Detail Pembelian -->
    <div class="section-title">DETAIL PEMBELIAN</div>
    @if($jurnal->pembelian_items && count($jurnal->pembelian_items) > 0)
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="25%">Keterangan</th>
                    <th width="15%">Kode SAKEP</th>
                    <th width="30%">Akun Debit</th>
                    <th width="25%">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jurnal->pembelian_items_with_details as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['keterangan'] ?? 'Item pembelian' }}</td>
                    <td class="text-center">
                        <span class="kode-sakep">{{ $item['kode_sakep_debit'] ?? '-' }}</span>
                    </td>
                    <td>{{ $item['nama_akun_debit'] ?? '-' }}</td>
                    <td class="text-right amount">Rp {{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right amount">Rp {{ number_format($jurnal->rp, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div style="text-align: center; padding: 20px; color: #666; font-style: italic;">
            Tidak ada detail pembelian
        </div>
    @endif

    <!-- Jurnal Entry Format -->
    <div class="section-title">FORMAT JURNAL AKUNTANSI</div>
    <table class="debit-kredit-table">
        <thead>
            <tr>
                <th width="10%">Tanggal</th>
                <th width="40%">Keterangan</th>
                <th width="25%">Debit</th>
                <th width="25%">Kredit</th>
            </tr>
        </thead>
        <tbody>
            <!-- Debit Entries (Pembelian Items) -->
            @if($jurnal->pembelian_items)
                @foreach($jurnal->pembelian_items_with_details as $item)
                <tr class="debit-row">
                    <td class="text-center">{{ $jurnal->tanggal->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $item['nama_akun_debit'] ?? '-' }}</strong><br>
                        <small>{{ $item['keterangan'] ?? 'Item pembelian' }}</small>
                    </td>
                    <td class="text-right amount">{{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endforeach
            @endif

            <!-- Kredit Entry (Hutang) -->
            <tr class="kredit-row">
                <td class="text-center">{{ $jurnal->tanggal->format('d/m/Y') }}</td>
                <td>
                    <strong>{{ $jurnal->nama_akun_kredit }}</strong><br>
                    <small>{{ $jurnal->bukti ? 'No. Bukti: ' . $jurnal->bukti : 'Hutang pembelian' }}</small>
                </td>
                <td class="text-right">-</td>
                <td class="text-right amount">{{ number_format($jurnal->rp, 0, ',', '.') }}</td>
            </tr>

            <!-- Total -->
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right amount">{{ number_format($jurnal->rp, 0, ',', '.') }}</td>
                <td class="text-right amount">{{ number_format($jurnal->rp, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <div class="signature-box">
            <div>Dibuat oleh:</div>
            <div class="signature-line">Bagian Keuangan</div>
        </div>

        @if($jurnal->is_confirmed)
        <div class="signature-box">
            <div>Disetujui oleh:</div>
            <div class="signature-line">Kepala Bagian</div>
        </div>
        @endif
    </div>

    <div class="print-info">
        Dicetak pada: {{ $generatedAt }} | Sistem Akuntansi Air Minum berbasis SAKEP
    </div>
</body>
</html>
