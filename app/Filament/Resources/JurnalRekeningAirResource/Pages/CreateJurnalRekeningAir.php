<?php

namespace App\Filament\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Resources\JurnalRekeningAirResource;
use App\Models\JurnalRekeningAir;
use App\Models\JurnalRekeningAirDetail;
use App\Models\Rekening;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateJurnalRekeningAir extends CreateRecord
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Validasi sebelum create
     */
    protected function beforeCreate(): void
    {
        $formData = $this->form->getState();
        $items = $formData['rekening_air_items'] ?? [];
        $itemsCompleted = $formData['items_completed'] ?? false;

        // Validasi ada items
        if (empty($items)) {
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Tidak ada item transaksi!')
                ->body('Tambahkan minimal 1 item transaksi terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        // Validasi konfirmasi selesai
        if (!$itemsCompleted) {
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Item belum dikonfirmasi!')
                ->body('Klik tombol "Konfirmasi Selesai Menambah Item" terlebih dahulu sebelum menyimpan.')
                ->danger()
                ->send();
            return;
        }
    }

    /**
     * Method untuk menghapus item
     */
    public function removeItem($index)
    {
        try {
            $items = $this->data['rekening_air_items'] ?? [];
            
            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['rekening_air_items'] = $items;
                
                // Reset konfirmasi
                $this->data['items_completed'] = false;
                
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
    public function editItem($index, $item)
    {
        try {
            // Populate form fields
            $this->data['temp_kode_proyek'] = $item['kode_proyek'] ?? null;
            $this->data['temp_rekening'] = $item['rekening'] ?? null;
            $this->data['temp_nomor_bantu'] = $item['nomor_bantu'] ?? null;
            $this->data['temp_position'] = $item['position'] ?? 'debit';
            
            // Format jumlah
            $jumlah = $item['jumlah'] ?? 0;
            $this->data['temp_jumlah'] = $jumlah ? number_format($jumlah, 0, ',', '.') : '';
            
            // Remove item from list
            $items = $this->data['rekening_air_items'] ?? [];
            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['rekening_air_items'] = $items;
            }
            
            // Reset konfirmasi
            $this->data['items_completed'] = false;
            
            \Filament\Notifications\Notification::make()
                ->title('Item dimuat untuk diedit')
                ->body('Data item telah dimuat ke form. Silakan ubah dan klik "Tambah Item" untuk menyimpan perubahan.')
                ->info()
                ->send();
                
            // Dispatch event untuk scroll ke form
            $this->dispatch('scroll-to-form');
                
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal edit item')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Handle record creation dengan metode add item
     */
    protected function handleRecordCreation(array $data): JurnalRekeningAir
    {
        // Extract items
        $rekeningAirItems = $data['rekening_air_items'] ?? [];
        $itemsCompleted = $data['items_completed'] ?? false;
        unset($data['rekening_air_items'], $data['items_completed']);

        if (empty($rekeningAirItems)) {
            throw new \Exception('Minimal harus ada 1 item transaksi');
        }

        // Validasi konfirmasi
        if (!$itemsCompleted) {
            throw new \Exception('Items belum dikonfirmasi selesai. Klik tombol konfirmasi terlebih dahulu.');
        }

        // Validasi balance
        $totalDebit = collect($rekeningAirItems)->where('position', 'debit')->sum('jumlah');
        $totalKredit = collect($rekeningAirItems)->where('position', 'kredit')->sum('jumlah');

        if ($totalDebit !== $totalKredit) {
            throw new \Exception("Jurnal tidak balance! Total Debit: Rp " . number_format($totalDebit, 0, ',', '.') .
                " | Total Kredit: Rp " . number_format($totalKredit, 0, ',', '.'));
        }

        if ($totalDebit == 0) {
            throw new \Exception("Jurnal harus memiliki minimal 1 item Debit dan 1 item Kredit!");
        }

        // Set rp dari total
        $data['rp'] = $totalDebit;

        // Generate nomor referensi
        $year = now()->year;
        $lastJurnal = JurnalRekeningAir::where('no_reff', 'LIKE', "2-%/{$year}")
            ->orderBy('created_at', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastJurnal && preg_match('/^2-(\d+)\/\d{4}$/', $lastJurnal->no_reff, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        $data['no_reff'] = "2-{$nextNumber}/{$year}";

        // Create jurnal header
        $jurnal = JurnalRekeningAir::create($data);

        // Create detail items
        foreach ($rekeningAirItems as $item) {
            $rekening = Rekening::with('kelompok')->find($item['rekening'] ?? null);
            
            JurnalRekeningAirDetail::create([
                'jurnal_rekening_air_id' => $jurnal->id,
                'kelompok_id' => $rekening?->kelompok_id,
                'rekening_id' => $item['rekening'],
                'nomor_bantu_id' => $item['nomor_bantu'] ?? null,
                'kode_proyek_id' => $item['kode_proyek'] ?? null,
                'position' => $item['position'],
                'jumlah' => $item['jumlah'],
            ]);
        }

        return $jurnal;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jurnal Rekening Air berhasil dibuat';
    }
}
