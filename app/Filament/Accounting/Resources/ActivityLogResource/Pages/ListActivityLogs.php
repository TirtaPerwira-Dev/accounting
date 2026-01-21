<?php

namespace App\Filament\Accounting\Resources\ActivityLogResource\Pages;

use App\Filament\Accounting\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
