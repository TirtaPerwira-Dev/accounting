<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - {{ $company->name ?? 'PDAM' }}</title>
    <style>
        @page {
            margin: 1cm;
            size: A4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 10px;
        }
        .company-header {
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .company-location {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .company-address {
            font-size: 9px;
            color: #333;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        .header-line {
            border-bottom: 1px solid #000;
            margin: 10px 0;
            width: 100%;
            height: 1px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            margin-top: 10px;
        }
        .report-date {
            font-size: 11px;
            color: #000;
            margin-bottom: 8px;
        }
        .content {
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
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
        .footer {
            margin-top: 20px;
            font-size: 8px;
            color: #666;
            text-align: center;
            page-break-inside: avoid;
        }
        .section-header {
            background-color: #e0e0e0;
            font-weight: bold;
            padding: 6px 8px;
            font-size: 10px;
        }
        .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9px;
        }
        .page-break {
            page-break-before: always;
        }
        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-header">
            <div class="company-name">Perusahaan Umum Daerah Air Minum Tirta Perwira</div>
            <div class="company-location">Kabupaten Purbalingga</div>
            <div class="company-address">
                Jl. Letnan Jenderal S Parman No.62, Kedung Menjangan, Bancar,<br>
                Kec. Purbalingga, Kabupaten Purbalingga, Jawa Tengah 53316
            </div>
        </div>

        <div class="header-line"></div>

        <div class="report-title">{{ $reportTitle }}</div>
        <div class="report-date">Tanggal: {{ $period }}</div>
    </div>

    <div class="content">
        @switch($reportType)
            @case('trial_balance')
                @include('filament.reports.pdf.sections.trial-balance', ['data' => $reportData])
                @break

            @case('balance_sheet')
                @include('filament.reports.pdf.sections.balance-sheet', ['data' => $reportData])
                @break

            @case('income_statement')
                @include('filament.reports.pdf.sections.income-statement', ['data' => $reportData])
                @break

            @case('cash_flow')
                @include('filament.reports.pdf.sections.cash-flow', ['data' => $reportData])
                @break

            @case('general_ledger')
                @include('filament.reports.pdf.sections.general-ledger', ['data' => $reportData])
                @break

            @default
                <p>Laporan tidak tersedia</p>
        @endswitch
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ $generatedAt }}</p>
        <p>Sistem Akuntansi Air Minum Tirta Perwira</p>
    </div>
</body>
</html>
