<?php

namespace App\Filament\Accounting\Pages;

use Filament\Pages\Page;

class ManualBook extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.accounting.pages.manual-book';

    protected static ?string $navigationLabel = 'Manual Book';

    protected static ?string $navigationGroup = 'Bantuan';

    protected static ?int $navigationSort = 99;

    public function getTitle(): string
    {
        return 'Manual Book - Panduan Pengguna';
    }
}
