<?php

namespace App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Accounting\Resources\JurnalPenerimaanKasResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJurnalPenerimaanKas extends EditRecord
{
    protected static string $resource = JurnalPenerimaanKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn($record) => !$record->jurnalPenerimaanKas->is_posted && auth()->user()->can('postToLedger', $record->jurnalPenerimaanKas)),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalPenerimaanKas->confirm();
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
                    $record->jurnalPenerimaanKas->unconfirm();
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
                        $service->post($record->jurnalPenerimaanKas);
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
                ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_posted && auth()->user()->can('postToLedger', $record->jurnalPenerimaanKas)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $header = $this->record->jurnalPenerimaanKas;

        $data['rekening_id'] = $header->rekening_id;
        $data['kas_bank_id'] = $header->kas_bank_id;
        $data['tanggal'] = $header->tanggal;
        $data['keterangan'] = $header->keterangan;
        $data['lampiran'] = $header->lampiran;
        $data['total_item_input'] = $header->total_item_input;
        $data['nominal_input'] = $header->nominal_input;

        $data['penerimaan_items'] = $header->details->map(function ($detail) {
            return [
                'nomor_bukti' => $detail->nomor_bukti,
                'kode_proyek_id' => $detail->kode_proyek_id,
                'rekening_id' => $detail->rekening_id,
                'nomor_bantu_id' => $detail->nomor_bantu_id,
                'jumlah' => $detail->jumlah,
                'keterangan_item' => $detail->keterangan_item,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $items = $data['penerimaan_items'] ?? [];
            unset($data['penerimaan_items']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item penerimaan');
            }

            $header = $record->jurnalPenerimaanKas;

            // Calculate total
            $totalAmount = collect($items)->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
            $totalItemInput = (int) preg_replace('/[^0-9]/', '', (string) ($data['total_item_input'] ?? '0'));
            $nominalInput = (float) preg_replace('/[^0-9]/', '', (string) ($data['nominal_input'] ?? '0'));

            // Update Header
            $header->update([
                'rekening_id' => $data['rekening_id'],
                'kas_bank_id' => $data['kas_bank_id'],
                'tanggal' => $data['tanggal'],
                'nomor_bukti' => $items[0]['nomor_bukti'] ?? $header->nomor_bukti,
                'keterangan' => $data['keterangan'],
                'lampiran' => $data['lampiran'] ?? null,
                'total_item_input' => $totalItemInput,
                'nominal_input' => $nominalInput,
                'total_amount' => $totalAmount,
            ]);

            // Delete existing details
            $header->details()->delete();

            // Re-create details
            $newDetails = [];
            foreach ($items as $item) {
                $rekening = \App\Models\Rekening::find($item['rekening_id']);

                $newDetails[] = \App\Models\JurnalPenerimaanKasDetail::create([
                    'jurnal_penerimaan_kas_id' => $header->id,
                    'nomor_bukti' => $item['nomor_bukti'] ?? null,
                    'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                    'kelompok_id' => $rekening?->kelompok_id,
                    'rekening_id' => $item['rekening_id'],
                    'nomor_bantu_id' => $item['nomor_bantu_id'] ?? null,
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'keterangan_item' => $item['keterangan_item'] ?? null,
                ]);
            }

            return $newDetails[0];
        });
    }
}
