<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use App\Filament\Widgets\WelcomeWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\RevenueExpenseChart;
use App\Filament\Widgets\CashFlowTrendChart;
use App\Filament\Widgets\RecentJournalsTable;
use App\Filament\Widgets\DraftJournalsTable;
use App\Filament\Widgets\LiquidityRatioChart;
use App\Filament\Widgets\TransactionTypeChart;

class AccountingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('accounting')
            ->path('accounting')
            ->login()
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
            ]);
    }
}
