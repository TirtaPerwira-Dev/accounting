<?php

namespace App\Filament\Admin\Resources\DokumentasiResource\Pages;

use App\Filament\Admin\Resources\DokumentasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDokumentasi extends EditRecord
{
    protected static string $resource = DokumentasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
