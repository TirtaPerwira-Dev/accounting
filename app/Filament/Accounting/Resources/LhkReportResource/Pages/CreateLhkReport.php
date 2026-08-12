<?php

namespace App\Filament\Accounting\Resources\LhkReportResource\Pages;

use App\Filament\Accounting\Resources\LhkReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLhkReport extends CreateRecord
{
    protected static string $resource = LhkReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}