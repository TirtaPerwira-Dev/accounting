<?php
 
namespace App\Observers;
 
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
 
class JournalObserver
{
    public function created($model): void
    {
        $this->notifyKepalaSubBagian($model);
    }
 
    public function updated($model): void
    {
        if ($model->wasChanged('is_posted') && $model->is_posted) {
            $this->notifyStaffOrPelaksana($model);
        }
    }

    protected function notifyKepalaSubBagian($model): void
    {
        $type = str_replace('App\\Models\\', '', get_class($model));
        $noBukti = $model->nomor_bukti ?? ($model->bukti ?? 'No Ref');

        $recipients = User::role([
            'kepala_sub_bagian',
            'kepala_sub_bagian_anggaran_pendapatan',
            'kepala_sub_bagian_verifikasi_pembukuan',
            'super_admin',
        ])->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Jurnal Baru: ' . $type)
            ->body("Nomor Bukti: {$noBukti} baru diinput dan menunggu posting.")
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->actions([
                Action::make('view')
                    ->button()
                    ->url($this->getResourceUrl($model))
            ])
            ->sendToDatabase($recipients);
    }

    protected function notifyStaffOrPelaksana($model): void
    {
        $creatorId = (int) ($model->created_by ?? 0);

        if ($creatorId <= 0) {
            return;
        }

        $creator = User::find($creatorId);
        if (!$creator) {
            return;
        }

        if (!$creator->hasAnyRole([
            'staff',
            'staff_anggaran_pendapatan',
            'staff_verifikasi_pembukuan',
            'pelaksana',
        ])) {
            return;
        }

        $type = str_replace('App\\Models\\', '', get_class($model));
        $noBukti = $model->nomor_bukti ?? ($model->bukti ?? 'No Ref');

        Notification::make()
            ->title('Jurnal Diposting: ' . $type)
            ->body("Nomor Bukti: {$noBukti} yang Anda input sudah diposting.")
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->actions([
                Action::make('view')
                    ->button()
                    ->url($this->getResourceUrl($model))
            ])
            ->sendToDatabase($creator);
    }
 
    protected function getResourceUrl($model): string
    {
        $class = get_class($model);
        $slug = match($class) {
            \App\Models\JurnalPenerimaanKas::class => 'jurnal-penerimaan-kas',
            \App\Models\JurnalBayarKasBank::class => 'jurnal-bayar-kas-bank',
            \App\Models\JurnalPembelian::class => 'jurnal-pembelian-barang',
            \App\Models\JurnalMemorial::class => 'jurnal-memorial',
            \App\Models\JurnalPemakaianBahan::class => 'jurnal-pemakaian-bahan',
            \App\Models\JurnalRekeningAir::class => 'jurnal-rekening-air',
            default => null,
        };
 
        return $slug ? "/accounting/{$slug}/{$model->id}/edit" : '#';
    }
}
