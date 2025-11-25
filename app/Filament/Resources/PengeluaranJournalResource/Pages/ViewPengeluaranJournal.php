<?php

namespace App\Filament\Resources\PengeluaranJournalResource\Pages;

use App\Filament\Resources\PengeluaranJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPengeluaranJournal extends ViewRecord
{
    protected static string $resource = PengeluaranJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->status === 'draft'),
        ];
    }
}
