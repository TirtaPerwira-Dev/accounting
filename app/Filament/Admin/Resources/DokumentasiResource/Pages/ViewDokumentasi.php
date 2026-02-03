<?php

namespace App\Filament\Admin\Resources\DokumentasiResource\Pages;

use App\Filament\Admin\Resources\DokumentasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDokumentasi extends ViewRecord
{
    protected static string $resource = DokumentasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
