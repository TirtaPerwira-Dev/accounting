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
            // Get rekening info untuk debit
            $rekeningDebit = null;
            if (!empty($item['rekening_id'])) {
                $rekeningDebit = Rekening::with('kelompok')->find($item['rekening_id']);
            }

            $debit = (float) ($item['debit'] ?? 0);
            $kredit = (float) ($item['kredit'] ?? 0);

            // Prepare data untuk setiap record
            $recordData = array_merge($data, [
                'bukti' => $bukti,
                'rekening_debit_id' => $item['rekening_id'] ?? null,
                'kelompok_debit_id' => $rekeningDebit?->kelompok_id ?? null,
                'nomor_bantu_debit_id' => $item['nomor_bantu_id'] ?? null,
                'rekening_kredit_id' => $item['rekening_id'] ?? null,
                'kelompok_kredit_id' => $rekeningDebit?->kelompok_id ?? null,
                'nomor_bantu_kredit_id' => $item['nomor_bantu_id'] ?? null,
                'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                'rp' => $debit > 0 ? $debit : $kredit,
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

