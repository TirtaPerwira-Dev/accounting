<?php

namespace App\Filament\Accounting\Resources\AuthenticationLogResource\Pages;

use App\Filament\Accounting\Resources\AuthenticationLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuthenticationLog extends ViewRecord
{
    protected static string $resource = AuthenticationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
