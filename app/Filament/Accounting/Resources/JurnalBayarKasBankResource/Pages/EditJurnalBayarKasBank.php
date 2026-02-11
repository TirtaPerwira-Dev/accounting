<?php

namespace App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJurnalBayarKasBank extends EditRecord
{
    protected static string $resource = JurnalBayarKasBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $header = $record->jurnalBayarKasBank;
                    $header->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => !$record->jurnalBayarKasBank->is_confirmed && auth()->user()->can('confirm', $record->jurnalBayarKasBank)),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(function ($record) {
                    $header = $record->jurnalBayarKasBank;
                    $header->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => $record->jurnalBayarKasBank->is_confirmed && !$record->jurnalBayarKasBank->is_posted && auth()->user()->can('unconfirm', $record->jurnalBayarKasBank)),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalBayarKasBank);
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
                ->visible(fn($record) => $record->jurnalBayarKasBank->is_confirmed && !$record->jurnalBayarKasBank->is_posted),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $header = $this->record->jurnalBayarKasBank;

        $data['no_voucher'] = $header->no_voucher;
        $data['tanggal_check'] = $header->tanggal_check;
        $data['rekening_bank_id'] = $header->rekening_id . '|' . ($header->nomor_bantu_id ?? '0');
        $data['rekening_id'] = $header->rekening_id;
        $data['nomor_bantu_id'] = $header->nomor_bantu_id;
        $data['no_cek'] = $header->no_cek;
        $data['beban_bagian'] = $header->beban_bagian;
        $data['dibayar_kepada'] = $header->dibayar_kepada;

        $data['pembayaran_items'] = $header->details->map(function ($detail) {
            return [
                'rekening_id' => $detail->rekening_id,
                'nomor_bantu_id' => $detail->nomor_bantu_id,
                'kode_proyek_id' => $detail->kode_proyek_id,
                'jumlah' => $detail->jumlah,
                'keterangan' => $detail->keterangan,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $items = $data['pembayaran_items'] ?? [];
            unset($data['pembayaran_items']);

            if (empty($items)) {
                throw new \Exception('Minimal harus ada 1 item pembayaran');
            }

            $header = $record->jurnalBayarKasBank;

            // Hitung total
            $totalRp = collect($items)->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

            // Update Header
            $header->update([
                'no_voucher' => $data['no_voucher'],
                'tanggal' => $data['tanggal_check'],
                'tanggal_check' => $data['tanggal_check'],
                'rekening_id' => $data['rekening_id'],
                'nomor_bantu_id' => $data['nomor_bantu_id'],
                'no_cek' => $data['no_cek'],
                'beban_bagian' => $data['beban_bagian'],
                'dibayar_kepada' => $data['dibayar_kepada'],
                'rp' => $totalRp,
                'keterangan' => $items[0]['keterangan'] ?? $header->keterangan,
            ]);

            // Delete existing details
            $header->details()->delete();

            // Re-create details
            $newDetails = [];
            foreach ($items as $item) {
                $rekening = \App\Models\Rekening::find($item['rekening_id']);

                $newDetails[] = \App\Models\JurnalBayarKasBankDetail::create([
                    'jurnal_bayar_kas_bank_id' => $header->id,
                    'no_voucher' => $header->no_voucher,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'dibayar_kepada' => $header->dibayar_kepada,
                    'kelompok_id' => $rekening?->kelompok_id,
                    'rekening_id' => $item['rekening_id'] ?? null,
                    'nomor_bantu_id' => $item['nomor_bantu_id'] ?? null,
                    'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                ]);
            }

            return $newDetails[0];
        });
    }
}
