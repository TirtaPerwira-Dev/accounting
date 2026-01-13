<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class TechnicalDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static string $view = 'filament.admin.pages.technical-documentation';

    protected static ?string $navigationLabel = 'Technical Documentation';

    protected static ?string $navigationGroup = 'Dokumentasi';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->hasRole('super_admin');
    }

    public function getTitle(): string
    {
        return 'Technical Documentation - Developer Guide';
    }
}
