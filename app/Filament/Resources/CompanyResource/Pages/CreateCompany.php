<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    public function mount(): void
    {
        // Prevent access if company already exists
        if (Company::count() > 0) {
            Notification::make()
                ->title('Company Already Exists')
                ->body('Only one company profile is allowed. Please edit the existing company.')
                ->warning()
                ->send();

            $company = Company::first();
            $this->redirect(CompanyResource::getUrl('view', ['record' => $company]));
            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        // After creating, redirect to view page
        return CompanyResource::getUrl('view', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return 'Setup Company Profile';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Company profile created successfully!';
    }
}
