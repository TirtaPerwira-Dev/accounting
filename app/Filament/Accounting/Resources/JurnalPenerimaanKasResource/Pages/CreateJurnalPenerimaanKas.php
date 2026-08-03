<?php

namespace App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Accounting\Resources\JurnalPenerimaanKasResource;
use App\Models\JurnalPenerimaanKas;
use App\Models\JurnalPenerimaanKasDetail;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateJurnalPenerimaanKas extends CreateRecord
{
    protected static string $resource = JurnalPenerimaanKasResource::class;

    /**
     * Method untuk menghapus item dari staging area
     */
    public function removeItem($index)
    {
        try {
            $items = $this->data['penerimaan_items'] ?? [];

            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['penerimaan_items'] = $items;

                \Filament\Notifications\Notification::make()
                    ->title('Item berhasil dihapus!')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal menghapus item')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Method untuk edit item dari staging area
     */
    public function editItem($index)
    {
        try {
            $items = $this->data['penerimaan_items'] ?? [];

            if (isset($items[$index])) {
                $item = $items[$index];

                // Populate temp fields
                $this->data['temp_nomor_bukti'] = $item['nomor_bukti'] ?? null;
                $this->data['temp_kode_proyek_id'] = $item['kode_proyek_id'] ?? $item['kode_proyek'] ?? null;
                $this->data['temp_jumlah'] = $item['jumlah'] ?? 0;
                $this->data['temp_rekening_id'] = $item['rekening_id'] ?? $item['rekening'] ?? null;
                $this->data['temp_nomor_bantu_id'] = $item['nomor_bantu_id'] ?? $item['nomor_bantu'] ?? null;
                $this->data['temp_keterangan_item'] = $item['keterangan_item'] ?? null;

                // Remove item from list
                array_splice($items, $index, 1);
                $this->data['penerimaan_items'] = $items;

                \Filament\Notifications\Notification::make()
                    ->title('Item dimuat untuk diedit')
                    ->info()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal memuat item')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Handle custom record creation
     */
    protected function handleRecordCreation(array $data): Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $items = $data['penerimaan_items'] ?? [];
            unset($data['penerimaan_items']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item penerimaan');
            }

            // Calculate total
            $totalAmount = collect($items)->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

            // Buat header jurnal
            $jurnal = JurnalPenerimaanKas::create([
                'kelompok_id' => 10, // Aktiva Lancar
                'rekening_id' => $data['rekening_id'] ?? null,
                'kas_bank_id' => $data['kas_bank_id'] ?? null,
                'tanggal' => $data['tanggal'],
                'nomor_bukti' => $items[0]['nomor_bukti'] ?? 'BKM-' . date('YmdHis'),
                'keterangan' => $data['keterangan'] ?? 'Penerimaan Kas/Bank',
                'total_amount' => $totalAmount,
                'no_reff' => '3',
                'company_id' => 1,
                'created_by' => auth()->id(),
                'is_confirmed' => false,
            ]);

            $createdDetails = [];
            foreach ($items as $item) {
                // Mapping custom staging keys: 'rekening' => 'rekening_id', etc.
                $rekeningId = $item['rekening'] ?? $item['rekening_id'] ?? null;
                $nomorBantuId = $item['nomor_bantu'] ?? $item['nomor_bantu_id'] ?? null;
                $kodeProyekId = $item['kode_proyek'] ?? $item['kode_proyek_id'] ?? null;

                $rekening = \App\Models\Rekening::find($rekeningId);

                $createdDetails[] = JurnalPenerimaanKasDetail::create([
                    'jurnal_penerimaan_kas_id' => $jurnal->id,
                    'nomor_bukti' => $item['nomor_bukti'] ?? null,
                    'kode_proyek_id' => $kodeProyekId,
                    'kelompok_id' => $rekening?->kelompok_id,
                    'rekening_id' => $rekeningId,
                    'nomor_bantu_id' => $nomorBantuId,
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'keterangan_item' => $item['keterangan_item'] ?? null,
                ]);
            }

            return $createdDetails[0];
        });
    }

    /**
     * Redirect setelah create
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Success notification
     */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Jurnal Penerimaan Kas berhasil dibuat!')
            ->body('Data telah disimpan ke database.');
    }
}
