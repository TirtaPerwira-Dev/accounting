<?php

namespace App\Filament\Resources\AccountingStandardResource\Pages;

use App\Filament\Resources\AccountingStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountingStandard extends ViewRecord
{
    protected static string $resource = AccountingStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->before(function () {
                    if ($this->record->companies()->exists()) {
                        $this->halt();

                        \Filament\Notifications\Notification::make()
                            ->title('Cannot delete')
                            ->body('This standard is being used by companies.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
