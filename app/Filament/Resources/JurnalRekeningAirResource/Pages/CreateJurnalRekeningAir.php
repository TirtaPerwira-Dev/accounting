<?php

namespace App\Filament\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Resources\JurnalRekeningAirResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJurnalRekeningAir extends CreateRecord
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validasi balance dan hitung total dari items
        if (isset($data['rekening_air_items'])) {
            $totalDebit = collect($data['rekening_air_items'])
                ->where('position', 'debit')
                ->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));

            $totalKredit = collect($data['rekening_air_items'])
                ->where('position', 'kredit')
                ->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));

            // Validasi balance
            if ($totalDebit !== $totalKredit) {
                throw new \Exception("Jurnal tidak balance! Total Debit: Rp " . number_format($totalDebit, 0, ',', '.') .
                    " | Total Kredit: Rp " . number_format($totalKredit, 0, ',', '.'));
            }

            // Validasi minimal ada 1 debit dan 1 kredit
            if ($totalDebit == 0 || $totalKredit == 0) {
                throw new \Exception("Jurnal harus memiliki minimal 1 item Debit dan 1 item Kredit!");
            }

            $data['rp'] = $totalDebit; // atau totalKredit, karena sudah balance
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jurnal Rekening Air berhasil dibuat';
    }
}
