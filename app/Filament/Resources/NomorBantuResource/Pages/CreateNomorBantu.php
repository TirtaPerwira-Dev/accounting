<?php

namespace App\Filament\Resources\NomorBantuResource\Pages;

use App\Filament\Resources\NomorBantuResource;
use App\Models\NomorBantu;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Log;

class CreateNomorBantu extends CreateRecord
{
    protected static string $resource = NomorBantuResource::class;

    public function form(Form $form): Form
    {
        return NomorBantuResource::createForm($form);
    }

    protected function handleRecordCreation(array $data): NomorBantu
    {
        // Karena menggunakan repeater, kita perlu loop untuk create multiple records
        // Tapi Filament CreateRecord expect single model, jadi kita create yang pertama
        // dan tambahkan logic untuk yang lainnya

        $nomorBantus = $data['nomor_bantus'] ?? [];
        $baseRekeningId = $data['base_rekening_id'];

        // Ambil data rekening untuk mendapatkan kel dan kode
        $rekening = \App\Models\Rekening::with('kelompok')->find($baseRekeningId);
        if (!$rekening) {
            throw new \Exception('Rekening tidak ditemukan');
        }

        // Debug: Mari kita lihat data kelompok dan rekening
        Log::info('Rekening data:', [
            'rekening_id' => $rekening->id,
            'rekening_kode' => $rekening->kode,
            'kelompok_id' => $rekening->kelompok->id,
            'kelompok_kel' => $rekening->kelompok->kel,
        ]);

        $baseKel = $rekening->kelompok->kel;
        $baseKode = $rekening->kode; // Ambil kode dari rekening, bukan kelompok

        // Pastikan kode tidak null, berikan default jika perlu
        if (empty($baseKode)) {
            // Default berdasarkan kategori kelompok
            $baseKode = in_array($baseKel, [1, 3, 4, 5, 6]) ? 'D' : 'K';
        }

        // Create record pertama untuk return value
        $firstRecord = null;

        foreach ($nomorBantus as $index => $nomorBantu) {
            $recordData = [
                'rekening_id' => $baseRekeningId,
                'no_bantu' => $nomorBantu['no_bantu'],
                'nm_bantu' => $nomorBantu['nm_bantu'],
                'kel' => $baseKel,
                'kode' => $baseKode,
            ];

            Log::info('Creating record with data:', $recordData);

            $record = NomorBantu::create($recordData);

            // Set first record sebagai return value
            if ($index === 0) {
                $firstRecord = $record;
            }
        }

        return $firstRecord ?? new NomorBantu();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Nomor bantu berhasil ditambahkan';
    }
}
