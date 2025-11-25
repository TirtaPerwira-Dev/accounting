<?php

namespace App\Observers;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyObserver
{
    /**
     * Handle the Company "creating" event.
     */
    public function creating(Company $company): void
    {
        // Ensure NPWP is properly formatted and unique
        if ($company->npwp) {
            $company->npwp = preg_replace('/[^0-9.-]/', '', $company->npwp);
        }
    }

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $this->clearRelatedCache($company);
        Log::info('Company created', ['id' => $company->id, 'name' => $company->name]);
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        $this->clearRelatedCache($company);

        // If logo was changed, delete old logo
        if ($company->isDirty('logo') && $company->getOriginal('logo')) {
            $oldLogo = $company->getOriginal('logo');
            if (Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
        }

        Log::info('Company updated', ['id' => $company->id, 'name' => $company->name]);
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        $this->clearRelatedCache($company);

        // Delete logo file when company is deleted
        if ($company->logo && Storage::disk('public')->exists($company->logo)) {
            Storage::disk('public')->delete($company->logo);
        }

        Log::info('Company deleted', ['id' => $company->id, 'name' => $company->name]);
    }

    /**
     * Clear related cache
     */
    private function clearRelatedCache(Company $company): void
    {
        $keys = [
            'standards_with_counts',
            'dashboard_summary',
            "company_{$company->id}_with_accounts",
            "company_{$company->id}_accounts_hierarchy",
            "company_npwp_{$company->npwp}"
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
