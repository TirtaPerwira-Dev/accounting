<?php

namespace App\Filament\Accounting\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Accounting\Resources\JurnalRekeningAirResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateJurnalRekeningAir extends CreateRecord
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Method untuk menghapus item
     */
    public function removeItem($index)
    {
        try {
            if (($this->data['items_completed'] ?? false) === true) {
                \Filament\Notifications\Notification::make()
                    ->title('Item sudah dikonfirmasi')
                    ->body('Reset konfirmasi terlebih dahulu jika ingin mengubah daftar item.')
                    ->warning()
                    ->send();
                return;
            }

            $items = $this->data['rekening_air_items'] ?? [];

            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['rekening_air_items'] = $items;

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
     * Method untuk edit item
     */
    public function editItem($index)
    {
        try {
            if (($this->data['items_completed'] ?? false) === true) {
                \Filament\Notifications\Notification::make()
                    ->title('Item sudah dikonfirmasi')
                    ->body('Reset konfirmasi terlebih dahulu jika ingin mengubah daftar item.')
                    ->warning()
                    ->send();
                return;
            }

            $items = $this->data['rekening_air_items'] ?? [];

            if (!isset($items[$index])) {
                return;
            }

            $item = $items[$index];

            // Populate form fields
            $this->data['temp_rekening_id'] = $item['rekening_id'] ?? $item['rekening'] ?? null;
            $this->data['temp_nomor_bantu_id'] = $item['nomor_bantu_id'] ?? $item['nomor_bantu'] ?? null;
            $this->data['temp_kode_proyek_id'] = $item['kode_proyek_id'] ?? $item['kode_proyek'] ?? null;
            $this->data['temp_position'] = $item['position'] ?? 'debit';
            $this->data['temp_jumlah'] = number_format((float) ($item['jumlah'] ?? 0), 0, ',', '.');

            // Remove item from list
            array_splice($items, $index, 1);
            $this->data['rekening_air_items'] = $items;

            \Filament\Notifications\Notification::make()
                ->title('Item dimuat untuk diedit')
                ->info()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal edit item')
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
            $items = $data['rekening_air_items'] ?? [];
            unset($data['rekening_air_items']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item transaksi');
            }

            if (($data['items_completed'] ?? false) !== true) {
                throw ValidationException::withMessages([
                    'items_completed' => 'Konfirmasi item transaksi terlebih dahulu sebelum menyimpan.',
                ]);
            }

            // Hitung total debit dan kredit untuk validasi
            $totalDebit = collect($items)
                ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'debit')
                ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
            $totalKredit = collect($items)
                ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'kredit')
                ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

            // Validasi balance
            if (abs($totalDebit - $totalKredit) > 0.01 || $totalDebit <= 0 || $totalKredit <= 0) {
                throw new \Exception('Jurnal tidak balance! Total Debit: Rp ' . number_format($totalDebit, 0, ',', '.') . ', Total Kredit: Rp ' . number_format($totalKredit, 0, ',', '.'));
            }

            $totalItemInput = (int) preg_replace('/[^0-9]/', '', (string) ($data['total_item_input'] ?? '0'));
            $nominalInput = (float) preg_replace('/[^0-9]/', '', (string) ($data['nominal_input'] ?? '0'));

            // Create Header
            $header = \App\Models\JurnalRekeningAir::create([
                'bukti' => $data['bukti'],
                'tanggal' => $data['tanggal'],
                'no_reff' => '2',
                'rp' => $totalDebit,
                'keterangan' => $data['keterangan'] ?? ('Jurnal Rekening Air ' . $data['bukti']),
                'lampiran' => $data['lampiran'] ?? null,
                'total_item_input' => $totalItemInput,
                'nominal_input' => $nominalInput,
                'company_id' => 1,
                'created_by' => Auth::id(),
                'is_confirmed' => true,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);

            $createdDetails = [];
            foreach ($items as $item) {
                $rekening = \App\Models\Rekening::find($item['rekening_id']);

                $createdDetails[] = \App\Models\JurnalRekeningAirDetail::create([
                    'jurnal_rekening_air_id' => $header->id,
                    'kelompok_id' => $rekening?->kelompok_id,
                    'rekening_id' => $item['rekening_id'],
                    'nomor_bantu_id' => $item['nomor_bantu_id'] ?? null,
                    'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                    'position' => $item['position'],
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                ]);
            }

            return $createdDetails[0];
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jurnal Rekening Air berhasil dibuat';
    }
}
