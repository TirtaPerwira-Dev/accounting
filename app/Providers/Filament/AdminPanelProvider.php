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
use Jeffgreco13\FilamentBreezy\BreezyCore;
use App\Filament\Widgets\WelcomeWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\RevenueExpenseChart;
use App\Filament\Widgets\CashFlowTrendChart;
use App\Filament\Widgets\RecentJournalsTable;
use App\Filament\Widgets\DraftJournalsTable;
use App\Filament\Widgets\LiquidityRatioChart;
use App\Filament\Widgets\TransactionTypeChart;
use App\Filament\Admin\Pages\Auth\Register as CustomRegister;
use App\Filament\Admin\Widgets\UserStatsWidget;
use App\Filament\Admin\Widgets\RoleStatsWidget;
use App\Filament\Admin\Widgets\ActivityLogStatsWidget;
use App\Filament\Admin\Widgets\RecentActivityLogTableWidget;
use App\Filament\Admin\Widgets\RecentAuthenticationLogTableWidget;
use App\Filament\Admin\Widgets\ActivityLogEventChartWidget;
use App\Filament\Admin\Widgets\UserRoleChartWidget;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login([LoginController::class, 'show'])
            ->authGuard('web')
            ->registration(CustomRegister::class)
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            ->userMenuItems([
                'accounting' => MenuItem::make()
                    ->label('Accounting Panel')
                    ->url('/accounting')
                    ->icon('heroicon-o-calculator')
                    ->visible(fn(): bool => Auth::check() && Auth::user()?->hasRole('super_admin')),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
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
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                UserStatsWidget::class,
                RoleStatsWidget::class,
                ActivityLogStatsWidget::class,
                ActivityLogEventChartWidget::class,
                UserRoleChartWidget::class,
                RecentActivityLogTableWidget::class,
                RecentAuthenticationLogTableWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // AuthenticateSession::class, // Disabled - causes login loop and redirect prompts on hosting
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
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: true,
                        slug: 'my-profile'
                    )
                    ->enableTwoFactorAuthentication(
                        force: false,
                    )
                    ->enableSanctumTokens(
                        permissions: ['create', 'view', 'update', 'delete']
                    ),
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\'notification-bell\')'),
            );
    }
}
