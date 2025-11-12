<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat Profil Perusahaan'),
            // Remove delete action - company profile cannot be deleted
        ];
    }

    protected function getRedirectUrl(): string
    {
        // After editing, redirect back to view page
        return CompanyResource::getUrl('view', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return 'Edit Profil Perusahaan';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Profil perusahaan berhasil diperbarui!';
    }
}
