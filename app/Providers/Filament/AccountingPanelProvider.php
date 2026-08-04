<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentAuthenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\MenuItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\WelcomeWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\RevenueExpenseChart;
use App\Filament\Widgets\CashFlowTrendChart;
use App\Filament\Widgets\RecentJournalsTable;
use App\Filament\Widgets\DraftJournalsTable;
use App\Filament\Widgets\LiquidityRatioChart;
use App\Filament\Widgets\TransactionTypeChart;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;

class AccountingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('accounting')
            ->path('accounting')
            ->authGuard('web')
            ->login([LoginController::class, 'show'])
            ->userMenuItems([
                'admin' => MenuItem::make()
                    ->label('Admin Panel')
                    ->url('/admin')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->visible(fn(): bool => Auth::check() && Auth::user()?->hasRole('super_admin')),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                NavigationGroup::make('Master Penomoran')
                    ->label('Master Penomoran')
                    ->collapsible(),
                NavigationGroup::make('Setup Saldo Awal')
                    ->label('Setup Saldo Awal')
                    ->collapsible(),
                NavigationGroup::make('Laporan Keuangan')
                    ->label('Laporan Keuangan')
                    ->collapsible(),
            ])
            ->discoverResources(in: app_path('Filament/Accounting/Resources'), for: 'App\\Filament\\Accounting\\Resources')
            ->discoverPages(in: app_path('Filament/Accounting/Pages'), for: 'App\\Filament\\Accounting\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                WelcomeWidget::class,
                FinancialOverviewWidget::class,
                RevenueExpenseChart::class,
                CashFlowTrendChart::class,
                RecentJournalsTable::class,
                DraftJournalsTable::class,
                LiquidityRatioChart::class,
                TransactionTypeChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
                'redirect.role',
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn(): string => \Illuminate\Support\Facades\Blade::render('@livewire(\'notification-bell\')'),
            );
    }
}
