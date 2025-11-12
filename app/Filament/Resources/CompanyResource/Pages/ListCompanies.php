<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function mount(): void
    {
        parent::mount();

        // If company exists, redirect to view page instead of list
        $company = Company::first();
        if ($company) {
            $this->redirect(CompanyResource::getUrl('view', ['record' => $company]));
            return;
        }
    }

    protected function getHeaderActions(): array
    {
        // Only show create action if no company exists
        if (Company::count() === 0) {
            return [
                Actions\CreateAction::make()
                    ->label('Setup Profil Perusahaan'),
            ];
        }

        return [];
    }

    public function getTitle(): string
    {
        return Company::count() === 0 ? 'Setup Profil Perusahaan' : 'Profil Perusahaan';
    }
}
