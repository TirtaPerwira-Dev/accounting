<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use App\Models\JurnalMemorial;
use Filament\Resources\Pages\CreateRecord;

class CreateJurnalMemorial extends CreateRecord
{
    protected static string $resource = JurnalMemorialResource::class;

    /**
     * Method untuk menghapus item dari staging area
     */
    public function removeItem($index)
    {
        try {
            $items = $this->data['detail_rekening'] ?? [];

            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['detail_rekening'] = $items;

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
     * Method untuk memuat item agar dapat diedit
     */
    public function editItem($index)
    {
        try {
            $items = $this->data['detail_rekening'] ?? [];

            if (!isset($items[$index])) {
                return;
            }

            $item = $items[$index];

            $this->data['temp_rekening'] = $item['rekening'] ?? $item['rekening_id'] ?? null;
            $this->data['temp_nomor_bantu'] = $item['nomor_bantu'] ?? $item['nomor_bantu_id'] ?? null;
            $this->data['temp_kode_proyek'] = $item['kode_proyek'] ?? $item['kode_proyek_id'] ?? null;
            $this->data['temp_position'] = $item['position'] ?? 'debit';
            $this->data['temp_jumlah'] = number_format((float) ($item['jumlah'] ?? 0), 0, ',', '.');
            $this->data['temp_keterangan'] = $item['keterangan'] ?? null;

            array_splice($items, $index, 1);
            $this->data['detail_rekening'] = $items;

            \Filament\Notifications\Notification::make()
                ->title('Item dimuat untuk diedit')
                ->info()
                ->send();
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
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $items = $data['detail_rekening'] ?? [];
            unset($data['detail_rekening']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item memorial');
            }

            // Hitung total debit dan kredit untuk validasi
            $totalDebit = collect($items)->where('position', 'debit')->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
            $totalKredit = collect($items)->where('position', 'kredit')->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

            // Validasi balance
            if (number_format($totalDebit, 2) !== number_format($totalKredit, 2)) {
                throw new \Exception('Jurnal tidak balance! Total Debit: Rp ' . number_format($totalDebit, 0, ',', '.') . ', Total Kredit: Rp ' . number_format($totalKredit, 0, ',', '.'));
            }

            // Create Header
            $header = JurnalMemorial::create([
                'bukti' => $data['bukti'],
                'tanggal' => $data['tanggal'],
                'no_reff' => '6',
                'rp' => $totalDebit,
                'keterangan' => $items[0]['keterangan'] ?? 'Jurnal Memorial',
                'lampiran' => $data['lampiran'] ?? null,
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
                // Convert 'debit'/'kredit' to 'D'/'K' if needed, or keep as is if model supports it
                $posisi = ($item['position'] ?? $item['posisi'] ?? 'debit') === 'debit' ? 'D' : 'K';

                $rekening = \App\Models\Rekening::find($rekeningId);

                $createdDetails[] = \App\Models\JurnalMemorialDetail::create([
                    'jurnal_memorial_id' => $header->id,
                    'bukti' => $header->bukti,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'posisi' => $posisi,
                    'kelompok_id' => $rekening?->kelompok_id,
                    'rekening_id' => $rekeningId,
                    'nomor_bantu_id' => $nomorBantuId,
                    'kode_proyek_id' => $kodeProyekId,
                ]);
            }

            return $createdDetails[0];
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
