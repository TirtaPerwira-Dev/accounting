<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 10pt;
            color: #666;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f0f0f0;
            border-left: 4px solid #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th,
        table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: bold;
        }
        
        .total-row {
            background-color: #f9fafb;
            font-weight: bold;
        }
        
        .grand-total {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 12pt;
        }
        
        .grid-2 {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .grid-item {
            display: table-cell;
            width: 48%;
            padding: 10px;
            vertical-align: top;
        }
        
        .grid-item:first-child {
            padding-right: 20px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
        
        .positive {
            color: #059669;
        }
        
        .negative {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $reportData['title'] ?? 'LAPORAN KEUANGAN' }}</h1>
        <p>{{ $reportData['periode'] ?? '' }}</p>
    </div>

    @if($reportType === 'neraca')
        <div class="grid-2">
            <div class="grid-item">
                <div class="section-title">AKTIVA</div>
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Akun</th>
                            <th class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['aktiva'] ?? [] as $item)
                        <tr>
                            <td>{{ $item['kode'] }}</td>
                            <td>{{ $item['nama'] }}</td>
                            <td class="text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="grand-total">
                            <td colspan="2">TOTAL AKTIVA</td>
                            <td class="text-right">Rp {{ number_format($reportData['total_aktiva'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="grid-item">
                <div class="section-title">PASIVA</div>
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Akun</th>
                            <th class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['pasiva'] ?? [] as $item)
                        <tr>
                            <td>{{ $item['kode'] }}</td>
                            <td>{{ $item['nama'] }}</td>
                            <td class="text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="grand-total">
                            <td colspan="2">TOTAL PASIVA</td>
                            <td class="text-right">Rp {{ number_format($reportData['total_pasiva'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($reportType === 'laba_rugi')
        <div class="section-title">PENDAPATAN</div>
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Akun</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['pendapatan'] ?? [] as $item)
                <tr>
                    <td>{{ $item['kode'] }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total Pendapatan</td>
                    <td class="text-right">Rp {{ number_format($reportData['total_pendapatan'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">BEBAN</div>
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Akun</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['beban'] ?? [] as $item)
                <tr>
                    <td>{{ $item['kode'] }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total Beban</td>
                    <td class="text-right">Rp {{ number_format($reportData['total_beban'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table>
            <tr class="grand-total">
                <td style="width: 70%">{{ $reportData['status'] ?? 'LABA/RUGI BERSIH' }}</td>
                <td class="text-right {{ ($reportData['laba_rugi'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                    Rp {{ number_format(abs($reportData['laba_rugi'] ?? 0), 0, ',', '.') }}
                </td>
            </tr>
        </table>

    @elseif($reportType === 'trial_balance')
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Rekening</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['data'] ?? [] as $item)
                <tr>
                    <td>{{ $item['kode'] }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td class="text-right">
                        @if($item['debit'] > 0)
                            Rp {{ number_format($item['debit'], 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item['kredit'] > 0)
                            Rp {{ number_format($item['kredit'], 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr class="grand-total">
                    <td colspan="2">TOTAL</td>
                    <td class="text-right">Rp {{ number_format($reportData['total_debit'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($reportData['total_kredit'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

    @elseif($reportType === 'buku_besar')
        @foreach($reportData['data'] ?? [] as $rekening)
            <div class="section-title">{{ $rekening['kode'] }} - {{ $rekening['nama'] }}</div>
            <table style="margin-bottom: 30px;">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis Transaksi</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekening['transaksi'] ?? [] as $tr)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($tr['tanggal'])->format('d/m/Y') }}</td>
                        <td>{{ $tr['jenis'] }}</td>
                        <td class="text-right">
                            @if($tr['debit'] > 0)
                                <span class="positive">Rp {{ number_format($tr['debit'], 0, ',', '.') }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">
                            @if($tr['kredit'] > 0)
                                <span class="negative">Rp {{ number_format($tr['kredit'], 0, ',', '.') }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right font-bold">Rp {{ number_format($tr['saldo'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada transaksi</td>
                    </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td class="text-right">Rp {{ number_format($rekening['total_debit'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($rekening['total_kredit'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($rekening['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d F Y H:i:s') }}</p>
        <p>Sistem Akuntansi Air Minum - PDAM</p>
    </div>
</body>
</html>
