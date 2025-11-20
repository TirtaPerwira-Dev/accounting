<?php

namespace App\Filament\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Resources\JurnalPenerimaanKasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJurnalPenerimaanKas extends EditRecord
{
    protected static string $resource = JurnalPenerimaanKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
