<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Profil Perusahaan'),

            Actions\Action::make('initialize_sakep')
                ->label('Inisialisasi SAKEP')
                ->icon('heroicon-o-squares-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Inisialisasi Struktur SAKEP')
                ->modalDescription('Ini akan membuat struktur SAKEP berdasarkan standar akuntansi yang dipilih.')
                ->visible(
                    fn() =>
                    $this->record->accounting_standard_id &&
                        !$this->record->isSakepInitialized()
                )
                ->action(function () {
                    if ($this->record->initializeSakepStructure()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Struktur SAKEP Berhasil Diinisialisasi')
                            ->body('Struktur SAKEP telah dibuat dengan sukses.')
                            ->success()
                            ->send();

                        $this->redirect(request()->header('Referer'));
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Inisialisasi Gagal')
                            ->body('Tidak dapat menginisialisasi struktur SAKEP.')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('view_journals')
                ->label('Lihat Jurnal')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn() => '/admin/journals?company_id=' . $this->record->id)
                ->visible(fn() => $this->record->isSakepInitialized()),
        ];
    }

    public function getTitle(): string
    {
        return 'Profil Perusahaan: ' . $this->record->name;
    }
}
