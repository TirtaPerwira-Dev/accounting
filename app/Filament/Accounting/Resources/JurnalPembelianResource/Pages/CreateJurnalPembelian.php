<?php

namespace App\Filament\Accounting\Resources\JurnalPembelianResource\Pages;

use App\Filament\Accounting\Resources\JurnalPembelianResource;
use App\Models\JurnalPembelian;
use App\Models\NomorBantu;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateJurnalPembelian extends CreateRecord
{
    protected static string $resource = JurnalPembelianResource::class;

    /**
     * Validasi form sebelum submit
     */
    protected function beforeFill(): void
    {
        // Set default state
        $this->data['items_completed'] = false;
    }

    /**
     * Hook untuk memvalidasi form data sebelum submission
     */
    protected function beforeCreate(): void
    {
        $formData = $this->form->getState();
        $items = $formData['pembelian_items'] ?? [];
        $itemsCompleted = $formData['items_completed'] ?? false;

        // Validasi ada items
        if (empty($items)) {
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Tidak ada item pembelian!')
                ->body('Tambahkan minimal 1 item pembelian terlebih dahulu.')
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
     * Method untuk menghapus item dari JavaScript
     */
    public function removeItem($index)
    {
        try {
            $items = $this->data['pembelian_items'] ?? [];

            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['pembelian_items'] = $items;

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
     * Method untuk edit item dari JavaScript
     */
    public function editItem($index, $item)
    {
        try {
            // Populate form fields
            $this->data['temp_bukti'] = $item['bukti'] ?? '';
            $this->data['temp_keterangan'] = $item['keterangan'] ?? '';
            $this->data['temp_kode_proyek_id'] = $item['kode_proyek_id'] ?? null;
            $this->data['temp_nomor_bantu_debit_id'] = $item['nomor_bantu_debit_id'] ?? null;

            // Format jumlah
            $jumlah = $item['jumlah'] ?? 0;
            $this->data['temp_jumlah'] = $jumlah ? number_format($jumlah, 0, ',', '.') : '';

            // Remove item from list
            $items = $this->data['pembelian_items'] ?? [];
            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['pembelian_items'] = $items;
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
     * Custom success notification
     */
    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Jurnal Pembelian Berhasil Dibuat!')
            ->body('Data jurnal pembelian telah disimpan dengan nomor referensi yang baru.');
    }

    protected function handleRecordCreation(array $data): JurnalPembelian
    {
        // Extract dan hapus field yang tidak perlu disimpan
        $pembelianItems = $data['pembelian_items'] ?? [];
        $itemsCompleted = $data['items_completed'] ?? false;
        unset($data['pembelian_items'], $data['items_completed']);

        // Hapus temporary fields
        unset($data['temp_bukti'], $data['temp_keterangan'], $data['temp_kode_proyek_id']);
        unset($data['temp_nomor_bantu_debit_id'], $data['temp_jumlah']);
        unset($data['rekening_kredit_id']); // This is used for form selection, not stored

        if (empty($pembelianItems)) {
            throw new \Exception('Minimal harus ada 1 item pembelian');
        }

        // Double check validasi konfirmasi (sebagai backup)
        if (!$itemsCompleted) {
            throw new \Exception('Items belum dikonfirmasi selesai. Klik tombol konfirmasi terlebih dahulu.');
        }

        // Hitung total dari semua items
        $totalRp = collect($pembelianItems)->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

        // Cek apakah ada item dengan Aktiva Tetap
        $hasAktivaTetap = false;
        foreach ($pembelianItems as $item) {
            if (!empty($item['nomor_bantu_debit_id'])) {
                $nomorBantu = NomorBantu::with(['rekening'])->find($item['nomor_bantu_debit_id']);
                if ($nomorBantu && $nomorBantu->rekening && $nomorBantu->rekening->data === 'AT') {
                    $hasAktivaTetap = true;
                    break;
                }
            }
        }

        // Prepare data untuk header (tanpa detail items)
        $headerData = [
            'no_reff' => $data['no_reff'] ?? null, // Will be auto-generated by model boot
            'tanggal' => $data['tanggal'],
            'nomor_bantu_kredit_id' => $data['nomor_bantu_kredit_id'] ?? null,
            'nama_nomor_bantu_kredit' => $data['nama_nomor_bantu_kredit'] ?? null,
            'data_k' => $data['data_k'] ?? null,
            'data_d' => $hasAktivaTetap ? 'AT' : null,
            'rp' => $totalRp,
            'keterangan' => 'Jurnal Pembelian Barang - ' . count($pembelianItems) . ' item(s)',
            'company_id' => $data['company_id'] ?? 1,
            'created_by' => auth()->id(),
            'is_confirmed' => false,
        ];

        // Create header record
        $header = JurnalPembelian::create($headerData);

        // Create detail records
        foreach ($pembelianItems as $item) {
            $nomorBantu = null;
            if (!empty($item['nomor_bantu_debit_id'])) {
                $nomorBantu = NomorBantu::with(['rekening.kelompok'])->find($item['nomor_bantu_debit_id']);
            }

            \App\Models\JurnalPembelianDetail::create([
                'jurnal_pembelian_id' => $header->id,
                'bukti' => $item['bukti'] ?? null,
                'keterangan' => $item['keterangan'] ?? null,
                'jumlah' => (float) ($item['jumlah'] ?? 0),
                'kelompok_debit_id' => $nomorBantu?->rekening?->kelompok_id,
                'rekening_debit_id' => $nomorBantu?->rekening_id,
                'nomor_bantu_debit_id' => $item['nomor_bantu_debit_id'] ?? null,
                'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
            ]);
        }

        return $header;
    }
}
