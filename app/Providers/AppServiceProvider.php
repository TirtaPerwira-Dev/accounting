<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use App\Http\Responses\LoginResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom login response for role-based redirect
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Register Observers for Notifications
        \App\Models\JurnalPenerimaanKas::observe(\App\Observers\JournalObserver::class);
        \App\Models\JurnalBayarKasBank::observe(\App\Observers\JournalObserver::class);
        \App\Models\JurnalPembelian::observe(\App\Observers\JournalObserver::class);
        \App\Models\JurnalMemorial::observe(\App\Observers\JournalObserver::class);
        \App\Models\JurnalPemakaianBahan::observe(\App\Observers\JournalObserver::class);
        \App\Models\JurnalRekeningAir::observe(\App\Observers\JournalObserver::class);
    }
}
