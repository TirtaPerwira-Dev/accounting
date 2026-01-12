<?php

namespace App\Filament\Accounting\Resources\OpeningBalanceResource\Pages;

use App\Filament\Accounting\Resources\OpeningBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOpeningBalances extends ListRecords
{
    protected static string $resource = OpeningBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
