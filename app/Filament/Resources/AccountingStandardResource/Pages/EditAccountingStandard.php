<?php

namespace App\Filament\Resources\AccountingStandardResource\Pages;

use App\Filament\Resources\AccountingStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingStandard extends EditRecord
{
    protected static string $resource = AccountingStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
