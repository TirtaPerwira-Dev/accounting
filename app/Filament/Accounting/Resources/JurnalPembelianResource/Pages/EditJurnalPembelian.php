<?php

namespace App\Filament\Accounting\Resources\JurnalPembelianResource\Pages;

use App\Filament\Accounting\Resources\JurnalPembelianResource;
use App\Models\JurnalPembelian;
use App\Models\NomorBantu;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EditJurnalPembelian extends EditRecord
{
    protected static string $resource = JurnalPembelianResource::class;

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
                    $header = $record->jurnalPembelian;
                    $header->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => !$record->jurnalPembelian->is_confirmed && auth()->user()->can('confirm', $record->jurnalPembelian)),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(function ($record) {
                    $header = $record->jurnalPembelian;
                    $header->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => $record->jurnalPembelian->is_confirmed && !$record->jurnalPembelian->is_posted && auth()->user()->can('unconfirm', $record->jurnalPembelian)),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalPembelian);
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
                ->visible(fn($record) => $record->jurnalPembelian->is_confirmed && !$record->jurnalPembelian->is_posted),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $header = $this->record->jurnalPembelian;

        $data['tanggal'] = $header->tanggal;
        $data['rekening_kredit_id'] = $header->nomorBantuKredit?->rekening_id;
        $data['nomor_bantu_kredit_id'] = $header->nomor_bantu_kredit_id;
        $data['nama_nomor_bantu_kredit'] = $header->nama_nomor_bantu_kredit;
        $data['keterangan_header'] = $header->keterangan;

        $data['pembelian_items'] = $header->details->map(function ($detail) {
            return [
                'bukti' => $detail->bukti,
                'keterangan' => $detail->keterangan,
                'jumlah' => $detail->jumlah,
                'nomor_bantu_debit_id' => $detail->nomor_bantu_debit_id,
                'kode_proyek_id' => $detail->kode_proyek_id,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $pembelianItems = $data['pembelian_items'] ?? [];
            unset($data['pembelian_items']);

            if (empty($pembelianItems)) {
                throw new \Exception('Minimal harus ada 1 item pembelian');
            }

            $header = $record->jurnalPembelian;

            // Hitung total dari semua items
            $totalRp = collect($pembelianItems)->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
            
            // Cek apakah ada item dengan Aktiva Tetap
            $hasAktivaTetap = false;
            foreach ($pembelianItems as $item) {
                if (!empty($item['nomor_bantu_debit_id'])) {
                    $nomorBantu = \App\Models\NomorBantu::with(['rekening'])->find($item['nomor_bantu_debit_id']);
                    if ($nomorBantu && $nomorBantu->rekening && $nomorBantu->rekening->data === 'AT') {
                        $hasAktivaTetap = true;
                        break;
                    }
                }
            }

            // Update header
            $headerData = [
                'tanggal' => $data['tanggal'],
                'bukti' => $pembelianItems[0]['bukti'] ?? null,
                'kode_proyek_id' => $pembelianItems[0]['kode_proyek_id'] ?? null,
                'nomor_bantu_kredit_id' => $data['nomor_bantu_kredit_id'] ?? null,
                'nama_nomor_bantu_kredit' => $data['nama_nomor_bantu_kredit'] ?? null,
                'data_d' => $hasAktivaTetap ? 'AT' : null,
                'rp' => $totalRp,
                'keterangan' => $data['keterangan_header'] ?? $header->keterangan,
            ];

            // Update data_k if changed
            if ($headerData['nomor_bantu_kredit_id'] != $header->nomor_bantu_kredit_id) {
                $nbKredit = \App\Models\NomorBantu::with('rekening')->find($headerData['nomor_bantu_kredit_id']);
                $headerData['data_k'] = $nbKredit?->rekening?->data;
            }

            $header->update($headerData);

            // Delete existing details
            $header->details()->delete();

            // Re-create details
            $newDetails = [];
            foreach ($pembelianItems as $item) {
                $nomorBantu = null;
                if (!empty($item['nomor_bantu_debit_id'])) {
                    $nomorBantu = \App\Models\NomorBantu::with(['rekening.kelompok'])->find($item['nomor_bantu_debit_id']);
                }

                $newDetails[] = \App\Models\JurnalPembelianDetail::create([
                    'jurnal_pembelian_id' => $header->id,
                    'bukti' => $item['bukti'] ?? null,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'kelompok_debit_id' => $nomorBantu?->rekening?->kelompok_id,
                    'rekening_debit_id' => $nomorBantu?->rekening_id,
                    'nomor_bantu_debit_id' => $item['nomor_bantu_debit_id'] ?? null,
                    'kode_proyek_id' => $item['kode_proyek_id'] ?? null,
                ]);
            }

            // Return some detail record to satisfy Filament
            return $newDetails[0];
        });
    }
}
