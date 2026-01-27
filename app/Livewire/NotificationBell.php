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

        // Get dismissed items from session
        $dismissedJournals = session()->get('dismissed_journals', []);
        $dismissedLogs = session()->get('dismissed_logs', []);

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
                $dismissedIds = $dismissedJournals[$label] ?? [];

                // Fetch unconfirmed
                $unconfirmed = $model::where('is_confirmed', 0)
                    ->whereNotIn('id', $dismissedIds)
                    ->latest()->take(5)->get();
                
                // Fetch confirmed but unposted
                $unposted = $model::where('is_confirmed', 1)
                    ->where('is_posted', 0)
                    ->whereNotIn('id', $dismissedIds)
                    ->latest()->take(5)->get();

                if ($unconfirmed->count() > 0 || $unposted->count() > 0) {
                    $pendingJournals[$label] = [
                        'count' => $model::where(function($q) use ($dismissedIds) {
                            $q->where('is_confirmed', 0)->orWhere(fn($sq) => $sq->where('is_confirmed', 1)->where('is_posted', 0));
                        })->whereNotIn('id', $dismissedIds)->count(),
                        'unconfirmed' => $unconfirmed,
                        'unposted' => $unposted,
                    ];
                    $notificationsCount += $pendingJournals[$label]['count'];
                }
            }
        }

        if ($user->hasRole('super_admin')) {
            $recentLogs = [
                'activity' => Activity::whereNotIn('id', $dismissedLogs['activity'] ?? [])->latest()->take(5)->get(),
                'auth' => AuthenticationLog::whereNotIn('id', $dismissedLogs['auth'] ?? [])->orderBy('login_at', 'desc')->take(5)->get(),
            ];
            
            $notificationsCount += $recentLogs['activity']->count();
            $notificationsCount += $recentLogs['auth']->count();
        }

        return view('livewire.notification-bell', [
            'notificationsCount' => $notificationsCount,
            'pendingJournals' => $pendingJournals,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function dismissJournal($type, $id)
    {
        $dismissed = session()->get('dismissed_journals', []);
        $dismissed[$type][] = (int) $id;
        session()->put('dismissed_journals', $dismissed);

        \Filament\Notifications\Notification::make()
            ->title('Notification cleared')
            ->success()
            ->send();
    }

    public function dismissLog($logType, $id)
    {
        $dismissed = session()->get('dismissed_logs', []);
        $dismissed[$logType][] = (int) $id;
        session()->put('dismissed_logs', $dismissed);

        \Filament\Notifications\Notification::make()
            ->title('Log entry hidden')
            ->success()
            ->send();
    }

    public function clearAllJournals($type)
    {
        $user = Auth::user();
        $model = match($type) {
            'Penerimaan Kas' => JurnalPenerimaanKas::class,
            'Bayar Kas/Bank' => JurnalBayarKasBank::class,
            'Pembelian' => JurnalPembelian::class,
            'Memorial' => JurnalMemorial::class,
            'Pemakaian Bahan' => JurnalPemakaianBahan::class,
            'Rekening Air' => JurnalRekeningAir::class,
            default => null,
        };

        if ($model) {
            $ids = $model::where('is_confirmed', 0)->orWhere(fn($q) => $q->where('is_confirmed', 1)->where('is_posted', 0))->pluck('id')->toArray();
            $dismissed = session()->get('dismissed_journals', []);
            $dismissed[$type] = array_unique(array_merge($dismissed[$type] ?? [], $ids));
            session()->put('dismissed_journals', $dismissed);

            \Filament\Notifications\Notification::make()
                ->title("All $type notifications cleared")
                ->success()
                ->send();
        }
    }

    public function clearAllLogs($logType)
    {
        $ids = [];
        if ($logType === 'activity') {
            $ids = Activity::latest()->take(50)->pluck('id')->toArray();
        } elseif ($logType === 'auth') {
            $ids = AuthenticationLog::orderBy('login_at', 'desc')->take(50)->pluck('id')->toArray();
        }

        if (!empty($ids)) {
            $dismissed = session()->get('dismissed_logs', []);
            $dismissed[$logType] = array_unique(array_merge($dismissed[$logType] ?? [], $ids));
            session()->put('dismissed_logs', $dismissed);

            \Filament\Notifications\Notification::make()
                ->title("All " . ($logType === 'activity' ? 'activity' : 'security') . " logs cleared")
                ->success()
                ->send();
        }
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
