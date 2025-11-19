<?php

namespace App\Filament\Resources\JurnalPembelianResource\Pages;

use App\Filament\Resources\JurnalPembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJurnalPembelian extends EditRecord
{
    protected static string $resource = JurnalPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
