<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJurnalMemorial extends ViewRecord
{
    protected static string $resource = JurnalMemorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn($record) => !$record->is_confirmed),
        ];
    }
}
