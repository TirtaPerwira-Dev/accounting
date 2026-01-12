<?php

namespace App\Filament\Accounting\Resources\JurnalPemakaianBahanResource\Pages;

use App\Filament\Accounting\Resources\JurnalPemakaianBahanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJurnalPemakaianBahan extends ViewRecord
{
    protected static string $resource = JurnalPemakaianBahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn($record) => !$record->is_confirmed),
        ];
    }
}
