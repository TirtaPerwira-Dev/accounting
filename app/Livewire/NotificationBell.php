<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\JurnalPenerimaanKas;
use App\Models\JurnalBayarKasBank;
use App\Models\JurnalPembelian;
use App\Models\JurnalMemorial;
use App\Models\JurnalPemakaianBahan;
use App\Models\JurnalRekeningAir;
use Spatie\Activitylog\Models\Activity;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class NotificationBell extends Component
{
    public function render()
    {
        $user = Auth::user();
        $notificationsCount = 0;
        $pendingJournals = [];
        $recentLogs = [];

        if ($user->hasAnyRole(['kepala_bagian', 'kepala_sub_bagian', 'super_admin'])) {
            $types = [
                'Penerimaan Kas' => JurnalPenerimaanKas::class,
                'Bayar Kas/Bank' => JurnalBayarKasBank::class,
                'Pembelian' => JurnalPembelian::class,
                'Memorial' => JurnalMemorial::class,
                'Pemakaian Bahan' => JurnalPemakaianBahan::class,
                'Rekening Air' => JurnalRekeningAir::class,
            ];

            foreach ($types as $label => $model) {
                // Fetch unconfirmed
                $unconfirmed = $model::where('is_confirmed', 0)->latest()->take(5)->get();
                // Fetch confirmed but unposted
                $unposted = $model::where('is_confirmed', 1)->where('is_posted', 0)->latest()->take(5)->get();

                if ($unconfirmed->count() > 0 || $unposted->count() > 0) {
                    $pendingJournals[$label] = [
                        'count' => $model::where('is_confirmed', 0)->orWhere(fn($q) => $q->where('is_confirmed', 1)->where('is_posted', 0))->count(),
                        'unconfirmed' => $unconfirmed,
                        'unposted' => $unposted,
                    ];
                    $notificationsCount += $pendingJournals[$label]['count'];
                }
            }
        }

        if ($user->hasRole('super_admin')) {
            $recentLogs = [
                'activity' => Activity::latest()->take(5)->get(),
                'auth' => AuthenticationLog::orderBy('login_at', 'desc')->take(5)->get(),
            ];
        }

        return view('livewire.notification-bell', [
            'notificationsCount' => $notificationsCount,
            'pendingJournals' => $pendingJournals,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function confirmJournal($modelClass, $id)
    {
        $record = $modelClass::find($id);
        if ($record) {
            $record->confirm();
            \Filament\Notifications\Notification::make()
                ->title('Jurnal berhasil dikonfirmasi')
                ->success()
                ->send();
        }
    }

    public function postJournal($modelClass, $id)
    {
        $record = $modelClass::find($id);
        if ($record) {
            if (!$record->is_confirmed) {
                \Filament\Notifications\Notification::make()
                    ->title('Jurnal harus dikonfirmasi terlebih dahulu')
                    ->danger()
                    ->send();
                return;
            }
            
            try {
                $service = app(\App\Services\JournalPostingService::class);
                $service->post($record);
                \Filament\Notifications\Notification::make()
                    ->title('Jurnal berhasil diposting ke Buku Besar')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Gagal posting')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
}
