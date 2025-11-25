<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use App\Filament\Widgets\WelcomeWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\RevenueExpenseChart;
use App\Filament\Widgets\CashFlowTrendChart;
use App\Filament\Widgets\RecentJournalsTable;
use App\Filament\Widgets\DraftJournalsTable;
use App\Filament\Widgets\LiquidityRatioChart;
use App\Filament\Widgets\TransactionTypeChart;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('Master Penomoran')
                    ->label('Master Penomoran')
                    ->collapsible(),
                NavigationGroup::make('Setup Saldo Awal')
                    ->label('Setup Saldo Awal')
                    ->collapsible(),
                NavigationGroup::make('Transaksi Kas')
                    ->label('Transaksi Kas')
                    ->collapsible(),
                NavigationGroup::make('Laporan Keuangan')
                    ->label('Laporan Keuangan')
                    ->collapsible(),
                NavigationGroup::make('Setup & Konfigurasi')
                    ->label('Setup & Konfigurasi')
                    ->collapsible(),
                NavigationGroup::make('Monitoring & Audit')
                    ->label('Monitoring & Audit')
                    ->collapsible(),
                NavigationGroup::make('Manajemen Pengguna')
                    ->label('Manajemen Pengguna')
                    ->collapsible(true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
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
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
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
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        hasAvatars: true, // Enables the avatar upload form component (default = false)
                        slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->enableTwoFactorAuthentication(
                        force: false, // force the user to enable 2FA before they can use the application (default = false)
                    )
                    ->enableSanctumTokens(
                        permissions: ['create', 'view', 'update', 'delete'] // optional, customize the permissions (default = ['create', 'view', 'update', 'delete'])
                    ),
            ]);
    }
}
