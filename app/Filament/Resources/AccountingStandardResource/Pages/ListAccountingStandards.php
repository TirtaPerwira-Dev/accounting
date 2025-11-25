<?php

namespace App\Filament\Resources\AccountingStandardResource\Pages;

use App\Filament\Resources\AccountingStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingStandards extends ListRecords
{
    protected static string $resource = AccountingStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
