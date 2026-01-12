<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use App\Models\JurnalMemorial;
use App\Models\Rekening;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CreateJurnalMemorial extends CreateRecord
{
    protected static string $resource = JurnalMemorialResource::class;

    protected function handleRecordCreation(array $data): JurnalMemorial
    {
        // Extract repeater items
        $details = $data['details'] ?? [];
        unset($data['details']);

        if (empty($details)) {
            throw new \Exception('Minimal harus ada 1 item memorial');
        }

        // Generate group transaksi ID jika lebih dari 1 item
        $groupTransaksi = count($details) > 1 ? Str::uuid()->toString() : null;

        // Generate no_bukti sekali
        $bukti = $data['bukti'] ?? 'MEM-' . rand(100, 999);

        $createdRecords = [];

        foreach ($details as $index => $item) {
            // Get rekening info
            $rekening = null;
            if (!empty($item['rekening_id'])) {
                $rekening = Rekening::with('kelompok')->find($item['rekening_id']);
            }

            $debit = (float) ($item['debit'] ?? 0);
            $kredit = (float) ($item['kredit'] ?? 0);

            // Prepare data untuk setiap record
            $recordData = array_merge($data, [
                'bukti' => $bukti,
                'rekening_id' => $item['rekening_id'] ?? null,
                'kelompok_id' => $rekening?->kelompok_id ?? null,
                'nomor_bantu_id' => $item['nomor_bantu_id'] ?? null,
                'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                'rp' => $debit > 0 ? $debit : $kredit,
                'kode' => $debit > 0 ? 'D' : 'K',
                'keterangan' => $item['keterangan'] ?? '',
                'group_transaksi' => $groupTransaksi,
                'item_sequence' => $index + 1,
                'created_by' => Auth::id(),
                'is_confirmed' => false,
            ]);

            // Remove field yang tidak ada di table
            unset($recordData['total_debit'], $recordData['total_kredit'], $recordData['selisih']);

            $record = JurnalMemorial::create($recordData);
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
