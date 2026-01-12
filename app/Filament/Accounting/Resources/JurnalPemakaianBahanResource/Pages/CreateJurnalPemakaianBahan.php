<?php

namespace App\Filament\Accounting\Resources\JurnalPemakaianBahanResource\Pages;

use App\Filament\Accounting\Resources\JurnalPemakaianBahanResource;
use App\Models\JurnalPemakaianBahan;
use App\Models\Rekening;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CreateJurnalPemakaianBahan extends CreateRecord
{
    protected static string $resource = JurnalPemakaianBahanResource::class;

    protected function handleRecordCreation(array $data): JurnalPemakaianBahan
    {
        // Extract repeater items
        $details = $data['details'] ?? [];
        unset($data['details']);

        if (empty($details)) {
            throw new \Exception('Minimal harus ada 1 item pemakaian bahan');
        }

        // Generate group transaksi ID jika lebih dari 1 item
        $groupTransaksi = count($details) > 1 ? Str::uuid()->toString() : null;

        // Generate no_bukti sekali
        $bukti = $data['bukti'] ?? 'JPBIK-' . rand(100, 999);

        $createdRecords = [];

        foreach ($details as $index => $item) {
            // Get rekening info
            $rekening = null;
            if (!empty($item['rekening_id'])) {
                $rekening = Rekening::with('kelompok')->find($item['rekening_id']);
            }

            $position = $item['position'] ?? 'debit';
            $jumlah = (float) ($item['jumlah'] ?? 0);

            // Tentukan debit/kredit berdasarkan position
            if ($position === 'debit') {
                $debitId = $item['rekening_id'] ?? null;
                $kreditId = null;
                $debitNomor = $item['nomor_bantu_id'] ?? null;
                $kreditNomor = null;
            } else {
                $debitId = null;
                $kreditId = $item['rekening_id'] ?? null;
                $debitNomor = null;
                $kreditNomor = $item['nomor_bantu_id'] ?? null;
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
                'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
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
