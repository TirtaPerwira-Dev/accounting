<?php

namespace App\Filament\Accounting\Resources\JurnalKoreksiResource\Pages;

use App\Filament\Accounting\Resources\JurnalKoreksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJurnalKoreksis extends ListRecords
{
    protected static string $resource = JurnalKoreksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Jurnal Koreksi')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
