<?php

namespace App\Filament\Accounting\Resources\SaldoAwalRekeningResource\Pages;

use App\Filament\Accounting\Resources\SaldoAwalRekeningResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSaldoAwalRekening extends CreateRecord
{
    protected static string $resource = SaldoAwalRekeningResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
