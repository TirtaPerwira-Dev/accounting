<?php

namespace App\Filament\Resources\OpeningBalanceResource\Pages;

use App\Filament\Resources\OpeningBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\NomorBantu;

class CreateOpeningBalance extends CreateRecord
{
    protected static string $resource = OpeningBalanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set created_by
        $data['created_by'] = Auth::id();

        // Auto-populate SAKEP hierarchy from nomor_bantu_id
        if (isset($data['nomor_bantu_id'])) {
            $nomorBantu = NomorBantu::with(['rekening.kelompok'])->find($data['nomor_bantu_id']);

            if ($nomorBantu) {
                $data['kelompok_id'] = $nomorBantu->rekening->kelompok_id;
                $data['rekening_id'] = $nomorBantu->rekening_id;
            }
        }

        // Validate that only debit OR credit is filled
        $debit = (float) ($data['debit_balance'] ?? 0);
        $credit = (float) ($data['credit_balance'] ?? 0);

        if ($debit > 0 && $credit > 0) {
            throw new \Exception('Tidak boleh mengisi Debit dan Kredit bersamaan. Pilih salah satu saja.');
        }

        if ($debit == 0 && $credit == 0) {
            throw new \Exception('Harus mengisi salah satu: Debit atau Kredit.');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
