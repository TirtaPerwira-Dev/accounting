<?php
 
namespace App\Observers;
 
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
 
class JournalObserver
{
    public function created($model): void
    {
        $this->notifyAdmins($model, 'created');
    }
 
    public function updated($model): void
    {
        if ($model->wasChanged('is_confirmed') && $model->is_confirmed) {
            $this->notifyAdmins($model, 'confirmed');
        }
    }
 
    protected function notifyAdmins($model, string $event): void
    {
        $type = str_replace('App\\Models\\', '', get_class($model));
        $noBukti = $model->nomor_bukti ?? ($model->bukti ?? 'No Ref');
        
        $recipients = User::role(['kepala_bagian', 'kepala_sub_bagian', 'super_admin'])->get();
 
        if ($event === 'created') {
            Notification::make()
                ->title('Jurnal Baru: ' . $type)
                ->body("Nomor Bukti: {$noBukti} menunggu konfirmasi.")
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->actions([
                    Action::make('view')
                        ->button()
                        ->url($this->getResourceUrl($model))
                ])
                ->sendToDatabase($recipients);
        }
    }
 
    protected function getResourceUrl($model): string
    {
        $class = get_class($model);
        $slug = match($class) {
            \App\Models\JurnalPenerimaanKas::class => 'jurnal-penerimaan-kas',
            \App\Models\JurnalBayarKasBank::class => 'jurnal-bayar-kas-bank',
            \App\Models\JurnalPembelian::class => 'jurnal-pembelian',
            \App\Models\JurnalMemorial::class => 'jurnal-memorial',
            \App\Models\JurnalPemakaianBahan::class => 'jurnal-pemakaian-bahan',
            \App\Models\JurnalRekeningAir::class => 'jurnal-rekening-air',
            default => null,
        };
 
        return $slug ? "/accounting/{$slug}/{$model->id}/edit" : '#';
    }
}
