<?php

namespace App\Filament\Accounting\Resources\KodeProyekResource\Pages;

use App\Filament\Accounting\Resources\KodeProyekResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKodeProyeks extends ListRecords
{
    protected static string $resource = KodeProyekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
