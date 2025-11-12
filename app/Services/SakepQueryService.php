<?php

namespace App\Services;

use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SakepQueryService
{
    /**
     * Get complete SAKEP hierarchy
     */
    public function getSakepHierarchy(): array
    {
        return Cache::remember('sakep_hierarchy', 1800, function () {
            return Kelompok::with([
                'rekenings' => function ($query) {
                    $query->orderBy('no_rek');
                },
                'rekenings.nomorBantus' => function ($query) {
                    $query->orderBy('no_bantu');
                }
            ])
                ->orderBy('no_kel')
                ->get()
                ->map(function ($kelompok) {
                    return [
                        'kelompok' => $kelompok->only(['id', 'no_kel', 'nama_kel', 'kel']),
                        'rekenings' => $kelompok->rekenings->map(function ($rekening) {
                            return [
                                'rekening' => $rekening->only(['id', 'no_rek', 'nama_rek', 'kode']),
                                'nomor_bantus' => $rekening->nomorBantus->map(function ($nomorBantu) {
                                    return $nomorBantu->only(['id', 'no_bantu', 'nm_bantu', 'kode']);
                                })
                            ];
                        })
                    ];
                });
        });
    }

    /**
     * Get SAKEP for dropdown/select options
     */
    public function getSakepOptions(string $level = 'all'): array
    {
        switch ($level) {
            case 'kelompok':
                return Kelompok::orderBy('no_kel')
                    ->pluck('nama_kel', 'id')
                    ->toArray();

            case 'rekening':
                return Rekening::with('kelompok')
                    ->orderBy('no_rek')
                    ->get()
                    ->mapWithKeys(function ($rekening) {
                        return [
                            $rekening->id => $rekening->kelompok->no_kel . $rekening->no_rek . ' - ' . $rekening->nama_rek
                        ];
                    })
                    ->toArray();

            case 'nomor_bantu':
                return NomorBantu::with(['rekening.kelompok'])
                    ->orderBy('no_bantu')
                    ->get()
                    ->mapWithKeys(function ($nomorBantu) {
                        $code = $nomorBantu->rekening->kelompok->no_kel .
                            $nomorBantu->rekening->no_rek .
                            $nomorBantu->no_bantu;
                        return [
                            $nomorBantu->id => $code . ' - ' . $nomorBantu->nm_bantu
                        ];
                    })
                    ->toArray();

            default:
                return [
                    'kelompok' => $this->getSakepOptions('kelompok'),
                    'rekening' => $this->getSakepOptions('rekening'),
                    'nomor_bantu' => $this->getSakepOptions('nomor_bantu'),
                ];
        }
    }

    /**
     * Get SAKEP account balances from journal details
     */
    public function getAccountBalances(?string $asOfDate = null): array
    {
        $query = JournalDetail::with(['kelompok', 'rekening', 'nomorBantu'])
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted');

        if ($asOfDate) {
            $query->whereDate('journals.transaction_date', '<=', $asOfDate);
        }

        $details = $query->select([
            'journal_details.*',
            'journals.transaction_date'
        ])
            ->get()
            ->groupBy(function ($detail) {
                // Group by the lowest level SAKEP available
                if ($detail->nomor_bantu_id) {
                    return 'nomor_bantu_' . $detail->nomor_bantu_id;
                } elseif ($detail->rekening_id) {
                    return 'rekening_' . $detail->rekening_id;
                } else {
                    return 'kelompok_' . $detail->kelompok_id;
                }
            });

        $balances = [];
        foreach ($details as $groupKey => $groupDetails) {
            $detail = $groupDetails->first();
            $totalDebit = $groupDetails->sum('debit');
            $totalCredit = $groupDetails->sum('credit');
            $balance = $totalDebit - $totalCredit;

            $balances[] = [
                'group_key' => $groupKey,
                'sakep_code' => $detail->sakep_code,
                'account_name' => $detail->account_name,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance' => $balance,
                'balance_type' => $balance >= 0 ? 'debit' : 'credit',
                'abs_balance' => abs($balance),
                'kelompok' => $detail->kelompok,
                'rekening' => $detail->rekening,
                'nomor_bantu' => $detail->nomorBantu,
            ];
        }

        return collect($balances)->sortBy('sakep_code')->values()->toArray();
    }

    /**
     * Get trial balance in SAKEP format
     */
    public function getTrialBalance(?string $asOfDate = null): array
    {
        $balances = $this->getAccountBalances($asOfDate);

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($balances as &$balance) {
            if ($balance['balance'] >= 0) {
                $totalDebit += $balance['abs_balance'];
            } else {
                $totalCredit += $balance['abs_balance'];
            }
        }

        return [
            'accounts' => $balances,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'as_of_date' => $asOfDate ?: now()->toDateString(),
        ];
    }

    /**
     * Get SAKEP summary statistics
     */
    public function getSakepStats(): array
    {
        return Cache::remember('sakep_stats', 3600, function () {
            return [
                'total_kelompok' => Kelompok::count(),
                'total_rekening' => Rekening::count(),
                'total_nomor_bantu' => NomorBantu::count(),
                'kelompok_by_type' => Kelompok::select('kel', DB::raw('count(*) as total'))
                    ->groupBy('kel')
                    ->pluck('total', 'kel')
                    ->toArray(),
                'recent_journals_count' => Journal::where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'total_journal_entries' => JournalDetail::count(),
            ];
        });
    }

    /**
     * Search SAKEP accounts
     */
    public function searchSakep(string $query, int $limit = 50): array
    {
        $results = [];

        // Search in Nomor Bantu
        $nomorBantus = NomorBantu::with(['rekening.kelompok'])
            ->where(function ($q) use ($query) {
                $q->where('nm_bantu', 'ILIKE', "%{$query}%")
                    ->orWhere('no_bantu', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        foreach ($nomorBantus as $nb) {
            $results[] = [
                'type' => 'nomor_bantu',
                'id' => $nb->id,
                'code' => $nb->rekening->kelompok->no_kel . $nb->rekening->no_rek . $nb->no_bantu,
                'name' => $nb->nm_bantu,
                'hierarchy' => $nb->rekening->kelompok->nama_kel . ' > ' . $nb->rekening->nama_rek . ' > ' . $nb->nm_bantu,
            ];
        }

        // Search in Rekening if not enough results
        if (count($results) < $limit) {
            $rekenings = Rekening::with('kelompok')
                ->where(function ($q) use ($query) {
                    $q->where('nama_rek', 'ILIKE', "%{$query}%")
                        ->orWhere('no_rek', 'LIKE', "%{$query}%");
                })
                ->limit($limit - count($results))
                ->get();

            foreach ($rekenings as $rek) {
                $results[] = [
                    'type' => 'rekening',
                    'id' => $rek->id,
                    'code' => $rek->kelompok->no_kel . $rek->no_rek,
                    'name' => $rek->nama_rek,
                    'hierarchy' => $rek->kelompok->nama_kel . ' > ' . $rek->nama_rek,
                ];
            }
        }

        // Search in Kelompok if still not enough
        if (count($results) < $limit) {
            $kelompoks = Kelompok::where(function ($q) use ($query) {
                $q->where('nama_kel', 'ILIKE', "%{$query}%")
                    ->orWhere('no_kel', 'LIKE', "%{$query}%");
            })
                ->limit($limit - count($results))
                ->get();

            foreach ($kelompoks as $kel) {
                $results[] = [
                    'type' => 'kelompok',
                    'id' => $kel->id,
                    'code' => $kel->no_kel,
                    'name' => $kel->nama_kel,
                    'hierarchy' => $kel->nama_kel,
                ];
            }
        }

        return $results;
    }
}
