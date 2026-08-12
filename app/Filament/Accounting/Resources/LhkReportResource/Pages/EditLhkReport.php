<?php

namespace App\Filament\Accounting\Resources\LhkReportResource\Pages;

use App\Filament\Accounting\Resources\LhkReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLhkReport extends EditRecord
{
    protected static string $resource = LhkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
