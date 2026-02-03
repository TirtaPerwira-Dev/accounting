<?php

namespace App\Filament\Accounting\Resources\DokumentasiResource\Pages;

use App\Filament\Accounting\Resources\DokumentasiResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDokumentasi extends ViewRecord
{
    protected static string $resource = DokumentasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak ada action untuk read-only
        ];
    }
}
