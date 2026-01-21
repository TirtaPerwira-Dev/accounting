<?php

namespace App\Filament\Accounting\Resources\KodeProyekResource\Pages;

use App\Filament\Accounting\Resources\KodeProyekResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKodeProyek extends CreateRecord
{
    protected static string $resource = KodeProyekResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
