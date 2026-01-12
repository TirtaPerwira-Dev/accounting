<?php

namespace App\Filament\Accounting\Resources\SaldoAwalJurnalResource\Pages;

use App\Filament\Accounting\Resources\SaldoAwalJurnalResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditSaldoAwalJurnal extends EditRecord
{
    protected static string $resource = SaldoAwalJurnalResource::class;

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
