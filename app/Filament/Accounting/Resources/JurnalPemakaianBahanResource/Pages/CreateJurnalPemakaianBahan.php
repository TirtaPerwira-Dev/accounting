<?php

namespace App\Filament\Accounting\Resources\JurnalPemakaianBahanResource\Pages;

use App\Filament\Accounting\Resources\JurnalPemakaianBahanResource;
use App\Models\JurnalPemakaianBahan;
use App\Models\Rekening;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class CreateJurnalPemakaianBahan extends CreateRecord
{
    protected static string $resource = JurnalPemakaianBahanResource::class;

    #[On('remove-jpbik-item')]
    public function removeJpbikItem($index)
    {
        $data = $this->form->getState();
        $items = $data['detail_rekening'] ?? [];

        if (isset($items[$index])) {
            unset($items[$index]);
            $items = array_values($items); // Reindex array
            $this->form->fill(['detail_rekening' => $items]);
        }
    }

    protected function handleRecordCreation(array $data): JurnalPemakaianBahan
    {
        // Extract items from detail_rekening
        $items = $data['detail_rekening'] ?? [];
        unset($data['detail_rekening']);
        unset($data['items_completed']);
        unset($data['temp_rekening']);
        unset($data['temp_nomor_bantu']);
        unset($data['temp_kode_proyek']);
        unset($data['temp_position']);
        unset($data['temp_jumlah']);
        unset($data['temp_keterangan']);

        if (empty($items)) {
            throw new \Exception('Minimal harus ada 1 item pemakaian bahan');
        }

        // Generate group transaksi ID jika lebih dari 1 item
        $groupTransaksi = count($items) > 1 ? Str::uuid()->toString() : null;

        // Generate no_bukti sekali
        $bukti = $data['bukti'] ?? 'JPBIK-' . rand(100, 999);

        $createdRecords = [];

        foreach ($items as $index => $item) {
            // Get rekening info
            $rekening = null;
            if (!empty($item['rekening'])) {
                $rekening = Rekening::with('kelompok')->find($item['rekening']);
            }

            $position = $item['position'] ?? 'debit';
            $jumlah = (float) ($item['jumlah'] ?? 0);

            // Tentukan debit/kredit berdasarkan position
            if ($position === 'debit') {
                $debitId = $item['rekening'] ?? null;
                $kreditId = null;
                $debitNomor = $item['nomor_bantu'] ?? null;
                $kreditNomor = null;
            } else {
                $debitId = null;
                $kreditId = $item['rekening'] ?? null;
                $debitNomor = null;
                $kreditNomor = $item['nomor_bantu'] ?? null;
            }

            // Prepare data untuk setiap record
            $recordData = array_merge($data, [
                'bukti' => $bukti,
                'rekening_debit_id' => $debitId,
                'kelompok_debit_id' => $position === 'debit' ? $rekening?->kelompok_id : null,
                'nomor_bantu_debit_id' => $debitNomor,
                'rekening_kredit_id' => $kreditId,
                'kelompok_kredit_id' => $position === 'kredit' ? $rekening?->kelompok_id : null,
                'nomor_bantu_kredit_id' => $kreditNomor,
                'kode_proyek_id' => $item['kode_proyek'] ?? null,
                'rp' => $jumlah,
                'keterangan' => $item['keterangan'] ?? '',
                'group_transaksi' => $groupTransaksi,
                'item_sequence' => $index + 1,
                'created_by' => Auth::id(),
                'is_confirmed' => false,
            ]);

            // Remove field yang tidak ada di table
            unset($recordData['total_debit'], $recordData['total_kredit'], $recordData['selisih']);

            $record = JurnalPemakaianBahan::create($recordData);
            $createdRecords[] = $record;
        }

        // Return record pertama sebagai representasi grup
        return $createdRecords[0];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
