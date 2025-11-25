<?php

namespace App\Filament\Resources\NomorBantuResource\Pages;

use App\Filament\Resources\NomorBantuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNomorBantus extends ListRecords
{
    protected static string $resource = NomorBantuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
