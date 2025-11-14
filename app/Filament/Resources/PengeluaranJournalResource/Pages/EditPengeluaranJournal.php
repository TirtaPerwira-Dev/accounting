<?php

namespace App\Filament\Resources\PengeluaranJournalResource\Pages;

use App\Filament\Resources\PengeluaranJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengeluaranJournal extends EditRecord
{
    protected static string $resource = PengeluaranJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
