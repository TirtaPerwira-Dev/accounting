<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use App\Models\JurnalMemorial;
use App\Models\Rekening;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class CreateJurnalMemorial extends CreateRecord
{
    protected static string $resource = JurnalMemorialResource::class;

    /**
     * Listener untuk menghapus item dari staging area
     */
    #[On('remove-memorial-item')]
    public function removeMemorialItem($index)
    {
        try {
            $items = $this->data['memorial_items'] ?? [];

            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['memorial_items'] = $items;

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
     * Handle custom record creation
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $items = $data['memorial_items'] ?? [];
            unset($data['memorial_items']);

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

            return $header; // Returning the header for memorial resource
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
