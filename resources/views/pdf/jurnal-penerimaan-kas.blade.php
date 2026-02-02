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
                <td><strong>{{ $record->nomor_bukti ?? '-' }}</strong></td>
                <td class="label">Tanggal:</td>
                <td><strong>{{ $record->tanggal ? $record->tanggal->format('d/m/Y') : '-' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Kas/Bank:</td>
                <td>
                    <strong>{{ $record->kasBank->nm_bantu ?? '-' }}</strong><br>
                    <small style="color: #666;">
                        {{ $record->kasBank->rekening->kelompok->no_kel ?? '' }}-{{ $record->kasBank->rekening->no_rek ?? '' }}-{{ $record->kasBank->no_bantu ?? '' }}
                    </small>
                </td>
                <td class="label">Referensi:</td>
                <td><span class="badge badge-debit">{{ $record->no_reff ?? '-' }}</span></td>
            </tr>
            <tr>
                <td class="label">Keterangan:</td>
                <td colspan="3">{{ $record->keterangan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td>
                    @if($record->is_confirmed)
                        <span class="badge badge-debit">✓ Dikonfirmasi</span>
                        @if($record->confirmed_by)
                            <small style="display: block; margin-top: 5px;">
                                oleh {{ $record->confirmedBy->name ?? '-' }}<br>
                                pada {{ $record->confirmed_at ? $record->confirmed_at->format('d/m/Y H:i') : '-' }}
                            </small>
                        @endif
                    @else
                        <span class="badge" style="background-color: #fff3cd; color: #856404;">⏳ Belum Konfirmasi</span>
                    @endif
                </td>
                <td class="label">Posting:</td>
                <td>
                    @if($record->is_posted)
                        <span class="badge badge-debit">✓ Sudah Posting</span>
                        @if($record->posted_at)
                            <small style="display: block; margin-top: 5px;">
                                pada {{ $record->posted_at->format('d/m/Y H:i') }}
                            </small>
                        @endif
                    @else
                        <span class="badge" style="background-color: #d1ecf1; color: #0c5460;">Belum Posting</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="transaction-detail">
        <h3 style="margin-bottom: 15px; color: #333;">Detail Transaksi</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%">No</th>
                    <th style="width: 15%">No. Bukti</th>
                    <th style="width: 30%">Keterangan</th>
                    <th style="width: 15%">Debit (Rp)</th>
                    <th style="width: 15%">Kredit (Rp)</th>
                    <th style="width: 17%">Kode Akun</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalDebit = 0;
                    $totalKredit = 0;
                    // Relasi sudah di-load dari controller, tidak perlu load lagi
                    $totalPenerimaan = $record->details->sum('jumlah');
                @endphp
                
                <!-- Entry Debit (Kas/Bank) -->
                <tr>
                    <td class="text-center">1</td>
                    <td class="text-center">
                        <strong>{{ $record->nomor_bukti ?? '-' }}</strong>
                    </td>
                    <td>
                        <strong>{{ $record->kasBank->nm_bantu ?? '-' }}</strong><br>
                        <small style="color: #666;">{{ $record->kasBank->rekening->nama_rek ?? '-' }}</small><br>
                        <small style="color: #888; font-style: italic;">{{ $record->keterangan ?? '-' }}</small>
                    </td>
                    <td class="amount">
                        @php $totalDebit += $totalPenerimaan; @endphp
                        {{ number_format($totalPenerimaan, 0, ',', '.') }}
                    </td>
                    <td class="amount">-</td>
                    <td class="text-center">
                        {{ $record->kasBank->rekening->kelompok->no_kel ?? '' }}-{{ $record->kasBank->rekening->no_rek ?? '' }}-{{ $record->kasBank->no_bantu ?? '' }}
                    </td>
                </tr>

                <!-- Entry Kredit dari Detail Penerimaan -->
                @if($record->details && $record->details->count() > 0)
                    @foreach($record->details as $index => $detail)
                        @php
                            $totalKredit += $detail->jumlah;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 2 }}</td>
                            <td class="text-center">{{ $detail->nomor_bukti ?? '-' }}</td>
                            <td>
                                @if($detail->rekening)
                                    <strong>{{ $detail->rekening->nama_rek }}</strong><br>
                                @endif
                                @if($detail->nomorBantu)
                                    <small style="color: #666;">{{ $detail->nomorBantu->nm_bantu }}</small><br>
                                @endif
                                @if($detail->kodeProyek)
                                    <small style="color: #007bff;">[{{ $detail->kodeProyek->kode }}] {{ $detail->kodeProyek->name }}</small><br>
                                @endif
                                <small style="color: #888; font-style: italic;">{{ $detail->keterangan_item ?? '-' }}</small>
                            </td>
                            <td class="amount">-</td>
                            <td class="amount">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($detail->rekening)
                                    {{ $detail->rekening->kelompok->no_kel ?? '' }}-{{ $detail->rekening->no_rek ?? '' }}
                                    @if($detail->nomorBantu)
                                        -{{ $detail->nomorBantu->no_bantu }}
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="text-center" style="color: #999; padding: 20px;">
                            Tidak ada detail transaksi
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="total-section">
        <table style="width: 50%; margin-left: auto;">
            <tr>
                <td style="border: none; font-weight: bold;">Total Debit:</td>
                <td style="border: none;" class="amount">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="border: none; font-weight: bold;">Total Kredit:</td>
                <td style="border: none;" class="amount">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td style="border: none; font-weight: bold;">Selisih:</td>
                <td style="border: none;" class="amount">
                    @php $selisih = $totalDebit - $totalKredit; @endphp
                    @if($selisih == 0)
                        <span class="badge badge-debit">✓ Balance</span>
                    @else
                        <span style="color: red; font-weight: bold;">Rp {{ number_format(abs($selisih), 0, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="font-weight: bold; margin-bottom: 10px;">Dibuat Oleh</div>
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                @if($record->createdBy)
                    <strong>{{ $record->createdBy->name }}</strong><br>
                    <small style="color: #666;">{{ $record->created_at ? $record->created_at->format('d/m/Y') : '' }}</small>
                @else
                    Staff Akuntansi
                @endif
            </div>
        </div>
        <div class="signature-box">
            <div style="font-weight: bold; margin-bottom: 10px;">Dikonfirmasi Oleh</div>
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                @if($record->is_confirmed && $record->confirmedBy)
                    <strong>{{ $record->confirmedBy->name }}</strong><br>
                    <small style="color: #666;">{{ $record->confirmed_at ? $record->confirmed_at->format('d/m/Y') : '' }}</small>
                @else
                    Supervisor
                @endif
            </div>
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
