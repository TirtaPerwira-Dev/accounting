<?php

namespace App\Filament\Accounting\Resources\SaldoAwalRekeningResource\Pages;

use App\Filament\Accounting\Resources\SaldoAwalRekeningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaldoAwalRekening extends EditRecord
{
    protected static string $resource = SaldoAwalRekeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
