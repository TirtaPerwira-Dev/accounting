<?php

namespace App\Filament\Accounting\Resources\DokumentasiResource\Pages;

use App\Filament\Accounting\Resources\DokumentasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDokumentasis extends ListRecords
{
    protected static string $resource = DokumentasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak ada create action untuk read-only
        ];
    }
}
