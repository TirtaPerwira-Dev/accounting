<?php

namespace App\Filament\Resources\JurnalPembelianResource\Pages;

use App\Filament\Resources\JurnalPembelianResource;
use App\Models\JurnalPembelian;
use App\Models\NomorBantu;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateJurnalPembelian extends CreateRecord
{
    protected static string $resource = JurnalPembelianResource::class;

    protected function handleRecordCreation(array $data): JurnalPembelian
    {
        // Extract pembelian items
        $pembelianItems = $data['pembelian_items'] ?? [];
        unset($data['pembelian_items']);

        if (empty($pembelianItems)) {
            throw new \Exception('Minimal harus ada 1 item pembelian');
        }

        // Generate group transaksi ID jika lebih dari 1 item
        $groupTransaksi = count($pembelianItems) > 1 ? Str::uuid()->toString() : null;

        // Generate nomor referensi sekali - perbaiki logic
        $year = now()->year;
        $lastJurnal = JurnalPembelian::where('no_reff', 'LIKE', "1-_/{$year}")
            ->orderBy('created_at', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastJurnal && preg_match('/^1-(\d+)\/\d{4}$/', $lastJurnal->no_reff, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        $noReff = "1-{$nextNumber}/{$year}";

        $createdRecords = [];

        foreach ($pembelianItems as $index => $item) {
            // Get nomor bantu info untuk populate kelompok dan rekening
            $nomorBantu = null;
            if (!empty($item['nomor_bantu_debit_id'])) {
                $nomorBantu = NomorBantu::with(['rekening.kelompok'])->find($item['nomor_bantu_debit_id']);
            }

            // Prepare data untuk setiap record
            $recordData = array_merge($data, [
                'no_reff' => $noReff, // Set explicitly
                'bukti' => $item['bukti'] ?? null,
                'bukti_item' => $item['bukti'] ?? null,
                'keterangan_item' => $item['keterangan'],
                'jumlah_item' => (float) ($item['jumlah'] ?? 0),
                'kelompok_debit_id' => $nomorBantu?->rekening->kelompok_id,
                'rekening_debit_id' => $nomorBantu?->rekening_id,
                'nomor_bantu_debit_id' => $item['nomor_bantu_debit_id'] ?? null,
                'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                'group_transaksi' => $groupTransaksi,
                'item_sequence' => $index + 1,
            ]);

            // Set data_d jika ada rekening AT
            if ($nomorBantu && $nomorBantu->rekening->data === 'AT') {
                $recordData['data_d'] = 'AT';
            }

            // Create record tanpa trigger boot method untuk no_reff
            $record = new JurnalPembelian($recordData);
            $record->save();
            $createdRecords[] = $record;
        }

        // Return record pertama sebagai representasi grup
        return $createdRecords[0];
    }
}
