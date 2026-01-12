<?php

namespace App\Filament\Accounting\Resources\RekeningResource\Pages;

use App\Filament\Accounting\Resources\RekeningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRekening extends EditRecord
{
    protected static string $resource = RekeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
