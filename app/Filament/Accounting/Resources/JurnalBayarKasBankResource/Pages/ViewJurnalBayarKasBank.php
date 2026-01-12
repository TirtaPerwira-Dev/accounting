<?php

namespace App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJurnalBayarKasBank extends ViewRecord
{
    protected static string $resource = JurnalBayarKasBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn($record) => !$record->is_confirmed),
        ];
    }
}
