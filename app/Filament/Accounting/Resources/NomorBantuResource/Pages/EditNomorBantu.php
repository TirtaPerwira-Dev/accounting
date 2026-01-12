<?php

namespace App\Filament\Accounting\Resources\NomorBantuResource\Pages;

use App\Filament\Accounting\Resources\NomorBantuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNomorBantu extends EditRecord
{
    protected static string $resource = NomorBantuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
