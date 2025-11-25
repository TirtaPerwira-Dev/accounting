<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
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
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .info {
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
        }
        .info-item {
            margin: 5px 0;
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
            vertical-align: top;
        }
        th {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }
        td {
            font-size: 11px;
            line-height: 1.4;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .journal-detail {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #007bff;
        }
        .status-draft {
            color: #856404;
            background-color: #fff3cd;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-posted {
            color: #155724;
            background-color: #d4edda;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .balance-ok {
            color: #155724;
            font-weight: bold;
            background-color: #d4edda;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
        }
        .balance-error {
            color: #721c24;
            font-weight: bold;
            background-color: #f8d7da;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .summary {
            background-color: #e3f2fd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .account-code {
            font-family: monospace;
            background-color: #e7f3ff;
            color: #0056b3;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <h2>Periode: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($until)->format('d/m/Y') }}</h2>
        <h2>Status: {{ $status === 'all' ? 'Semua Status' : ($status === 'draft' ? 'Draft Saja' : 'Posted Saja') }}</h2>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td><strong>Total Jurnal:</strong></td>
                <td>{{ $journals->count() }} transaksi</td>
                <td><strong>Total Nominal:</strong></td>
                <td class="text-right">Rp {{ number_format($journals->sum('total_amount'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Draft:</strong></td>
                <td>{{ $journals->where('status', 'draft')->count() }} transaksi</td>
                <td><strong>Posted:</strong></td>
                <td>{{ $journals->where('status', 'posted')->count() }} transaksi</td>
            </tr>
        </table>
    </div>

    @if($journals->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 12%">Tanggal</th>
                    <th style="width: 18%">No. Referensi</th>
                    <th style="width: 10%">Status</th>
                    <th style="width: 25%">Keterangan</th>
                    <th style="width: 15%">Total Amount</th>
                    <th style="width: 12%">Balance</th>
                    <th style="width: 8%">Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journals as $journal)
                    <tr>
                        <td class="text-center">{{ $journal->transaction_date->format('d/m/Y') }}</td>
                        <td><strong>{{ $journal->reference }}</strong></td>
                        <td class="text-center">
                            <span class="status-{{ $journal->status }}">
                                {{ $journal->status === 'draft' ? 'DRAFT' : 'POSTED' }}
                            </span>
                        </td>
                        <td>{{ $journal->description ?: '-' }}</td>
                        <td class="text-right"><strong>Rp {{ number_format($journal->total_amount, 0, ',', '.') }}</strong></td>
                        <td class="text-center">
                            @if($journal->is_balanced)
                                <span class="balance-ok">SEIMBANG</span>
                            @else
                                <span class="balance-error">TIDAK SEIMBANG</span>
                            @endif
                        </td>
                        <td style="font-size: 10px;">{{ $journal->createdBy?->name ?? '-' }}</td>
                    </tr>

                    {{-- Detail Jurnal --}}
                    @if($journal->details && $journal->details->count() > 0)
                        <tr>
                            <td colspan="7" class="journal-detail">
                                <table style="width: 100%; border: none; margin: 5px 0;">
                                    <thead>
                                        <tr style="border: none;">
                                            <th style="border: 1px solid #333; width: 40%; background-color: #e8f4fd; font-size: 10px;">Kode & Nama Akun</th>
                                            <th style="border: 1px solid #333; width: 18%; background-color: #e8f4fd; font-size: 10px;">Debet</th>
                                            <th style="border: 1px solid #333; width: 18%; background-color: #e8f4fd; font-size: 10px;">Kredit</th>
                                            <th style="border: 1px solid #333; width: 24%; background-color: #e8f4fd; font-size: 10px;">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($journal->details as $detail)
                                            <tr>
                                                <td style="border: 1px solid #333; font-size: 10px; padding: 8px;">
                                                    @if($detail->nomorBantu)
                                                        <span class="account-code">{{ $detail->nomorBantu->rekening->kelompok->no_kel }}{{ $detail->nomorBantu->rekening->no_rek }}{{ str_pad($detail->nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) }}</span><br>
                                                        <strong>{{ $detail->nomorBantu->nm_bantu }}</strong>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td style="border: 1px solid #333; text-align: right; font-size: 10px; padding: 8px;">
                                                    @if($detail->debit > 0)
                                                        <strong>Rp {{ number_format($detail->debit, 0, ',', '.') }}</strong>
                                                    @else
                                                        <span style="color: #999;">-</span>
                                                    @endif
                                                </td>
                                                <td style="border: 1px solid #333; text-align: right; font-size: 10px; padding: 8px;">
                                                    @if($detail->credit > 0)
                                                        <strong>Rp {{ number_format($detail->credit, 0, ',', '.') }}</strong>
                                                    @else
                                                        <span style="color: #999;">-</span>
                                                    @endif
                                                </td>
                                                <td style="border: 1px solid #333; font-size: 10px; padding: 8px;">{{ $detail->description ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 50px; color: #666;">
            <h3>Tidak ada data jurnal untuk periode yang dipilih</h3>
        </div>
    @endif

    <div class="footer">
        <p>Dicetak pada: {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y \p\u\k\u\l H:i') }} WIB</p>
        <p>{{ config('app.name') }} - Sistem Akuntansi Tirta Perwira</p>
    </div>
</body>
</html>
