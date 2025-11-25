<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
            font-size: 10px;
            margin-bottom: 10px;
            color: #666;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .report-subtitle {
            font-size: 11px;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 10px;
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

        td {
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .nomor-akuntansi {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            background-color: #f0f8ff;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-debet {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-kredit {
            background-color: #f8d7da;
            color: #721c24;
        }

        .page-break {
            page-break-before: always;
        }

        /* CSS untuk pagination otomatis */
        @page {
            margin-bottom: 60px;
            @bottom-center {
                content: "Halaman " counter(page) " dari " counter(pages);
                font-size: 9px;
                color: #666;
                font-family: Arial, sans-serif;
            }
        }

        /* Hide manual footer pagination */
        .manual-pagination {
            display: none;
        }

    </style>
</head>
<body>
    <div class="header">
        @if($company)
        <div class="company-name">{{ $company->name }}</div>
        <div class="company-name" style="font-size: 14px; margin-bottom: 10px;">Kabupaten Purbalingga</div>
        <div class="company-address">
            @if($company->address)
                {{ $company->address }}
            @endif
            @if($company->phone)
                | Telp: {{ $company->phone }}
            @endif
            @if($company->email)
            @endif
        </div>
        @endif

    </div>

    <div class="report-subtitle">
        <div class="report-title">{{ $title }}</div>
        Sistem Akuntansi Air Minum Tirta Perwira
    </div>

    <!-- Meta Information -->
    <div style="margin-bottom: 20px; font-size: 10px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <div style="margin-bottom: 10px;">
            <strong>Tanggal Cetak:</strong> {{ $generated_at }} &nbsp;&nbsp;&nbsp;
            <strong>Dicetak Oleh:</strong> {{ $generated_by }}
        </div>

        @if(isset($filters) && ($filters['kelompok_id'] || $filters['rekening_id'] || $filters['kode'] || $filters['kel']))
        <div style="margin-top: 10px; padding: 8px; background-color: #f8f9fa; border-left: 3px solid #007bff;">
            <strong>Filter Diterapkan:</strong>
            @if($filters['kelompok_id'])
                • Kelompok: {{ $filters['kelompok_name'] }}
            @endif
            @if($filters['rekening_id'])
                • Rekening: {{ $filters['rekening_name'] }}
            @endif
            @if($filters['kode'])
                • Saldo Normal: {{ $filters['kode'] === 'D' ? 'Debet' : 'Kredit' }}
            @endif
            @if($filters['kel'])
                • Kategori: {{
                    match($filters['kel']) {
                        '1' => 'Aktiva',
                        '2' => 'Kewajiban',
                        '3' => 'Pendapatan',
                        '4' => 'Biaya Operasional',
                        '5' => 'Biaya Administrasi',
                        '6' => 'Biaya Luar Usaha',
                        default => $filters['kel']
                    }
                }}
            @endif
        </div>
        @endif
    </div>

    @if($records->count() == 0)
    <!-- Peringatan Data Kosong -->
    <div style="border: 2px solid #dc3545; background-color: #f8d7da; color: #721c24; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;">
        <h3 style="margin-bottom: 15px; color: #721c24;">⚠️ DATA TIDAK DITEMUKAN</h3>
        <p style="margin-bottom: 10px; font-size: 12px;">
            <strong>Tidak ada data nomor bantu yang sesuai dengan kriteria pencarian.</strong>
        </p>
        @if(isset($filters) && ($filters['kelompok_id'] || $filters['rekening_id'] || $filters['kode'] || $filters['kel']))
        <p style="font-size: 11px; color: #666;">
            Silakan periksa kembali filter yang diterapkan atau coba dengan kriteria pencarian yang berbeda.
        </p>
        @else
        <p style="font-size: 11px; color: #666;">
            Belum ada data nomor bantu yang tersimpan dalam sistem.
        </p>
        @endif
    </div>
    @else
    <!-- Tabel Data Normal -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 20%">Kelompok</th>
                <th style="width: 20%">Nomor Akuntansi</th>
                <th style="width: 30%">Nama Akun</th>
                <th style="width: 15%">Saldo Normal</th>
                <th style="width: 10%">Kategori</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $index => $record)
            @if($index > 0 && $index % 25 == 0)
            <!-- Page break setiap 25 baris -->
            <tr class="page-break">
                <td colspan="6" style="border: none; height: 0; padding: 0;"></td>
            </tr>
            @endif
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $record->rekening->kelompok->nama_kel }}</strong><br>
                    <small style="color: #666;">{{ $record->rekening->kelompok->no_kel }}</small>
                </td>
                <td class="nomor-akuntansi text-center">
                    @php
                        $noKel = str_pad($record->rekening->kelompok->no_kel, 2, '0', STR_PAD_LEFT);
                        $noRek = str_pad($record->rekening->no_rek, 4, '0', STR_PAD_LEFT);
                        $noBantu = str_pad($record->no_bantu, 2, '0', STR_PAD_LEFT);
                        $nomorAkuntansi = $noKel . '.' . $noRek . '.' . $noBantu;
                    @endphp
                    {{ $nomorAkuntansi }}<br>
                    <small style="color: #666;">
                        K:{{ $record->rekening->kelompok->no_kel }} |
                        R:{{ $record->rekening->no_rek }} |
                        B:{{ $record->no_bantu }}
                    </small>
                </td>
                <td>
                    <strong>{{ $record->nm_bantu }}</strong><br>
                    <small style="color: #666;">{{ $record->rekening->nama_rek }}</small>
                </td>
                <td class="text-center">
                    <span class="badge {{ $record->kode === 'D' ? 'badge-debet' : 'badge-kredit' }}">
                        {{ $record->kode === 'D' ? 'Debet' : 'Kredit' }}
                    </span>
                </td>
                <td class="text-center">
                    @php
                        $kategori = match($record->kel) {
                            '1' => 'Aktiva',
                            '2' => 'Kewajiban',
                            '3' => 'Pendapatan',
                            '4' => 'Biaya Ops',
                            '5' => 'Biaya Adm',
                            '6' => 'Biaya Luar',
                            default => $record->kel,
                        };
                    @endphp
                    {{ $kategori }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
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
                    Oleh: {{ $generated_by }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
