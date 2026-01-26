<?php

namespace App\Filament\Accounting\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Accounting\Resources\JurnalRekeningAirResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJurnalRekeningAir extends EditRecord
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn() => $this->record->canBeEdited()),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalRekeningAir->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => !$record->jurnalRekeningAir->is_confirmed && auth()->user()->can('confirm', $record->jurnalRekeningAir)),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(function ($record) {
                    $record->jurnalRekeningAir->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => $record->jurnalRekeningAir->is_confirmed && !$record->jurnalRekeningAir->is_posted && auth()->user()->can('unconfirm', $record->jurnalRekeningAir)),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalRekeningAir);
                        Notification::make()
                            ->title('Jurnal berhasil diposting ke Buku Besar')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal posting')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn($record) => $record->jurnalRekeningAir->is_confirmed && !$record->jurnalRekeningAir->is_posted),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validasi balance dan hitung total dari items
        if (isset($data['rekening_air_items'])) {
            $totalDebit = collect($data['rekening_air_items'])
                ->where('position', 'debit')
                ->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));

            $totalKredit = collect($data['rekening_air_items'])
                ->where('position', 'kredit')
                ->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));

            // Validasi balance
            if ($totalDebit !== $totalKredit) {
                throw new \Exception("Jurnal tidak balance! Total Debit: Rp " . number_format($totalDebit, 0, ',', '.') .
                    " | Total Kredit: Rp " . number_format($totalKredit, 0, ',', '.'));
            }

            // Validasi minimal ada 1 debit dan 1 kredit
            if ($totalDebit == 0 || $totalKredit == 0) {
                throw new \Exception("Jurnal harus memiliki minimal 1 item Debit dan 1 item Kredit!");
            }

            $data['rp'] = $totalDebit; // atau totalKredit, karena sudah balance
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Jurnal Rekening Air berhasil diperbarui';
    }
}
