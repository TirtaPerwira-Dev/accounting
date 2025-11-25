@if(isset($data['operating_activities']) || isset($data['investing_activities']) || isset($data['financing_activities']))
<table>
    <thead>
        <tr>
            <th style="width: 70%;">Aktivitas</th>
            <th style="width: 30%;" class="text-right">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- OPERATING ACTIVITIES --}}
        @if(isset($data['operating_activities']) && !empty($data['operating_activities']))
            <tr>
                <td class="section-header" colspan="2">ARUS KAS DARI AKTIVITAS OPERASI</td>
            </tr>
            @foreach($data['operating_activities'] as $item)
                <tr>
                    <td style="padding-left: 20px;">{{ $item['description'] ?? $item['account_name'] ?? 'N/A' }}</td>
                    <td class="amount">
                        {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">
                    Kas Bersih dari Aktivitas Operasi
                </td>
                <td class="amount font-bold">
                    {{ number_format($data['net_operating_cash_flow'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- INVESTING ACTIVITIES --}}
        @if(isset($data['investing_activities']) && !empty($data['investing_activities']))
            <tr>
                <td class="section-header" colspan="2">ARUS KAS DARI AKTIVITAS INVESTASI</td>
            </tr>
            @foreach($data['investing_activities'] as $item)
                <tr>
                    <td style="padding-left: 20px;">{{ $item['description'] ?? $item['account_name'] ?? 'N/A' }}</td>
                    <td class="amount">
                        {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">
                    Kas Bersih dari Aktivitas Investasi
                </td>
                <td class="amount font-bold">
                    {{ number_format($data['net_investing_cash_flow'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- FINANCING ACTIVITIES --}}
        @if(isset($data['financing_activities']) && !empty($data['financing_activities']))
            <tr>
                <td class="section-header" colspan="2">ARUS KAS DARI AKTIVITAS PENDANAAN</td>
            </tr>
            @foreach($data['financing_activities'] as $item)
                <tr>
                    <td style="padding-left: 20px;">{{ $item['description'] ?? $item['account_name'] ?? 'N/A' }}</td>
                    <td class="amount">
                        {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">
                    Kas Bersih dari Aktivitas Pendanaan
                </td>
                <td class="amount font-bold">
                    {{ number_format($data['net_financing_cash_flow'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- NET CASH CHANGE --}}
        <tr style="border-top: 3px solid #333;">
            <td class="font-bold">Kenaikan (Penurunan) Kas Bersih</td>
            <td class="amount font-bold">
                {{ number_format($data['net_change_in_cash'] ?? 0, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td class="font-bold">Kas Awal Periode</td>
            <td class="amount font-bold">
                {{ number_format($data['beginning_cash_balance'] ?? 0, 0, ',', '.') }}
            </td>
        </tr>

        <tr style="border-top: 2px solid #333;">
            <td class="font-bold">Kas Akhir Periode</td>
            <td class="amount font-bold">
                {{ number_format($data['ending_cash_balance'] ?? $data['calculated_ending_cash'] ?? 0, 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>

@else
<p>Data laporan arus kas tidak tersedia atau format tidak sesuai.</p>
@endif
