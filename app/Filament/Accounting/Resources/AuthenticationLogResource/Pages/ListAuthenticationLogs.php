<?php

namespace App\Filament\Accounting\Resources\AuthenticationLogResource\Pages;

use App\Filament\Accounting\Resources\AuthenticationLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuthenticationLogs extends ListRecords
{
    protected static string $resource = AuthenticationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
