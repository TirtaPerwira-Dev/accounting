<?php

namespace App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource;
use App\Models\JurnalBayarKasBank;
use App\Models\NomorBantu;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CreateJurnalBayarKasBank extends CreateRecord
{
    protected static string $resource = JurnalBayarKasBankResource::class;

    protected function handleRecordCreation(array $data): JurnalBayarKasBank
    {
        // Extract item pembayaran dari detail_pembayaran
        $details = $data['detail_pembayaran'] ?? [];
        unset($data['detail_pembayaran']);
        unset($data['items_completed']);

        if (empty($details)) {
            throw new \Exception('Minimal harus ada 1 item pembayaran');
        }

        // Generate group transaksi ID jika lebih dari 1 item
        $groupTransaksi = count($details) > 1 ? Str::uuid()->toString() : null;

        // Generate no_voucher sekali
        $noVoucher = $data['no_voucher'] ?? 'BKB-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        $createdRecords = [];
        $totalPembayaran = 0;

        foreach ($details as $index => $item) {
            // Get nomor bantu info untuk populate kelompok dan rekening
            $nomorBantu = null;
            if (!empty($item['nomor_bantu'])) {
                $nomorBantu = NomorBantu::with(['rekening.kelompok'])->find($item['nomor_bantu']);
            }

            $jumlah = (float) ($item['jumlah'] ?? 0);
            $totalPembayaran += $jumlah;

            // Prepare data untuk setiap record
            $recordData = array_merge($data, [
                'no_voucher' => $noVoucher,
                'tanggal' => $data['tanggal_check'], // Fix: Add tanggal field
                'rekening_id' => $nomorBantu?->rekening_id ?? ($item['rekening'] ?? $data['rekening_id']),
                'kelompok_id' => $nomorBantu?->rekening->kelompok_id ?? $data['kelompok_id'],
                'nomor_bantu_id' => $item['nomor_bantu'] ?? null,
                'kode_proyek_id' => $item['kode_proyek'] ?? null,
                'rp' => $jumlah,
                'keterangan' => $item['keterangan'] ?? '',
                'group_transaksi' => $groupTransaksi,
                'item_sequence' => $index + 1,
                'created_by' => Auth::id(),
                'is_confirmed' => false,
            ]);

            // Remove field yang tidak ada di table
            unset($recordData['rekening_bank_id'], $recordData['total_pembayaran']);

            $record = JurnalBayarKasBank::create($recordData);
            $createdRecords[] = $record;
        }

        // Return record pertama sebagai representasi grup
        return $createdRecords[0];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
