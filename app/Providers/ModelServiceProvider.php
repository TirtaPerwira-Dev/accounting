<?php

namespace App\Providers;

use App\Models\AccountingStandard;
use App\Models\Company;
use App\Observers\AccountingStandardObserver;
use App\Observers\CompanyObserver;
use Illuminate\Support\ServiceProvider;

class ModelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers
        AccountingStandard::observe(AccountingStandardObserver::class);
        Company::observe(CompanyObserver::class);
    }
}
