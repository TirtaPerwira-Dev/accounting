<?php

namespace App\Filament\Resources\PengeluaranJournalResource\Pages;

use App\Filament\Resources\PengeluaranJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengeluaranJournals extends ListRecords
{
    protected static string $resource = PengeluaranJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PengeluaranJournalResource\Widgets\PengeluaranStatsWidget::class,
        ];
    }
}
