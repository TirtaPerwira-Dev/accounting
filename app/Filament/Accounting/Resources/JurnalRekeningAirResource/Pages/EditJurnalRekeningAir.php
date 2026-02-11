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
                ->visible(fn($record) => $record->jurnalRekeningAir->canBeEdited()),

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $header = $this->record->jurnalRekeningAir;

        $data['bukti'] = $header->bukti;
        $data['tanggal'] = $header->tanggal;
        $data['keterangan'] = $header->keterangan;
        $data['no_reff'] = $header->no_reff;

        $data['rekening_air_items'] = $header->details->map(function ($detail) {
            return [
                'rekening_id' => $detail->rekening_id,
                'nomor_bantu_id' => $detail->nomor_bantu_id,
                'kode_proyek_id' => $detail->kode_proyek_id,
                'position' => $detail->position,
                'jumlah' => $detail->jumlah,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $items = $data['rekening_air_items'] ?? [];
            unset($data['rekening_air_items']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item transaksi');
            }

            $header = $record->jurnalRekeningAir;

            // Hitung total debit dan kredit untuk validasi
            $totalDebit = collect($items)->where('position', 'debit')->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
            $totalKredit = collect($items)->where('position', 'kredit')->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

            // Validasi balance
            if (number_format($totalDebit, 2) !== number_format($totalKredit, 2)) {
                throw new \Exception('Jurnal tidak balance! Total Debit: Rp ' . number_format($totalDebit, 0, ',', '.') . ', Total Kredit: Rp ' . number_format($totalKredit, 0, ',', '.'));
            }

            // Update Header
            $header->update([
                'bukti' => $data['bukti'],
                'tanggal' => $data['tanggal'],
                'keterangan' => $data['keterangan'],
                'rp' => $totalDebit,
            ]);

            // Delete existing details
            $header->details()->delete();

            // Re-create details
            $newDetails = [];
            foreach ($items as $item) {
                $rekening = \App\Models\Rekening::find($item['rekening_id']);

                $newDetails[] = \App\Models\JurnalRekeningAirDetail::create([
                    'jurnal_rekening_air_id' => $header->id,
                    'kelompok_id' => $rekening?->kelompok_id,
                    'rekening_id' => $item['rekening_id'],
                    'nomor_bantu_id' => $item['nomor_bantu_id'] ?? null,
                    'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                    'position' => $item['position'],
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                ]);
            }

            return $newDetails[0];
        });
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Jurnal Rekening Air berhasil diperbarui';
    }
}
