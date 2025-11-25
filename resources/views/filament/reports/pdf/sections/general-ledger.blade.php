@if(isset($data['account']))
<div style="margin-bottom: 20px;">
    <h3 style="margin: 0; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
        {{ $data['account']['code'] ?? '' }} - {{ $data['account']['name'] ?? 'N/A' }}
    </h3>
</div>

@if(isset($data['transactions']) && is_array($data['transactions']) && count($data['transactions']) > 0)
<table>
    <thead>
        <tr>
            <th style="width: 12%;">Tanggal</th>
            <th style="width: 15%;">No. Jurnal</th>
            <th style="width: 38%;">Keterangan</th>
            <th style="width: 15%;" class="text-right">Debit (Rp)</th>
            <th style="width: 15%;" class="text-right">Kredit (Rp)</th>
            <th style="width: 15%;" class="text-right">Saldo (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['transactions'] as $transaction)
            <tr>
                <td>{{ isset($transaction['date']) ? \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') : '' }}</td>
                <td>{{ $transaction['journal_number'] ?? '' }}</td>
                <td>{{ $transaction['description'] ?? '' }}</td>
                <td class="amount">
                    {{ isset($transaction['debit']) && $transaction['debit'] > 0 ? number_format($transaction['debit'], 0, ',', '.') : '-' }}
                </td>
                <td class="amount">
                    {{ isset($transaction['credit']) && $transaction['credit'] > 0 ? number_format($transaction['credit'], 0, ',', '.') : '-' }}
                </td>
                <td class="amount">
                    {{ number_format($transaction['balance'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach

        <tr style="border-top: 2px solid #333;">
            <td colspan="3" class="font-bold">TOTAL</td>
            <td class="amount font-bold">
                {{ number_format($data['total_debit'] ?? 0, 0, ',', '.') }}
            </td>
            <td class="amount font-bold">
                {{ number_format($data['total_credit'] ?? 0, 0, ',', '.') }}
            </td>
            <td class="amount font-bold">
                {{ number_format($data['ending_balance'] ?? 0, 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>

<div style="margin-top: 20px;">
    <p><strong>Saldo Awal:</strong> {{ number_format($data['beginning_balance'] ?? 0, 0, ',', '.') }}</p>
    <p><strong>Total Debit:</strong> {{ number_format($data['total_debit'] ?? 0, 0, ',', '.') }}</p>
    <p><strong>Total Kredit:</strong> {{ number_format($data['total_credit'] ?? 0, 0, ',', '.') }}</p>
    <p><strong>Saldo Akhir:</strong> {{ number_format($data['ending_balance'] ?? 0, 0, ',', '.') }}</p>
</div>

@else
<p>Tidak ada transaksi untuk akun ini pada periode yang dipilih.</p>
@endif

@else
<p>Data buku besar tidak tersedia atau format tidak sesuai.</p>
@endif
