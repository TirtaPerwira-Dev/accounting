<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;

class ModalServiceProvider extends ServiceProvider
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
        // Inject modals into all Filament pages
        FilamentView::registerRenderHook(
            'panels::body.end',
            fn(): View => view('components.documentation-modal'),
        );

        FilamentView::registerRenderHook(
            'panels::body.end',
            fn(): View => view('components.manual-book-modal'),
        );

        FilamentView::registerRenderHook(
            'panels::body.end',
            fn(): View => view('components.technical-documentation-modal'),
        );

        // Add JavaScript to trigger modals
        FilamentView::registerRenderHook(
            'panels::body.end',
            fn(): View => view('components.modal-trigger-script'),
        );
    }
}
