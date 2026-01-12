<?php

namespace App\Filament\Accounting\Resources\KodeProyekResource\Pages;

use App\Filament\Accounting\Resources\KodeProyekResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKodeProyek extends EditRecord
{
    protected static string $resource = KodeProyekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
