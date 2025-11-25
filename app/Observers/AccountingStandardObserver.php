<?php

namespace App\Observers;

use App\Models\AccountingStandard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccountingStandardObserver
{
    /**
     * Handle the AccountingStandard "created" event.
     */
    public function created(AccountingStandard $accountingStandard): void
    {
        $this->clearRelatedCache($accountingStandard);
        Log::info('Accounting Standard created', ['id' => $accountingStandard->id, 'code' => $accountingStandard->code]);
    }

    /**
     * Handle the AccountingStandard "updated" event.
     */
    public function updated(AccountingStandard $accountingStandard): void
    {
        $this->clearRelatedCache($accountingStandard);
        Log::info('Accounting Standard updated', ['id' => $accountingStandard->id, 'code' => $accountingStandard->code]);
    }

    /**
     * Handle the AccountingStandard "deleted" event.
     */
    public function deleted(AccountingStandard $accountingStandard): void
    {
        $this->clearRelatedCache($accountingStandard);
        Log::info('Accounting Standard deleted', ['id' => $accountingStandard->id, 'code' => $accountingStandard->code]);
    }

    /**
     * Clear related cache
     */
    private function clearRelatedCache(AccountingStandard $accountingStandard): void
    {
        $keys = [
            'standards_with_counts',
            'active_accounting_standards',
            "accounting_standard_{$accountingStandard->code}",
            'dashboard_summary'
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
