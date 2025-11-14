<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;

class NavigationOrderServiceProvider extends ServiceProvider
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
        // Define navigation group order
        $this->configureNavigationGroupOrder();
    }

    /**
     * Configure the order of navigation groups
     */
    private function configureNavigationGroupOrder(): void
    {
        // Define the desired order of navigation groups
        $groupOrder = [
            'Master Penomoran' => 1,
            'Setup Saldo Awal' => 2,
            'Transaksi Kas' => 3,
            'Laporan Keuangan' => 4,
            'Setup & Konfigurasi' => 5,
            'Monitoring & Audit' => 6,
            'Manajemen Pengguna' => 7,
        ];

        // Register navigation groups with proper ordering
        Filament::serving(function () use ($groupOrder) {
            foreach ($groupOrder as $groupName => $sort) {
                Filament::registerNavigationGroups([
                    NavigationGroup::make($groupName)
                        ->label($groupName)
                        ->icon($this->getGroupIcon($groupName))
                        ->collapsed(false)
                ]);
            }
        });
    }

    /**
     * Get icon for each navigation group
     */
    private function getGroupIcon(string $groupName): string
    {
        return match ($groupName) {
            'Master Penomoran' => 'heroicon-o-numbered-list',
            'Setup Saldo Awal' => 'heroicon-o-calculator',
            'Transaksi Kas' => 'heroicon-o-banknotes',
            'Laporan Keuangan' => 'heroicon-o-document-chart-bar',
            'Setup & Konfigurasi' => 'heroicon-o-cog-6-tooth',
            'Monitoring & Audit' => 'heroicon-o-eye',
            'Manajemen Pengguna' => 'heroicon-o-users',
            default => 'heroicon-o-folder',
        };
    }
}
