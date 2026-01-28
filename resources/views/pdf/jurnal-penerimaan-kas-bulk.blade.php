<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Jurnal Penerimaan Kas - Batch</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 11px;
            margin-bottom: 10px;
            color: #666;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
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
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
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

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-primary {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .page-break {
            page-break-before: always;
        }

        @page {
            margin-bottom: 60px;
            @bottom-center {
                content: "Halaman " counter(page) " dari " counter(pages);
                font-size: 9px;
                color: #666;
                font-family: Arial, sans-serif;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Perumdam Tirta Perwira</div>
        <div class="company-name" style="font-size: 14px; margin-bottom: 10px;">Kabupaten Purbalingga</div>
        <div class="company-address">
            Jl. Veteran No. 53 Purbalingga | Telp: (0281) 891292
        </div>
        <div class="report-title">Laporan Jurnal Penerimaan Kas</div>
        <div style="font-size: 11px; color: #666; margin-top: 5px;">
            Periode: {{ now()->format('d/m/Y') }} | Total Data: {{ $records->count() }} transaksi
        </div>
    </div>

    <div class="summary-info">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; font-weight: bold; width: 150px;">Tanggal Cetak:</td>
                <td style="border: none;">{{ now()->format('d/m/Y H:i') }}</td>
                <td style="border: none; font-weight: bold; width: 150px;">Total Transaksi:</td>
                <td style="border: none;">{{ $records->count() }} transaksi</td>
            </tr>
            <tr style="border: none;">
                <td style="border: none; font-weight: bold;">Dicetak Oleh:</td>
                <td style="border: none;">{{ auth()->user()->name ?? 'System' }}</td>
                <td style="border: none; font-weight: bold;">Total Nilai:</td>
                <td style="border: none; font-weight: bold; color: #007bff;">
                    @php
                        $grandTotal = $records->sum(function($record) {
                            return $record->details->sum('jumlah');
                        });
                    @endphp
                    Rp {{ number_format($grandTotal, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    @if($records->count() == 0)
    <div style="border: 2px solid #dc3545; background-color: #f8d7da; color: #721c24; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;">
        <h3 style="margin-bottom: 15px; color: #721c24;">⚠️ DATA TIDAK DITEMUKAN</h3>
        <p style="margin-bottom: 10px; font-size: 12px;">
            <strong>Tidak ada data jurnal penerimaan kas yang sesuai dengan kriteria pencarian.</strong>
        </p>
    </div>
    @else
    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 12%">Tanggal</th>
                <th style="width: 12%">No. Bukti</th>
                <th style="width: 25%">Keterangan</th>
                <th style="width: 20%">Rekening</th>
                <th style="width: 8%">Proyek</th>
                <th style="width: 10%">Debit</th>
                <th style="width: 10%">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalDebit = 0;
                $totalKredit = 0;
                $no = 1;
            @endphp
            
            @foreach($records as $index => $record)
                @php
                    $recordTotal = $record->details->sum('jumlah');
                @endphp
                
                {{-- Header Jurnal --}}
                <tr style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="8" style="padding: 8px 10px;">
                        <strong>Jurnal #{{ $index + 1 }}</strong> - 
                        {{ $record->tanggal ? $record->tanggal->format('d/m/Y') : '-' }} | 
                        {{ $record->nomor_bukti ?? '-' }} | 
                        No. Reff: {{ $record->no_reff ?? '-' }} |
                        @if($record->is_posted)
                            <span class="badge" style="background-color: #d4edda; color: #155724;">POSTED</span>
                        @elseif($record->is_confirmed)
                            <span class="badge" style="background-color: #d1ecf1; color: #0c5460;">CONFIRMED</span>
                        @else
                            <span class="badge" style="background-color: #fff3cd; color: #856404;">DRAFT</span>
                        @endif
                    </td>
                </tr>
                
                {{-- Entry Debit (Kas/Bank) --}}
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center">{{ $record->tanggal ? $record->tanggal->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $record->nomor_bukti ?? '-' }}</td>
                    <td>
                        <strong>{{ $record->kasBank->nm_bantu ?? 'N/A' }}</strong>
                        @if($record->keterangan)
                            <br><small style="color: #666; font-style: italic;">{{ Str::limit($record->keterangan, 80) }}</small>
                        @endif
                    </td>
                    <td style="font-size: 10px;">
                        @if($record->kasBank && $record->kasBank->rekening)
                            {{ $record->kasBank->rekening->kelompok->no_kel ?? '' }}-{{ $record->kasBank->rekening->no_rek ?? '' }}-{{ $record->kasBank->no_bantu ?? '' }}
                            <br><small>{{ $record->kasBank->rekening->nama_rek ?? '' }}</small>
                        @endif
                    </td>
                    <td class="text-center">-</td>
                    <td class="amount" style="font-weight: bold;">
                        @php $totalDebit += $recordTotal; @endphp
                        {{ number_format($recordTotal, 0, ',', '.') }}
                    </td>
                    <td class="amount">-</td>
                </tr>
                
                {{-- Detail Items (Kredit) --}}
                @if($record->details && $record->details->count() > 0)
                    @foreach($record->details as $detail)
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td class="text-center">{{ $record->tanggal ? $record->tanggal->format('d/m/Y') : '-' }}</td>
                            <td class="text-center">{{ $detail->nomor_bukti ?? '-' }}</td>
                            <td>
                                @if($detail->rekening)
                                    <strong>{{ $detail->rekening->nama_rek }}</strong>
                                @endif
                                @if($detail->nomorBantu)
                                    <br><small style="color: #666;">{{ $detail->nomorBantu->nm_bantu }}</small>
                                @endif
                                @if($detail->keterangan_item)
                                    <br><small style="color: #888; font-style: italic;">{{ Str::limit($detail->keterangan_item, 60) }}</small>
                                @endif
                            </td>
                            <td style="font-size: 10px;">
                                @if($detail->rekening)
                                    {{ $detail->rekening->kelompok->no_kel ?? '' }}-{{ $detail->rekening->no_rek ?? '' }}
                                    @if($detail->nomorBantu)
                                        -{{ $detail->nomorBantu->no_bantu }}
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center" style="font-size: 10px;">
                                {{ $detail->kodeProyek ? $detail->kodeProyek->kode : '-' }}
                            </td>
                            <td class="amount">-</td>
                            <td class="amount" style="font-weight: bold;">
                                @php $totalKredit += $detail->jumlah; @endphp
                                {{ number_format($detail->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                @endif
                
                {{-- Subtotal per Jurnal --}}
                <tr style="background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #333;">
                    <td colspan="6" class="text-right" style="padding-right: 10px;">
                        <strong>Subtotal Jurnal #{{ $index + 1 }}:</strong>
                    </td>
                    <td class="amount" style="color: #007bff;">{{ number_format($recordTotal, 0, ',', '.') }}</td>
                    <td class="amount" style="color: #dc3545;">{{ number_format($recordTotal, 0, ',', '.') }}</td>
                </tr>
                
                {{-- Spacing antar jurnal --}}
                @if($index < $records->count() - 1)
                <tr style="height: 10px; border: none;">
                    <td colspan="8" style="border: none; background-color: #fff;"></td>
                </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #333; color: white; font-weight: bold; font-size: 12px;">
                <td colspan="6" class="text-right" style="padding: 12px;"><strong>TOTAL KESELURUHAN:</strong></td>
                <td class="amount" style="color: #fff;">
                    <strong>{{ number_format($totalDebit, 0, ',', '.') }}</strong>
                </td>
                <td class="amount" style="color: #fff;">
                    <strong>{{ number_format($totalKredit, 0, ',', '.') }}</strong>
                </td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td colspan="6" class="text-right" style="padding: 10px;"><strong>BALANCE CHECK:</strong></td>
                <td colspan="2" class="text-center">
                    @php $balance = $totalDebit - $totalKredit; @endphp
                    @if($balance == 0)
                        <span class="badge" style="background-color: #d4edda; color: #155724; padding: 5px 10px;">✓ BALANCED</span>
                    @else
                        <span style="color: red; font-weight: bold;">SELISIH: Rp {{ number_format(abs($balance), 0, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Summary by Month (if there are records) -->
    @if($records->count() > 0)
    <div style="margin-top: 30px; border-top: 2px solid #333; padding-top: 20px;">
        <h3 style="margin-bottom: 15px;">Ringkasan per Bulan</h3>
        @php
            $monthlyData = $records->groupBy(function($record) {
                return $record->tanggal->format('Y-m');
            });
        @endphp
        <table style="width: 60%;">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyData as $month => $monthRecords)
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</td>
                    <td class="text-center">{{ $monthRecords->count() }} transaksi</td>
                    <td class="amount">
                        @php
                            $monthTotal = $monthRecords->sum(function($record) {
                                return $record->details->sum('jumlah');
                            });
                        @endphp
                        {{ number_format($monthTotal, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer Professional -->
    <div style="position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #333; padding: 10px 15px; background-color: white; font-size: 9px;">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: left; color: #666; width: 50%;">
                    <strong>Perumdam Tirta Perwira Purbalingga</strong><br>
                    Dokumen ini digenerate secara otomatis
                </td>
                <td style="border: none; text-align: right; color: #666; width: 50%;">
                    Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
                    Oleh: {{ auth()->user()->name ?? 'System' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
