<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Penerimaan Kas - {{ $record->nomor_bukti }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 12px;
            margin-bottom: 10px;
            color: #666;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .voucher-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .voucher-info table {
            width: 100%;
            border: none;
        }

        .voucher-info td {
            border: none;
            padding: 5px;
            vertical-align: top;
        }

        .voucher-info .label {
            font-weight: bold;
            width: 150px;
        }

        .transaction-detail {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
            text-transform: uppercase;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .amount {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            text-align: right;
        }

        .total-section {
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 20px;
        }

        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
            vertical-align: top;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            margin: 40px 20px 5px 20px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-debit {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-credit {
            background-color: #f8d7da;
            color: #721c24;
        }

        @page {
            margin: 25mm;
            @bottom-center {
                content: "Halaman " counter(page);
                font-size: 10px;
                color: #666;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Perumdam Tirta Perwira</div>
        <div class="company-name" style="font-size: 16px; margin-bottom: 10px;">Kabupaten Purbalingga</div>
        <div class="company-address">
            Jl. Veteran No. 53 Purbalingga | Telp: (0281) 891292
        </div>
        <div class="report-title">Jurnal Penerimaan Kas</div>
    </div>

    <div class="voucher-info">
        <table>
            <tr>
                <td class="label">Nomor Bukti:</td>
                <td><strong>{{ $record->nomor_bukti }}</strong></td>
                <td class="label">Tanggal:</td>
                <td><strong>{{ $record->formatted_tanggal }}</strong></td>
            </tr>
            <tr>
                <td class="label">Kas/Bank:</td>
                <td>
                    <strong>{{ $record->kasBank->nm_bantu }}</strong><br>
                    <small style="color: #666;">
                        {{ $record->kasBank->rekening->kelompok->no_kel }}-{{ $record->kasBank->rekening->no_rek }}-{{ $record->kasBank->no_bantu }}
                    </small>
                </td>
                <td class="label">Referensi:</td>
                <td><span class="badge badge-debit">{{ $record->reff }}</span></td>
            </tr>
            <tr>
                <td class="label">Keterangan:</td>
                <td colspan="3">{{ $record->keterangan }}</td>
            </tr>
        </table>
    </div>

    <div class="transaction-detail">
        <h3 style="margin-bottom: 15px; color: #333;">Detail Transaksi</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%">No</th>
                    <th style="width: 35%">Keterangan</th>
                    <th style="width: 15%">Debit (Rp)</th>
                    <th style="width: 15%">Kredit (Rp)</th>
                    <th style="width: 17%">Kode Akun</th>
                    <th style="width: 10%">Proyek</th>
                </tr>
            </thead>
            <tbody>
                <!-- Entry Debit (Kas/Bank) -->
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <strong>{{ $record->kasBank->nm_bantu }}</strong><br>
                        <small style="color: #666;">{{ $record->kasBank->rekening->nama_rek }}</small>
                    </td>
                    <td class="amount">
                        @php 
                            $totalPenerimaan = collect($record->detail_penerimaan ?? [])->sum('jumlah');
                        @endphp
                        {{ number_format($totalPenerimaan, 0, ',', '.') }}
                    </td>
                    <td class="amount">-</td>
                    <td class="text-center">
                        {{ $record->kasBank->rekening->kelompok->no_kel }}-{{ $record->kasBank->rekening->no_rek }}-{{ $record->kasBank->no_bantu }}
                    </td>
                    <td class="text-center">-</td>
                </tr>
                
                <!-- Entry Kredit dari Detail Penerimaan -->
                @if($record->detail_penerimaan && count($record->detail_penerimaan) > 0)
                    @php $totalKredit = 0; @endphp
                    @foreach($record->detail_penerimaan as $index => $item)
                        @php
                            $rekening = \App\Models\Rekening::with('kelompok')->find($item['rekening'] ?? null);
                            $nomorBantu = \App\Models\NomorBantu::find($item['nomor_bantu'] ?? null);
                            $kodeProyek = \App\Models\KodeProyek::find($item['kode_proyek'] ?? null);
                            $jumlah = $item['jumlah'] ?? 0;
                            $totalKredit += $jumlah;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 2 }}</td>
                            <td>
                                @if($rekening)
                                    <strong>{{ $rekening->nama_rek }}</strong><br>
                                @endif
                                @if($nomorBantu)
                                    <small style="color: #666;">{{ $nomorBantu->nm_bantu }}</small><br>
                                @endif
                                <small style="color: #888;">{{ $item['keterangan_item'] ?? '-' }}</small>
                            </td>
                            <td class="amount">-</td>
                            <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($rekening)
                                    {{ $rekening->kelompok->no_kel }}-{{ $rekening->no_rek }}
                                    @if($nomorBantu)
                                        -{{ $nomorBantu->no_bantu }}
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $kodeProyek->kode ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="total-section">
        <table style="width: 50%; margin-left: auto;">
            @php 
                $totalPenerimaan = collect($record->detail_penerimaan ?? [])->sum('jumlah');
            @endphp
            <tr>
                <td style="border: none; font-weight: bold;">Total Debit:</td>
                <td style="border: none;" class="amount">Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="border: none; font-weight: bold;">Total Kredit:</td>
                <td style="border: none;" class="amount">Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td style="border: none; font-weight: bold;">Balance:</td>
                <td style="border: none;" class="amount">
                    <span class="badge badge-debit">✓ Balance</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="font-weight: bold; margin-bottom: 10px;">Dibuat Oleh</div>
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">Staff Akuntansi</div>
        </div>
        <div class="signature-box">
            <div style="font-weight: bold; margin-bottom: 10px;">Diperiksa Oleh</div>
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">Supervisor</div>
        </div>
        <div class="signature-box">
            <div style="font-weight: bold; margin-bottom: 10px;">Disetujui Oleh</div>
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">Manager Keuangan</div>
        </div>
    </div>

    <!-- Footer -->
    <div style="position: fixed; bottom: 15mm; left: 0; right: 0; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #666;">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: left;">
                    <strong>Perumdam Tirta Perwira Purbalingga</strong><br>
                    Dokumen ini digenerate secara otomatis
                </td>
                <td style="border: none; text-align: right;">
                    Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
                    User: {{ auth()->user()->name ?? 'System' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>