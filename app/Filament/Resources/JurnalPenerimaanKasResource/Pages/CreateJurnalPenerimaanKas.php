<?php

namespace App\Filament\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Resources\JurnalPenerimaanKasResource;
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
     * Validasi sebelum create
     */
    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        // Validasi apakah sudah konfirmasi selesai menambah item
        if (!($data['items_completed'] ?? false)) {
            Notification::make()
                ->title('Belum dikonfirmasi!')
                ->body('Klik tombol "Konfirmasi Selesai Menambah Item" terlebih dahulu sebelum menyimpan.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validasi minimal 1 item
        if (empty($data['detail_penerimaan'])) {
            Notification::make()
                ->title('Item kosong!')
                ->body('Tambahkan minimal 1 item sumber penerimaan.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validasi total
        $total = collect($data['detail_penerimaan'])->sum('jumlah');
        if ($total <= 0) {
            Notification::make()
                ->title('Total tidak valid!')
                ->body('Total penerimaan harus lebih dari 0.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    /**
     * Handle custom record creation
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Generate reff otomatis
        $lastRecord = JurnalPenerimaanKas::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        $nextNumber = $lastRecord + 1;
        $reff = '3-' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT) . '/' . now()->format('m/Y');

        // Calculate total
        $items = $data['detail_penerimaan'] ?? [];
        $total = collect($items)->sum('jumlah');

        // Buat header jurnal
        $jurnal = JurnalPenerimaanKas::create([
            'kelompok_id' => $data['kelompok_id'] ?? null,
            'rekening_id' => $data['rekening_id'] ?? null,
            'kas_bank_id' => $data['kas_bank_id'],
            'tanggal' => $data['tanggal'],
            'nomor_bukti' => $items[0]['nomor_bukti'] ?? 'AUTO-' . $reff,
            'keterangan' => $data['keterangan'] ?? 'Penerimaan Kas/Bank',
            'kode_proyek_id' => null, // Tidak ada di form utama
            'nomor_rekening_id' => $data['rekening_id'] ?? null,
            'jumlah' => $total,
            'detail_penerimaan' => $items,
            'total_amount' => $total,
            'reff' => $reff,
        ]);

        // Buat detail items dari array
        foreach ($items as $item) {
            // Get kelompok_id from rekening sumber
            $rekening = \App\Models\Rekening::find($item['rekening']);
            
            JurnalPenerimaanKasDetail::create([
                'jurnal_penerimaan_kas_id' => $jurnal->id,
                'nomor_bukti' => $item['nomor_bukti'] ?? null,
                'kode_proyek_id' => $item['kode_proyek'] ?? null,
                'kelompok_id' => $rekening?->kelompok_id ?? null,
                'rekening_id' => $item['rekening'],
                'nomor_bantu_id' => $item['nomor_bantu'] ?? null,
                'jumlah' => $item['jumlah'],
                'keterangan_item' => $item['keterangan_item'] ?? null,
            ]);
        }

        return $jurnal;
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

    /**
     * Method untuk hapus item dari form
     */
    public function removeItem(int $index): void
    {
        $data = $this->form->getState();
        $items = $data['detail_penerimaan'] ?? [];

        if (isset($items[$index])) {
            unset($items[$index]);
            $items = array_values($items); // Re-index array
            $this->form->fill(['detail_penerimaan' => $items]);

            // Reset konfirmasi jika masih ada perubahan
            $this->form->fill(['items_completed' => false]);

            Notification::make()
                ->title('Item dihapus!')
                ->success()
                ->send();
        }
    }

    /**
     * Method untuk edit item (load data ke form)
     */
    public function editItem(int $index): void
    {
        $data = $this->form->getState();
        $items = $data['detail_penerimaan'] ?? [];

        if (isset($items[$index])) {
            $item = $items[$index];

            // Load data ke temporary form fields
            $this->form->fill([
                'temp_nomor_bukti' => $item['nomor_bukti'] ?? null,
                'temp_kode_proyek' => $item['kode_proyek'] ?? null,
                'temp_rekening' => $item['rekening'] ?? null,
                'temp_nomor_bantu' => $item['nomor_bantu'] ?? null,
                'temp_jumlah' => number_format($item['jumlah'] ?? 0, 0, ',', '.'),
                'temp_keterangan_item' => $item['keterangan_item'] ?? null,
            ]);

            // Hapus item lama
            unset($items[$index]);
            $items = array_values($items);
            $this->form->fill(['detail_penerimaan' => $items]);

            // Reset konfirmasi
            $this->form->fill(['items_completed' => false]);

            Notification::make()
                ->title('Item dimuat ke form!')
                ->body('Silakan edit lalu klik "Tambah Item" untuk menyimpan perubahan.')
                ->info()
                ->send();
        }
    }
}
