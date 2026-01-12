<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Jurnal Memorial</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }
        .period {
            font-size: 11px;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 6px;
            font-weight: bold;
            text-align: left;
        }
        td {
            border: 1px solid #000;
            padding: 5px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'PDAM PURBALINGGA' }}</div>
        <div class="report-title">LAPORAN JURNAL MEMORIAL</div>
        <div class="period">
            Periode: {{ $startDate }} s/d {{ $endDate }}
            @if($status !== 'semua')
                | Status: {{ ucfirst($status) }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="10%">Tanggal</th>
                <th width="12%">No. Bukti</th>
                <th width="15%">Kelompok</th>
                <th width="20%">Rekening</th>
                <th width="13%">Nomor Bantu</th>
                <th width="5%" class="text-center">D/K</th>
                <th width="15%" class="text-right">Jumlah (Rp)</th>
                <th width="5%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @forelse($journals as $index => $journal)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $journal->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $journal->bukti }}</td>
                    <td>{{ $journal->kelompok->nama_kel ?? '-' }}</td>
                    <td>{{ $journal->rekening->nama_rek ?? '-' }}</td>
                    <td>{{ $journal->nomorBantu->nm_bantu ?? '-' }}</td>
                    <td class="text-center">{{ $journal->kode }}</td>
                    <td class="text-right">{{ number_format($journal->rp, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $journal->is_confirmed ? '✓' : '-' }}</td>
                </tr>
                @php $total += $journal->rp; @endphp
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
            @if($journals->count() > 0)
                <tr class="total-row">
                    <td colspan="7" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>{{ number_format($total, 0, ',', '.') }}</strong></td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
