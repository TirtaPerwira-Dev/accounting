<?php

namespace App\Filament\Resources\PenerimaanJournalResource\Pages;

use App\Filament\Resources\PenerimaanJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPenerimaanJournal extends ViewRecord
{
    protected static string $resource = PenerimaanJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->status === 'draft'),
        ];
    }
}
