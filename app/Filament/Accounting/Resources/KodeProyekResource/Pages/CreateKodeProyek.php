<?php

namespace App\Filament\Accounting\Resources\KodeProyekResource\Pages;

use App\Filament\Accounting\Resources\KodeProyekResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKodeProyek extends CreateRecord
{
    protected static string $resource = KodeProyekResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
