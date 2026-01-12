<?php

namespace App\Filament\Accounting\Resources\SaldoAwalJurnalResource\Pages;

use App\Filament\Accounting\Resources\SaldoAwalJurnalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSaldoAwalJurnal extends CreateRecord
{
    protected static string $resource = SaldoAwalJurnalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
