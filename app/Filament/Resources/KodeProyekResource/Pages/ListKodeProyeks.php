<?php

namespace App\Filament\Resources\KodeProyekResource\Pages;

use App\Filament\Resources\KodeProyekResource;
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
