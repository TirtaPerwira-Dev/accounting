<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJurnalMemorial extends EditRecord
{
    protected static string $resource = JurnalMemorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn($record) => $record->jurnalMemorial && !$record->jurnalMemorial->is_posted && !$record->jurnalMemorial->is_confirmed && auth()->user()->can('postToLedger', $record->jurnalMemorial)),
            Actions\ViewAction::make(),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalMemorial->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(false),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(function ($record) {
                    $record->jurnalMemorial->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(false),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalMemorial);
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
                ->visible(fn($record) => $record->jurnalMemorial && !$record->jurnalMemorial->is_posted && auth()->user()->can('postToLedger', $record->jurnalMemorial)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $header = $this->record->jurnalMemorial;

        $data['bukti'] = $header->bukti;
        $data['tanggal'] = $header->tanggal;
        $data['no_reff'] = $header->no_reff;

        $data['memorial_items'] = $header->details->map(function ($detail) {
            return [
                'rekening_id' => $detail->rekening_id,
                'nomor_bantu_id' => $detail->nomor_bantu_id,
                'kode_proyek_id' => $detail->kode_proyek_id,
                'posisi' => $detail->posisi,
                'jumlah' => $detail->jumlah,
                'keterangan' => $detail->keterangan,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $items = $data['memorial_items'] ?? [];
            unset($data['memorial_items']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item memorial');
            }

            $header = $record->jurnalMemorial;

            // Hitung total debit dan kredit untuk validasi
            $totalDebit = collect($items)->where('posisi', 'D')->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
            $totalKredit = collect($items)->where('posisi', 'K')->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

            // Validasi balance
            if (number_format($totalDebit, 2) !== number_format($totalKredit, 2)) {
                throw new \Exception('Jurnal tidak balance! Total Debit: Rp ' . number_format($totalDebit, 0, ',', '.') . ', Total Kredit: Rp ' . number_format($totalKredit, 0, ',', '.'));
            }

            // Update Header
            $header->update([
                'bukti' => $data['bukti'],
                'tanggal' => $data['tanggal'],
                'rp' => $totalDebit,
                'keterangan' => $items[0]['keterangan'] ?? $header->keterangan,
            ]);

            // Delete existing details
            $header->details()->delete();

            // Re-create details
            $newDetails = [];
            foreach ($items as $item) {
                $rekening = \App\Models\Rekening::find($item['rekening_id']);

                $newDetails[] = \App\Models\JurnalMemorialDetail::create([
                    'jurnal_memorial_id' => $header->id,
                    'bukti' => $header->bukti,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'posisi' => $item['posisi'],
                    'kelompok_id' => $rekening?->kelompok_id,
                    'rekening_id' => $item['rekening_id'],
                    'nomor_bantu_id' => $item['nomor_bantu_id'] ?? null,
                    'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                ]);
            }

            return $newDetails[0];
        });
    }
}
