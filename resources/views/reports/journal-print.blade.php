<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $journal->reference }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .journal-info {
            margin: 20px 0;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
            width: 40%;
        }
        .info-value {
            width: 60%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-draft {
            color: #856404;
            background-color: #fff3cd;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-posted {
            color: #155724;
            background-color: #d4edda;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .balance-summary {
            background-color: #e3f2fd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #2196f3;
        }
        .balance-ok {
            color: #155724;
            font-weight: bold;
            background-color: #d4edda;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        .balance-error {
            color: #721c24;
            font-weight: bold;
            background-color: #f8d7da;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
        }
        .signature-box {
            border-top: 1px solid #333;
            padding: 60px 0 10px 0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .account-code {
            font-family: monospace;
            background-color: #e7f3ff;
            color: #0056b3;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <h2>{{ $journal->reference }}</h2>
        <h2>{{ $journal->company?->name ?? 'PT. TIRTA PERWIRA' }}</h2>
    </div>

    <div class="journal-info">
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">No. Referensi:</span>
                    <span class="info-value"><strong>{{ $journal->reference }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Transaksi:</span>
                    <span class="info-value">{{ $journal->transaction_date->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-{{ $journal->status }}">
                            {{ $journal->status === 'draft' ? 'DRAFT' : 'POSTED' }}
                        </span>
                    </span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value"><strong>Rp {{ number_format($journal->total_amount, 0, ',', '.') }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dibuat Oleh:</span>
                    <span class="info-value">{{ $journal->createdBy?->name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Dibuat:</span>
                    <span class="info-value">{{ $journal->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>

        @if($journal->description)
            <div style="margin-top: 15px;">
                <div class="info-label">Keterangan:</div>
                <div style="margin-top: 5px; padding: 10px; background-color: white; border-radius: 3px; border-left: 3px solid #007bff;">
                    {{ $journal->description }}
                </div>
            </div>
        @endif
    </div>

    @if($journal->details && $journal->details->count() > 0)
        <div style="margin-top: 30px;">
            <h3>Detail Transaksi</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%">No</th>
                        <th style="width: 35%">Kode & Nama Akun</th>
                        <th style="width: 20%">Debet</th>
                        <th style="width: 20%">Kredit</th>
                        <th style="width: 17%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalDebet = 0;
                        $totalKredit = 0;
                    @endphp
                    @foreach($journal->details as $index => $detail)
                        @php
                            $totalDebet += $detail->debit;
                            $totalKredit += $detail->credit;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                @if($detail->nomorBantu)
                                    <span class="account-code">
                                        {{ $detail->nomorBantu->rekening->kelompok->no_kel }}{{ $detail->nomorBantu->rekening->no_rek }}{{ str_pad($detail->nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) }}
                                    </span><br>
                                    <strong>{{ $detail->nomorBantu->nm_bantu }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">
                                @if($detail->debit > 0)
                                    <strong>Rp {{ number_format($detail->debit, 0, ',', '.') }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">
                                @if($detail->credit > 0)
                                    <strong>Rp {{ number_format($detail->credit, 0, ',', '.') }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $detail->description }}</td>
                        </tr>
                    @endforeach

                    {{-- Total Row --}}
                    <tr class="total-row">
                        <td colspan="2" class="text-center"><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($totalDebet, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($totalKredit, 0, ',', '.') }}</strong></td>
                        <td class="text-center">
                            @if(abs($totalDebet - $totalKredit) < 0.01)
                                <span class="balance-ok">SEIMBANG</span>
                            @else
                                <span class="balance-error">TIDAK SEIMBANG</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="balance-summary">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div>
                    <strong>Total Debet:</strong><br>
                    Rp {{ number_format($totalDebet, 0, ',', '.') }}
                </div>
                <div>
                    <strong>Total Kredit:</strong><br>
                    Rp {{ number_format($totalKredit, 0, ',', '.') }}
                </div>
                <div>
                    <strong>Status Balance:</strong><br>
                    @if(abs($totalDebet - $totalKredit) < 0.01)
                        <span class="balance-ok">SEIMBANG</span>
                    @else
                        <span class="balance-error">SELISIH: Rp {{ number_format(abs($totalDebet - $totalKredit), 0, ',', '.') }}</span>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 50px; color: #666; background-color: #f8f9fa; border-radius: 5px;">
            <h3>Tidak ada detail transaksi</h3>
        </div>
    @endif

    <div class="footer">
        <p><strong>{{ config('app.name') }} - Sistem Akuntansi Tirta Perwira</strong></p>
        <p>Dicetak pada: {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y \p\u\k\u\l H:i') }} WIB</p>
    </div>
</body>
</html>
