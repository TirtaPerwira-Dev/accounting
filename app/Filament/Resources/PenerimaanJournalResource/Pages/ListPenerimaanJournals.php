<?php

namespace App\Filament\Resources\PenerimaanJournalResource\Pages;

use App\Filament\Resources\PenerimaanJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenerimaanJournals extends ListRecords
{
    protected static string $resource = PenerimaanJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PenerimaanJournalResource\Widgets\PenerimaanStatsWidget::class,
        ];
    }
}
