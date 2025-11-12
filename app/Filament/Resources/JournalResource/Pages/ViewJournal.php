<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJournal extends ViewRecord
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->status === 'draft'),

            Actions\Action::make('post')
                ->label('Post Journal')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->status === 'draft' && $this->record->isBalanced())
                ->requiresConfirmation()
                ->modalHeading('Post Journal')
                ->modalSubheading('Are you sure you want to post this journal? This action cannot be undone.')
                ->action(function () {
                    $this->record->post();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->successNotificationTitle('Journal posted successfully'),
        ];
    }
}
