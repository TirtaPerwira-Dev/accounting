<?php

namespace App\Filament\Resources\OpeningBalanceResource\Pages;

use App\Filament\Resources\OpeningBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\NomorBantu;

class EditOpeningBalance extends EditRecord
{
    protected static string $resource = OpeningBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => !$this->record->is_confirmed),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

        // If confirmed, unconfirm it since we're making changes
        if ($this->record->is_confirmed) {
            $data['is_confirmed'] = false;
            $data['confirmed_by'] = null;
            $data['confirmed_at'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
