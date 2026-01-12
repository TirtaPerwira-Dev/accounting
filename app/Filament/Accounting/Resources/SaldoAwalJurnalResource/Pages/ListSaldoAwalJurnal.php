<?php

namespace App\Filament\Accounting\Resources\SaldoAwalJurnalResource\Pages;

use App\Filament\Accounting\Resources\SaldoAwalJurnalResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListSaldoAwalJurnal extends ListRecords
{
    protected static string $resource = SaldoAwalJurnalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
