<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Jurnal Pembelian Barang</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            margin: 30px;
            line-height: 1.4;
            color: #000;
        }

        .text-center {
            text-align: center;
        }

        .uppercase {
            text-transform: uppercase;
        }

        /* Header rapi & elegan */
        .header-title {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
        }

        .header-period {
            margin: 6px 0 0 0;
            font-size: 12pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 7px;
            vertical-align: top;
        }

        th {
            background-color: #f0f0f0;
            text-align: center;
        }

        .amount {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        /* === INFO JURNAL — TITIK DUA 100% RATA === */
        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 11pt;
        }

        .info-label {
            width: 165px;
            flex-shrink: 0;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
        }

        .total-value {
            font-weight: bold;
            font-family: 'Courier New', monospace;
            font-size: 12pt;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 10pt;
        }

        .status-confirmed {
            background-color: #0b6e04;
        }

        .status-pending {
            background-color: #e65100;
        }

        /* === FOOTER SERAGAM DENGAN LAPORAN SINGLE === */
        .report-footer {
            margin-top: 70px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            position: relative;
            min-height: 300px;
            page-break-inside: avoid;
        }

        .signature-block {
            position: absolute;
            right: 0;
            top: 0;
            width: 380px;
            text-align: center;
        }

        .system-info {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 11pt;
            color: #555;
            padding: 10px 0;
        }
    </style>
</head>

<body>

    <!-- Header Perusahaan -->
    <div class="text-center">
        <h2 class="uppercase" style="margin:0; line-height:1.2;">PEMERINTAH KABUPATEN PURBALINGGA</h2>
        <h2 class="uppercase" style="margin:5px 0 0 0; line-height:1.2;">PERUSAHAAN UMUM DAERAH AIR MINUM TIRTA PERWIRA
        </h2>
        <h2 class="uppercase" style="margin:5px 0 10px 0; line-height:1.2;">KABUPATEN PURBALINGGA</h2>
        <hr style="border: 2px solid #000; margin:8px 0;">
        <p class="header-title">LAPORAN JURNAL PEMBELIAN BARANG</p>
        <p class="header-period">Periode: {{ $period }}</p>
    </div>

    @if($data->count() > 0)
    @php
        // Group data by no_reff and group_transaksi for proper display
        $groupedData = $data->groupBy(function ($item) {
            return $item->group_transaksi ?? 'single_' . $item->id;
        });
    @endphp

    @foreach($groupedData as $groupKey => $groupItems)
    @php
        $jurnal = $groupItems->first(); // Main record
        $totalGroupAmount = $groupItems->sum('jumlah_item');
        $no = $loop->iteration;
    @endphp

    <!-- INFO JURNAL (2 kolom, titik dua rata) -->
    <table width="100%" style="margin:25px 0 10px 0; border:none;">
        <tr>
            <td width="55%" style="vertical-align:top; border:none;">
                <div class="info-row"><span class="info-label">NO. REFERENSI :</span><span class="info-value"><strong>{{
                            $jurnal->no_reff }}</strong></span></div>
                <div class="info-row"><span class="info-label">TANGGAL :</span><span class="info-value">{{
                        $jurnal->tanggal->format('d M Y') }}</span></div>
                <div class="info-row"><span class="info-label">BUKTI :</span><span class="info-value">{{ $jurnal->bukti_item
                        ?: '-' }}</span></div>
                @if($jurnal->keterangan)
                <div class="info-row"><span class="info-label">KETERANGAN :</span><span class="info-value">{{
                        $jurnal->keterangan }}</span></div>
                @endif
                <div class="info-row"><span class="info-label">AKUN KREDIT :</span><span class="info-value">{{
                        $jurnal->kode_sakep_kredit }} - {{ $jurnal->nama_akun_kredit }}</span></div>
                @if($jurnal->kodeProyek)
                <div class="info-row"><span class="info-label">KODE PROYEK :</span><span class="info-value">{{
                        $jurnal->kodeProyek->name }}</span></div>
                @endif
            </td>
            <td width="45%" style="vertical-align:top; border:none;">
                <div class="info-row"><span class="info-label">TOTAL NILAI :</span><span
                        class="info-value total-value">Rp {{ number_format($totalGroupAmount, 0, ',', '.') }}</span></div>
                <div class="info-row"><span class="info-label">STATUS JURNAL :</span><span class="info-value">
                        @if($jurnal->is_confirmed)
                        <span class="status-badge status-confirmed">DIKONFIRMASI</span>
                        @else
                        <span class="status-badge status-pending">PENDING</span>
                        @endif
                    </span></div>
                @if($jurnal->is_confirmed && $jurnal->confirmed_at)
                <div class="info-row"><span class="info-label">WAKTU KONFIRMASI :</span><span class="info-value">{{
                        $jurnal->confirmed_at->format('d/m/Y H:i') }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Tabel Detail Item -->
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Bukti</th>
                <th width="10%">Kode Akun</th>
                <th width="38%">Nama Akun Debit</th>
                <th width="18%">Debit</th>
                <th width="17%">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupItems as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item->bukti_item ?: '-' }}</td>
                <td>{{ $item->kode_sakep_debit }}</td>
                <td style="padding-left:15px;">{{ $item->nama_akun_debit }}</td>
                <td class="amount text-right">Rp {{ number_format($item->jumlah_item, 0, ',', '.') }}</td>
                <td class="amount text-right">{{ $i == 0 ? 'Rp '.number_format($totalGroupAmount, 0, ',', '.') : '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="font-style:italic; color:#666;">— Tidak ada detail item —
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pemisah antar jurnal -->
    <div style="margin:30px 0; page-break-after:avoid;">
        <hr style="border-top:3px double #000;">
    </div>
    @endforeach

    <!-- Total Keseluruhan -->
    @php
        $totalAmount = $data->sum('jumlah_item');
    @endphp
    <table style="margin-top:20px;">
        <tr style="background:#e0e0e0; font-weight:bold; font-size:13pt;">
            <td colspan="4" class="text-right">TOTAL PEMBELIAN PERIODE</td>
            <td class="amount text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
            <td class="amount text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
        </tr>
    </table>
    @else
    <div style="text-align:center; padding:120px 0; color:#666; font-style:italic;">
        <h3>— TIDAK ADA DATA —</h3>
        <p>Tidak ditemukan transaksi pembelian barang pada periode yang dipilih.</p>
    </div>
    @endif

    <!-- FOOTER SERAGAM DENGAN LAPORAN SINGLE -->
    <div class="report-footer">
        <div class="signature-block">
            <div style="margin-bottom:20px;">
                Purbalingga, {{ \Carbon\Carbon::parse($generatedAt)->locale('id')->translatedFormat('d F Y') }}
            </div>
            <div style="margin-bottom:20px;">
                <strong>Perusahaan Umum Daerah Air Minum<br>Kab. Purbalingga</strong>
            </div>
            <div style="margin-bottom:10px;"><strong>Kepala Bagian Keuangan</strong></div>
            <div style="height:80px;"></div>
            <div style="border-bottom:1px solid #000; display:inline-block; padding:0 30px; line-height:2;">
                <strong><u>Yuni Setyowati, S.E</u></strong>
            </div>
            <br>NIPPAM : ....................
        </div>

        <div class="system-info">
            <em>
                Laporan ini dicetak pada : {{ \Carbon\Carbon::parse($generatedAt)->locale('id')->translatedFormat('d F
                Y') }}<br>
                Sistem Akuntansi Air Minum Tirta Perwira
            </em>
        </div>
    </div>

</body>

</html>
