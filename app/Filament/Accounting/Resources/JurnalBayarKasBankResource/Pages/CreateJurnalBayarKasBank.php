<?php

namespace App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource;
use App\Models\JurnalBayarKasBank;
use App\Models\NomorBantu;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateJurnalBayarKasBank extends CreateRecord
{
    protected static string $resource = JurnalBayarKasBankResource::class;

    /**
     * Method untuk menghapus item dari staging area
     */
    public function removeItem($index)
    {
        try {
            $items = $this->data['pembayaran_items'] ?? [];

            if (isset($items[$index])) {
                array_splice($items, $index, 1);
                $this->data['pembayaran_items'] = $items;

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
     * Method untuk memuat item ke form agar bisa diedit
     */
    public function editItem($index)
    {
        try {
            $items = $this->data['pembayaran_items'] ?? [];

            if (!isset($items[$index])) {
                return;
            }

            $item = $items[$index];

            $this->data['temp_kode_proyek_id'] = $item['kode_proyek_id'] ?? $item['kode_proyek'] ?? null;
            $this->data['temp_rekening_id'] = $item['rekening_id'] ?? $item['rekening'] ?? null;
            $this->data['temp_nomor_bantu_id'] = $item['nomor_bantu_id'] ?? $item['nomor_bantu'] ?? null;
            $this->data['temp_jumlah'] = $item['jumlah'] ?? 0;
            $this->data['temp_keterangan'] = $item['keterangan'] ?? null;

            array_splice($items, $index, 1);
            $this->data['pembayaran_items'] = $items;

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

    protected function handleRecordCreation(array $data): \App\Models\JurnalBayarKasBankDetail
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $items = $data['pembayaran_items'] ?? [];
            unset($data['pembayaran_items']);
            unset($data['nominal_input']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item pembayaran');
            }

            // Hitung total
            $totalRp = collect($items)->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

            // Fetch kelompok_id for the bank/cash account
            $rekeningBank = \App\Models\Rekening::find($data['rekening_id']);

            // Create Header
            $headerData = [
                'no_voucher' => $data['no_voucher'] ?? null,
                'tanggal' => $data['tanggal_check'],
                'tanggal_check' => $data['tanggal_check'],
                'no_reff' => '4',
                'kelompok_id' => $rekeningBank?->kelompok_id,
                'rekening_id' => $data['rekening_id'] ?? null,
                'nomor_bantu_id' => $data['nomor_bantu_id'] ?? null,
                'no_cek' => $data['no_cek'] ?? null,
                'beban_bagian' => $data['beban_bagian'] ?? null,
                'dibayar_kepada' => $data['dibayar_kepada'] ?? null,
                'rp' => $totalRp,
                'keterangan' => $items[0]['keterangan'] ?? 'Jurnal Bayar Kas/Bank',
                'kode' => 'K', // Kas/Bank berkurang (Kredit)
                'company_id' => 1,
                'created_by' => auth()->id(),
                'is_confirmed' => false,
            ];

            $header = \App\Models\JurnalBayarKasBank::create($headerData);

            $createdDetails = [];
            foreach ($items as $item) {
                // Key mapping untuk custom staging: 'rekening' instead of 'rekening_id'
                $rekeningId = $item['rekening'] ?? $item['rekening_id'] ?? null;
                $nomorBantuId = $item['nomor_bantu'] ?? $item['nomor_bantu_id'] ?? null;
                $kodeProyekId = $item['kode_proyek'] ?? $item['kode_proyek_id'] ?? null;

                $rekening = \App\Models\Rekening::find($rekeningId);

                $createdDetails[] = \App\Models\JurnalBayarKasBankDetail::create([
                    'jurnal_bayar_kas_bank_id' => $header->id,
                    'no_voucher' => $header->no_voucher,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'dibayar_kepada' => $header->dibayar_kepada,
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
