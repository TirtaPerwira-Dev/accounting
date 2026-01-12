<?php

namespace App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Accounting\Resources\JurnalPenerimaanKasResource;
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
