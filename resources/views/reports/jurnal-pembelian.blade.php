<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Jurnal Pembelian Barang</title>
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
            background-color: #e3f2fd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .summary-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary-amount {
            font-size: 16px;
            font-weight: bold;
            color: #1976d2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-nowrap { white-space: nowrap; }

        .amount {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        .status-confirmed {
            color: #4caf50;
            font-weight: bold;
        }

        .status-pending {
            color: #ff9800;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: right;
            color: #666;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .page-break {
            page-break-after: always;
        }

        .item-detail {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">SISTEM AKUNTANSI AIR MINUM - SAKEP</div>
        <div class="report-title">LAPORAN JURNAL PEMBELIAN BARANG</div>
        <div class="report-period">Periode: {{ $period }}</div>
    </div>

    <!-- Filter Info -->
    <div class="filter-info">
        <strong>Filter Laporan:</strong><br>
        Periode: {{ $period }}<br>
        @if(!empty($filters['kode_hutang']))
            Akun Hutang: {{ $data->first()?->nomorBantuKredit?->nm_bantu ?? 'N/A' }}<br>
        @endif
        Status: {{ $filters['status'] === 'all' ? 'Semua' : ($filters['status'] === 'confirmed' ? 'Dikonfirmasi' : 'Belum Konfirmasi') }}<br>
        Total Transaksi: {{ $data->count() }} transaksi
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-title">TOTAL NILAI PEMBELIAN</div>
        <div class="summary-amount">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
    </div>

    @if($data->count() > 0)
        <!-- Data Table -->
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="10%">No. Ref</th>
                    <th width="10%">Tanggal</th>
                    <th width="10%">Bukti</th>
                    <th width="15%">Akun Hutang</th>
                    <th width="25%">Detail Pembelian</th>
                    <th width="12%">Total</th>
                    <th width="8%">Status</th>
                    <th width="5%">Proyek</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $jurnal)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $jurnal->no_reff }}</td>
                    <td class="text-center">{{ $jurnal->tanggal->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $jurnal->bukti ?: '-' }}</td>
                    <td>
                        <strong>[{{ $jurnal->kode_sakep_kredit }}]</strong><br>
                        {{ $jurnal->nama_akun_kredit }}
                    </td>
                    <td>
                        @if($jurnal->pembelian_items)
                            @foreach($jurnal->pembelian_items_with_details as $item)
                                <div style="margin-bottom: 8px; padding: 5px; border: 1px solid #eee; background: #fafafa;">
                                    <strong>{{ $item['keterangan'] ?? 'Item pembelian' }}</strong><br>
                                    <small>[{{ $item['kode_sakep_debit'] ?? '-' }}] {{ $item['nama_akun_debit'] ?? '-' }}</small><br>
                                    <div class="amount text-right">Rp {{ number_format($item['jumlah'] ?? 0, 0, ',', '.') }}</div>
                                </div>
                            @endforeach
                        @else
                            <em>Tidak ada detail</em>
                        @endif

                        @if($jurnal->keterangan)
                            <div class="item-detail">
                                <strong>Keterangan:</strong> {{ $jurnal->keterangan }}
                            </div>
                        @endif
                    </td>
                    <td class="text-right amount">Rp {{ number_format($jurnal->rp, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if($jurnal->is_confirmed)
                            <span class="status-confirmed">✓ Confirmed</span>
                        @else
                            <span class="status-pending">⏱ Pending</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $jurnal->kodeProyek?->name ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="6" class="text-right"><strong>TOTAL KESELURUHAN:</strong></td>
                    <td class="text-right amount">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>Tidak Ada Data</h3>
            <p>Tidak ditemukan transaksi jurnal pembelian pada periode dan filter yang dipilih.</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>Laporan digenerate pada: {{ $generatedAt }}</div>
        <div>Sistem Akuntansi Air Minum berbasis SAKEP</div>
    </div>
</body>
</html>
