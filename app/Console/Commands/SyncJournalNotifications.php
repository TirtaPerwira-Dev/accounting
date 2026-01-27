<?php
 
namespace App\Console\Commands;
 
use Illuminate\Console\Command;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
 
class SyncJournalNotifications extends Command
{
    protected $signature = 'app:sync-journal-notifications';
    protected $description = 'Sync pending journals into Filament database notifications';
 
    public function handle()
    {
        $types = [
            'Penerimaan Kas' => \App\Models\JurnalPenerimaanKas::class,
            'Bayar Kas/Bank' => \App\Models\JurnalBayarKasBank::class,
            'Pembelian' => \App\Models\JurnalPembelian::class,
            'Memorial' => \App\Models\JurnalMemorial::class,
            'Pemakaian Bahan' => \App\Models\JurnalPemakaianBahan::class,
            'Rekening Air' => \App\Models\JurnalRekeningAir::class,
        ];
 
        $recipients = User::role(['kepala_bagian', 'kepala_sub_bagian', 'super_admin'])->get();
        $count = 0;
 
        foreach ($types as $label => $modelClass) {
            $pending = $modelClass::where('is_confirmed', false)->get();
 
            foreach ($pending as $record) {
                $noBukti = $record->nomor_bukti ?? ($record->bukti ?? 'No Ref');
                
                $notification = Notification::make()
                    ->title('Pending: ' . $label)
                    ->body("Nomor Bukti: {$noBukti} menunggu konfirmasi.")
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url($this->getResourceUrl($record))
                    ]);

                foreach ($recipients as $recipient) {
                    $notification->sendToDatabase($recipient);
                }
                
                $count++;
            }
        }
 
        $this->info("Successfully synced {$count} journal notifications.");
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
