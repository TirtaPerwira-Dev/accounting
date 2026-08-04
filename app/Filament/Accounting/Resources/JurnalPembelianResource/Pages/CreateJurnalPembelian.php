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
     * Method untuk menghapus item
     */
    public function removeItem($index)
    {
        try {
            $items = $this->data['pembelian_items'] ?? [];

            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['pembelian_items'] = $items;

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
            $items = $this->data['pembelian_items'] ?? [];

            if (!isset($items[$index])) {
                return;
            }

            $item = $items[$index];

            // Populate form fields
            $this->data['temp_bukti'] = $item['bukti'] ?? null;
            $this->data['temp_keterangan'] = $item['keterangan'] ?? null;
            $this->data['temp_kode_proyek_id'] = $item['kode_proyek_id'] ?? null;
            $this->data['temp_nomor_bantu_debit_id'] = $item['nomor_bantu_debit_id'] ?? null;
            $this->data['temp_rekening_debit_id'] = $item['rekening_debit_id'] ?? null;

            if (!$this->data['temp_rekening_debit_id'] && !empty($item['nomor_bantu_debit_id'])) {
                $nomorBantu = NomorBantu::find($item['nomor_bantu_debit_id']);
                $this->data['temp_rekening_debit_id'] = $nomorBantu?->rekening_id;
            }

            $this->data['temp_jumlah'] = number_format((float) ($item['jumlah'] ?? 0), 0, ',', '.');

            // Remove item from list
            array_splice($items, $index, 1);
            $this->data['pembelian_items'] = $items;

            \Filament\Notifications\Notification::make()
                ->title('Item dimuat untuk diedit')
                ->info()
                ->send();

            // Emit event untuk scroll ke form (opsional, script ada di view)
            $this->dispatch('scroll-to-form');
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal edit item')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function handleRecordCreation(array $data): \App\Models\JurnalPembelianDetail
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $pembelianItems = $data['pembelian_items'] ?? [];
            unset($data['pembelian_items']);

            if (empty($pembelianItems)) {
                throw new \Exception('Minimal harus ada 1 item pembelian');
            }

            // Hitung total dari semua items
            $totalRp = collect($pembelianItems)->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
            
            // Cek apakah ada item dengan Aktiva Tetap (Data 'AT')
            $hasAktivaTetap = false;
            foreach ($pembelianItems as $item) {
                if (!empty($item['nomor_bantu_debit_id'])) {
                    $nomorBantu = \App\Models\NomorBantu::with(['rekening'])->find($item['nomor_bantu_debit_id']);
                    if ($nomorBantu && $nomorBantu->rekening && $nomorBantu->rekening->data === 'AT') {
                        $hasAktivaTetap = true;
                        break;
                    }
                }
            }

            // Ambil data header dari item pertama untuk fallback jika diperlukan
            $firstItem = $pembelianItems[0];

            // Prepare header data
            $headerData = [
                'tanggal' => $data['tanggal'],
                'bukti' => $firstItem['bukti'] ?? null,
                'kode_proyek_id' => $firstItem['kode_proyek_id'] ?? null,
                'nomor_bantu_kredit_id' => $data['nomor_bantu_kredit_id'] ?? null,
                'nama_nomor_bantu_kredit' => $data['nama_nomor_bantu_kredit'] ?? null,
                'data_k' => null, // Will be set in boot or manual
                'data_d' => $hasAktivaTetap ? 'AT' : null,
                'rp' => $totalRp,
                'keterangan' => $data['keterangan_header'] ?? ('Jurnal Pembelian Barang - ' . count($pembelianItems) . ' item(s)'),
                'company_id' => 1,
                'created_by' => auth()->id(),
                'is_confirmed' => false,
            ];

            // Set data_k from nomor_bantu_kredit
            if ($headerData['nomor_bantu_kredit_id']) {
                $nbKredit = \App\Models\NomorBantu::with('rekening')->find($headerData['nomor_bantu_kredit_id']);
                $headerData['data_k'] = $nbKredit?->rekening?->data;
            }

            // Create header
            $header = \App\Models\JurnalPembelian::create($headerData);

            $createdDetails = [];
            foreach ($pembelianItems as $item) {
                $nomorBantu = null;
                if (!empty($item['nomor_bantu_debit_id'])) {
                    $nomorBantu = \App\Models\NomorBantu::with(['rekening.kelompok'])->find($item['nomor_bantu_debit_id']);
                }

                $createdDetails[] = \App\Models\JurnalPembelianDetail::create([
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

            // Return detail pertama agar Filament bisa redirect ke view/edit yang benar
            return $createdDetails[0];
        });
    }
}
