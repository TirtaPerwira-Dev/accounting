<?php

namespace App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource;
use App\Models\JurnalBayarKasBank;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

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
            $this->data['temp_jumlah'] = number_format((float) ($item['jumlah'] ?? 0), 0, ',', '.');
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

    protected function handleRecordCreation(array $data): Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $items = $data['pembayaran_items'] ?? [];
            unset($data['pembayaran_items']);
            unset($data['total_item_input']);
            unset($data['nominal_input']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item pembayaran');
            }

            $bankRekeningId = $data['rekening_id'] ?? null;
            $bankNomorBantuId = $data['nomor_bantu_id'] ?? null;

            $bankRekening = \App\Models\Rekening::find($bankRekeningId);
            if (!$bankRekening) {
                throw new \Exception('Rekening kas/bank tidak valid. Silakan pilih ulang kode rekening bank.');
            }

            $createdJournals = [];

            foreach ($items as $index => $item) {
                $rekeningId = $item['rekening'] ?? $item['rekening_id'] ?? null;
                $nomorBantuId = $item['nomor_bantu'] ?? $item['nomor_bantu_id'] ?? null;
                $kodeProyekId = $item['kode_proyek'] ?? $item['kode_proyek_id'] ?? null;
                $jumlah = (float) ($item['jumlah'] ?? 0);

                if (!$rekeningId || $jumlah <= 0) {
                    continue;
                }

                $rekeningItem = \App\Models\Rekening::find($rekeningId);
                if (!$rekeningItem) {
                    continue;
                }

                // 1 item repeater = 1 record jurnal pada tabel utama.
                $jurnal = JurnalBayarKasBank::create([
                    'no_voucher' => $data['no_voucher'] ?? null,
                    'bukti' => $data['no_voucher'] ?? null,
                    'tanggal' => $data['tanggal_check'],
                    'tanggal_check' => $data['tanggal_check'],
                    'no_reff' => '4',
                    'kelompok_id' => $rekeningItem->kelompok_id,
                    'rekening_id' => $rekeningId,
                    'nomor_bantu_id' => $nomorBantuId,
                    'nama_bank' => $data['nama_bank'] ?? null,
                    'no_cek' => $data['no_cek'] ?? null,
                    'beban_bagian' => $data['beban_bagian'] ?? null,
                    'dibayar_kepada' => $data['dibayar_kepada'] ?? null,
                    'rp' => $jumlah,
                    'keterangan' => $item['keterangan'] ?? 'Jurnal Bayar Kas/Bank',
                    'kode' => 'D',
                    'item_sequence' => $index + 1,
                    'company_id' => 1,
                    'created_by' => auth()->id(),
                    'is_confirmed' => false,
                ]);

                // Simpan akun lawan (kas/bank) sebagai detail agar posting tetap seimbang.
                \App\Models\JurnalBayarKasBankDetail::create([
                    'jurnal_bayar_kas_bank_id' => $jurnal->id,
                    'no_voucher' => $jurnal->no_voucher,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jumlah' => $jumlah,
                    'dibayar_kepada' => $jurnal->dibayar_kepada,
                    'kelompok_id' => $bankRekening->kelompok_id,
                    'rekening_id' => $bankRekeningId,
                    'nomor_bantu_id' => $bankNomorBantuId,
                    'kode_proyek_id' => $kodeProyekId,
                ]);

                $createdJournals[] = $jurnal;
            }

            if (empty($createdJournals)) {
                throw new \Exception('Tidak ada item jurnal yang valid untuk disimpan.');
            }

            return $createdJournals[0];
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
