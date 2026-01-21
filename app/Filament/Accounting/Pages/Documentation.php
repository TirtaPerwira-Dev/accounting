<?php

namespace App\Filament\Accounting\Pages;

use Filament\Pages\Page;

class Documentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static string $view = 'filament.accounting.pages.documentation';

    protected static ?string $navigationLabel = 'Dokumentasi';

    protected static ?string $navigationGroup = 'Bantuan';

    protected static ?int $navigationGroupSort = 999;

    protected static ?int $navigationSort = 98;

    public function getTitle(): string
    {
        return 'Dokumentasi Sistem Akuntansi';
    }
}
