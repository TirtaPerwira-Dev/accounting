<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $voucher['title'] }} - {{ $voucher['number'] }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .company-info {
            font-size: 8pt;
            color: #666;
        }
        .voucher-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .voucher-meta {
            width: 100%;
            margin-bottom: 20px;
        }
        .voucher-meta td {
            vertical-align: top;
        }
        .meta-label {
            font-weight: bold;
            width: 120px;
        }
        .meta-value {
            border-bottom: 1px dotted #ccc;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.details th {
            background-color: #f2f2f2;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-size: 9pt;
        }
        table.details td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 9pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .terbilang {
            margin-bottom: 30px;
            font-style: italic;
            font-size: 9pt;
            padding: 10px;
            border: 1px solid #eee;
        }
        .signatures {
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            text-align: center;
            width: 25%;
            font-size: 9pt;
        }
        .signature-space {
            height: 60px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 7pt;
            color: #999;
            text-align: right;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%;">
                    <div class="company-name">{{ $company->name ?? 'PERUMDA AIR MINUM TIRTA PERWIRA' }}</div>
                    <div class="company-info">
                        {{ $company->address ?? 'Jl. Kaswari No. 3 Purbalingga' }}<br>
                        Telp: {{ $company->phone ?? '-' }} | NPWP: {{ $company->npwp ?? '-' }}
                    </div>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: middle;">
                    @if(isset($company->logo) && $company->logo)
                        {{-- Note: DomPDF needs absolute path or base64 for images in some configs --}}
                        {{-- <img src="{{ public_path('storage/' . $company->logo) }}" style="height: 60px;"> --}}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="voucher-title">{{ $voucher['title'] }}</div>

    <table class="voucher-meta">
        <tr>
            <td style="width: 60%;">
                <table>
                    <tr>
                        <td class="meta-label">Dibayarkan ke/dari</td>
                        <td>:</td>
                        <td class="meta-value" style="width: 250px;">{{ $voucher['payee'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Keterangan</td>
                        <td>:</td>
                        <td class="meta-value">{{ $voucher['description'] }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%;">
                <table style="width: 100%;">
                    <tr>
                        <td class="meta-label">Nomor</td>
                        <td>:</td>
                        <td class="meta-value">{{ $voucher['number'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td>:</td>
                        <td class="meta-value">{{ \Carbon\Carbon::parse($voucher['date'])->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">No. Reff</td>
                        <td>:</td>
                        <td class="meta-value">{{ $voucher['reference'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="details">
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 15%;">KODE AKUN</th>
                <th style="width: 40%;">URAIAN / KETERANGAN</th>
                <th style="width: 20%;">DEBET (Rp)</th>
                <th style="width: 20%;">KREDIT (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalDebit = 0; $totalCredit = 0; @endphp
            @foreach($voucher['items'] as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item['code'] }}</td>
                    <td>
                        <div class="font-bold">{{ $item['name'] }}</div>
                        <div style="font-size: 8pt; color: #555;">{{ $item['description'] ?? '' }}</div>
                    </td>
                    <td class="text-right">
                        {{ $item['debit'] > 0 ? number_format($item['debit'], 0, ',', '.') : '' }}
                    </td>
                    <td class="text-right">
                        {{ $item['credit'] > 0 ? number_format($item['credit'], 0, ',', '.') : '' }}
                    </td>
                </tr>
                @php 
                    $totalDebit += $item['debit'];
                    $totalCredit += $item['credit'];
                @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($totalDebit, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalCredit, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="terbilang">
        <strong>Terbilang:</strong> 
        {{ ucwords(\App\Helpers\NumberHelper::terbilang($totalDebit > 0 ? $totalDebit : $totalCredit)) }} Rupiah
    </div>

    <table class="signatures">
        <tr>
            <td class="signature-box">
                <div>Menyetujui,</div>
                <div class="signature-space"></div>
                <div class="signature-name">H. SUGENG, S.T., M.S.I.</div>
                <div>Direktur Utama</div>
            </td>
            <td class="signature-box">
                <div>Mengetahui,</div>
                <div class="signature-space"></div>
                <div class="signature-name">YUNI SETYOWATI, S.E.</div>
                <div>Kabag Keuangan</div>
            </td>
            <td class="signature-box">
                <div>Diperiksa Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">..........................</div>
                <div>Kasubag Akuntansi</div>
            </td>
            <td class="signature-box">
                <div>Pembuat,</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $voucher['created_by'] ?? '..........................' }}</div>
                <div>Staf Akuntansi</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }} | Sistem Akuntansi Terpadu - Tirta Perwira
    </div>
</body>
</html>
