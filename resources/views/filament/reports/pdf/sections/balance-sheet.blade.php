@if(isset($data['assets']) || isset($data['liabilities']) || isset($data['equity']))
<table>
    <thead>
        <tr>
            <th style="width: 70%;">Akun</th>
            <th style="width: 30%;" class="text-right">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- ASSETS SECTION --}}
        @if(isset($data['assets']) && !empty($data['assets']))
            <tr>
                <td class="section-header" colspan="2">ASET</td>
            </tr>
            @foreach($data['assets'] as $account)
                <tr>
                    <td style="padding-left: 20px;">
                        {{ $account['sakep_code'] ?? $account['code'] ?? '' }} - {{ $account['name'] ?? 'N/A' }}
                    </td>
                    <td class="amount">
                        {{ number_format($account['balance'] ?? $account['amount'] ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">
                    TOTAL ASET
                </td>
                <td class="amount font-bold">
                    {{ number_format($data['total_assets'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- LIABILITIES SECTION --}}
        @if(isset($data['liabilities']) && !empty($data['liabilities']))
            <tr>
                <td class="section-header" colspan="2">KEWAJIBAN</td>
            </tr>
            @foreach($data['liabilities'] as $account)
                <tr>
                    <td style="padding-left: 20px;">
                        {{ $account['sakep_code'] ?? $account['code'] ?? '' }} - {{ $account['name'] ?? 'N/A' }}
                    </td>
                    <td class="amount">
                        {{ number_format(abs($account['balance'] ?? $account['amount'] ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">
                    TOTAL KEWAJIBAN
                </td>
                <td class="amount font-bold">
                    {{ number_format($data['total_liabilities'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- EQUITY SECTION --}}
        @if(isset($data['equity']) && !empty($data['equity']))
            <tr>
                <td class="section-header" colspan="2">EKUITAS</td>
            </tr>
            @foreach($data['equity'] as $account)
                <tr>
                    <td style="padding-left: 20px;">
                        {{ $account['sakep_code'] ?? $account['code'] ?? '' }} - {{ $account['name'] ?? 'N/A' }}
                    </td>
                    <td class="amount">
                        {{ number_format(abs($account['balance'] ?? $account['amount'] ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="font-bold" style="padding-left: 10px;">
                    TOTAL EKUITAS
                </td>
                <td class="amount font-bold">
                    {{ number_format($data['total_equity'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
        @endif

        {{-- TOTALS --}}
        @if(isset($data['total_assets']) && isset($data['total_liabilities_equity']))
            <tr style="border-top: 2px solid #333;">
                <td class="font-bold">TOTAL ASET</td>
                <td class="amount font-bold">{{ number_format($data['total_assets'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="font-bold">TOTAL KEWAJIBAN & EKUITAS</td>
                <td class="amount font-bold">{{ number_format($data['total_liabilities_equity'], 0, ',', '.') }}</td>
            </tr>
        @endif
    </tbody>
</table>

@if(isset($data['is_balanced']) && $data['is_balanced'])
    <p class="text-center font-bold" style="margin-top: 20px; color: green;">
        ✓ NERACA SEIMBANG (Aset = Kewajiban + Ekuitas)
    </p>
@else
    <p class="text-center font-bold" style="margin-top: 20px; color: red;">
        ⚠ NERACA TIDAK SEIMBANG
    </p>
@endif

@else
<p>Data neraca tidak tersedia atau format tidak sesuai.</p>
@endif
