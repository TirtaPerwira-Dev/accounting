@if(isset($data['revenues']) || isset($data['expenses']) || isset($data['net_income']))
<table>
    <thead>
        <tr>
            <th style="width: 70%;">Akun</th>
            <th style="width: 30%;" class="text-right">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- REVENUE SECTION --}}
        @if(isset($data['revenues']) && !empty($data['revenues']))
            <tr>
                <td class="section-header" colspan="2">PENDAPATAN</td>
            </tr>
            @foreach($data['revenues'] as $account)
                <tr>
                    <td style="padding-left: 20px;">
                        {{ $account['sakep_code'] ?? $account['code'] ?? '' }} - {{ $account['account_name'] ?? $account['name'] ?? 'N/A' }}
                    </td>
                    <td class="amount">
                        {{ number_format(abs($account['amount'] ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">TOTAL PENDAPATAN</td>
                <td class="amount font-bold">
                    {{ number_format($data['total_revenues'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- EXPENSES SECTION --}}
        @if(isset($data['expenses']) && !empty($data['expenses']))
            <tr>
                <td class="section-header" colspan="2">BEBAN</td>
            </tr>
            @foreach($data['expenses'] as $account)
                <tr>
                    <td style="padding-left: 20px;">
                        {{ $account['sakep_code'] ?? $account['code'] ?? '' }} - {{ $account['account_name'] ?? $account['name'] ?? 'N/A' }}
                    </td>
                    <td class="amount">
                        {{ number_format($account['amount'] ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">TOTAL BEBAN</td>
                <td class="amount font-bold">
                    {{ number_format($data['total_expenses'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- NET INCOME --}}
        <tr style="border-top: 3px solid #333;">
            <td class="font-bold">
                @if(($data['net_income'] ?? 0) >= 0)
                    LABA BERSIH
                @else
                    RUGI BERSIH
                @endif
            </td>
            <td class="amount font-bold" style="color: {{ ($data['net_income'] ?? 0) >= 0 ? 'green' : 'red' }};">
                {{ number_format(abs($data['net_income'] ?? 0), 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>

@else
<p>Data laporan laba rugi tidak tersedia atau format tidak sesuai.</p>
@endif
