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
use App\Models\User;
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

        $isSuperAdmin = $user?->hasRole('super_admin') ?? false;
        $isKepalaSubBagian = $user?->hasAnyRole([
            'kepala_sub_bagian',
            'kepala_sub_bagian_anggaran_pendapatan',
            'kepala_sub_bagian_verifikasi_pembukuan',
        ]) ?? false;
        $isStaffOrPelaksana = $user?->hasAnyRole([
            'staff',
            'staff_anggaran_pendapatan',
            'staff_verifikasi_pembukuan',
            'pelaksana',
        ]) ?? false;

        // Get dismissed items from session
        $dismissedJournals = session()->get('dismissed_journals', []);
        $dismissedLogs = session()->get('dismissed_logs', []);

        $types = [
            'Penerimaan Kas' => [
                'model' => JurnalPenerimaanKas::class,
                'view_permission' => 'view_any_jurnal::penerimaan::kas',
                'post_permission' => 'post_jurnal::penerimaan::kas',
            ],
            'Bayar Kas/Bank' => [
                'model' => JurnalBayarKasBank::class,
                'view_permission' => 'view_any_jurnal::bayar::kas::bank',
                'post_permission' => 'post_jurnal::bayar::kas::bank',
            ],
            'Pembelian' => [
                'model' => JurnalPembelian::class,
                'view_permission' => 'view_any_jurnal::pembelian',
                'post_permission' => 'post_jurnal::pembelian',
            ],
            'Memorial' => [
                'model' => JurnalMemorial::class,
                'view_permission' => 'view_any_jurnal::memorial',
                'post_permission' => 'post_jurnal::memorial',
            ],
            'Pemakaian Bahan' => [
                'model' => JurnalPemakaianBahan::class,
                'view_permission' => 'view_any_jurnal::pemakaian::bahan',
                'post_permission' => 'post_jurnal::pemakaian::bahan',
            ],
            'Rekening Air' => [
                'model' => JurnalRekeningAir::class,
                'view_permission' => 'view_any_jurnal::rekening::air',
                'post_permission' => 'post_jurnal::rekening::air',
            ],
        ];

        foreach ($types as $label => $meta) {
            if (!$user->can($meta['view_permission'])) {
                continue;
            }

            $model = $meta['model'];
            $canPost = $user->can($meta['post_permission']);

            $dismissedIds = $dismissedJournals[$label] ?? [];

            if ($isStaffOrPelaksana && !$isKepalaSubBagian && !$isSuperAdmin) {
                $records = $model::query()
                    ->where('created_by', $user->id)
                    ->where('is_posted', 1)
                    ->whereNotIn('id', $dismissedIds)
                    ->latest('posted_at')
                    ->take(5)
                    ->get();

                $count = $model::query()
                    ->where('created_by', $user->id)
                    ->where('is_posted', 1)
                    ->whereNotIn('id', $dismissedIds)
                    ->count();

                $action = 'view_posted';
                $canAct = false;
            } else {
                $records = $model::query()
                    ->where('is_posted', 0)
                    ->whereNotIn('id', $dismissedIds)
                    ->latest()
                    ->take(5)
                    ->get();

                $count = $model::query()
                    ->where('is_posted', 0)
                    ->whereNotIn('id', $dismissedIds)
                    ->count();

                $action = 'post';
                $canAct = $canPost;
            }

            if ($records->count() > 0) {
                $pendingJournals[$label] = [
                    'count' => $count,
                    'records' => $records,
                    'can_post' => $canAct,
                    'action' => $action,
                ];
                $notificationsCount += $pendingJournals[$label]['count'];
            }
        }

        $activityQuery = Activity::query()
            ->whereNotIn('id', $dismissedLogs['activity'] ?? []);

        $authQuery = AuthenticationLog::query()
            ->whereNotIn('id', $dismissedLogs['auth'] ?? []);

        if (!$isSuperAdmin) {
            $activityQuery
                ->where('causer_type', User::class)
                ->where('causer_id', $user->id);

            $authQuery
                ->where('authenticatable_type', User::class)
                ->where('authenticatable_id', $user->id);
        }

        $recentLogs = [
            'activity' => $activityQuery->latest()->take(5)->get(),
            'auth' => $authQuery->orderBy('login_at', 'desc')->take(5)->get(),
        ];

        $notificationsCount += $recentLogs['activity']->count();
        $notificationsCount += $recentLogs['auth']->count();

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
            $isStaffOrPelaksana = $user?->hasAnyRole([
                'staff',
                'staff_anggaran_pendapatan',
                'staff_verifikasi_pembukuan',
                'pelaksana',
            ]) ?? false;

            $query = $model::query();

            if ($isStaffOrPelaksana && !($user?->hasRole('super_admin') ?? false)) {
                $query->where('created_by', $user->id)->where('is_posted', 1);
            } else {
                $query->where('is_posted', 0);
            }

            $ids = $query->pluck('id')->toArray();
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

    public function postJournal($modelClass, $id)
    {
        $record = $modelClass::find($id);
        if ($record) {
            if (!auth()->user()->can('postToLedger', $record)) {
                \Filament\Notifications\Notification::make()
                    ->title('Anda tidak memiliki hak akses untuk posting jurnal ini')
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
