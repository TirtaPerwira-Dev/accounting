<?php

namespace App\Filament\Accounting\Resources\JurnalPembelianResource\Pages;

use App\Filament\Accounting\Resources\JurnalPembelianResource;
use App\Models\JurnalPembelian;
use App\Models\NomorBantu;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EditJurnalPembelian extends EditRecord
{
    protected static string $resource = JurnalPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Jika record ini bagian dari group, load semua items dalam group
        if ($this->record->group_transaksi) {
            $groupItems = JurnalPembelian::where('group_transaksi', $this->record->group_transaksi)
                ->orderBy('item_sequence')
                ->get();

            // Convert ke format pembelian_items untuk form
            $data['pembelian_items'] = $groupItems->map(function ($item) {
                return [
                    'bukti' => $item->bukti_item,
                    'keterangan' => $item->keterangan_item,
                    'jumlah' => $item->jumlah_item,
                    'nomor_bantu_debit_id' => $item->nomor_bantu_debit_id,
                    'kode_proyek_id' => $item->kode_proyek_id,
                ];
            })->toArray();
        } else {
            // Single item
            $data['pembelian_items'] = [[
                'bukti' => $this->record->bukti_item,
                'keterangan' => $this->record->keterangan_item,
                'jumlah' => $this->record->jumlah_item,
                'nomor_bantu_debit_id' => $this->record->nomor_bantu_debit_id,
                'kode_proyek_id' => $this->record->kode_proyek_id,
            ]];
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract pembelian items
        $pembelianItems = $data['pembelian_items'] ?? [];
        unset($data['pembelian_items']);

        if (empty($pembelianItems)) {
            throw new \Exception('Minimal harus ada 1 item pembelian');
        }

        // Jika record ini bagian dari group, hapus semua records dalam group
        if ($record->group_transaksi) {
            JurnalPembelian::where('group_transaksi', $record->group_transaksi)->delete();
        } else {
            // Hapus single record
            $record->delete();
        }

        // Generate group transaksi ID jika lebih dari 1 item
        $groupTransaksi = count($pembelianItems) > 1 ? $record->group_transaksi ?? Str::uuid()->toString() : null;

        $updatedRecords = [];

        foreach ($pembelianItems as $index => $item) {
            // Get nomor bantu info
            $nomorBantu = null;
            if (!empty($item['nomor_bantu_debit_id'])) {
                $nomorBantu = NomorBantu::with(['rekening.kelompok'])->find($item['nomor_bantu_debit_id']);
            }

            // Prepare data untuk setiap record
            $recordData = array_merge($data, [
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

            $newRecord = JurnalPembelian::create($recordData);
            $updatedRecords[] = $newRecord;
        }

        // Return record pertama sebagai representasi grup
        return $updatedRecords[0];
    }
}
