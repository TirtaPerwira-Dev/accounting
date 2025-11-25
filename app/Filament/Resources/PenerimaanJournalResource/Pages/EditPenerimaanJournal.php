<?php

namespace App\Filament\Resources\PenerimaanJournalResource\Pages;

use App\Filament\Resources\PenerimaanJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenerimaanJournal extends EditRecord
{
    protected static string $resource = PenerimaanJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
