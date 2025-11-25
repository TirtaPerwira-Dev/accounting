@if(isset($data['accounts']) && is_array($data['accounts']))
<table>
    <thead>
        <tr>
            <th style="width: 15%;">Kode</th>
            <th style="width: 40%;">Nama Akun</th>
            <th style="width: 15%;" class="text-right">Debit (Rp)</th>
            <th style="width: 15%;" class="text-right">Kredit (Rp)</th>
            <th style="width: 15%;" class="text-right">Saldo (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['accounts'] as $account)
            <tr>
                <td>{{ $account['sakep_code'] ?? $account['code'] ?? '' }}</td>
                <td>{{ $account['account_name'] ?? $account['name'] ?? 'N/A' }}</td>
                <td class="amount">
                    {{ isset($account['total_debit']) && $account['total_debit'] > 0 ? number_format($account['total_debit'], 0, ',', '.') : '-' }}
                </td>
                <td class="amount">
                    {{ isset($account['total_credit']) && $account['total_credit'] > 0 ? number_format($account['total_credit'], 0, ',', '.') : '-' }}
                </td>
                <td class="amount">
                    {{ number_format($account['balance'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach

        <tr style="border-top: 2px solid #333;">
            <td colspan="2" class="font-bold">TOTAL</td>
            <td class="amount font-bold">
                {{ number_format($data['total_debit'] ?? 0, 0, ',', '.') }}
            </td>
            <td class="amount font-bold">
                {{ number_format($data['total_credit'] ?? 0, 0, ',', '.') }}
            </td>
            <td class="amount font-bold">
                {{ number_format(($data['total_debit'] ?? 0) - ($data['total_credit'] ?? 0), 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>

@if(isset($data['is_balanced']) && $data['is_balanced'])
    <p class="text-center font-bold" style="margin-top: 20px; color: green;">
        ✓ NERACA SALDO SEIMBANG (Total Debit = Total Kredit)
    </p>
@else
    <p class="text-center font-bold" style="margin-top: 20px; color: red;">
        ⚠ NERACA SALDO TIDAK SEIMBANG
    </p>
@endif

@else
<p>Data neraca saldo tidak tersedia atau format tidak sesuai.</p>
@endif
