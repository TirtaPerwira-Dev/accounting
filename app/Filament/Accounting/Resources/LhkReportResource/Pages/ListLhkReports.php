<?php

namespace App\Filament\Accounting\Resources\LhkReportResource\Pages;

use App\Filament\Accounting\Resources\LhkReportResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLhkReports extends ListRecords
{
    protected static string $resource = LhkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input LHK')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->badge(fn() => $this->getModel()::count()),

            'pemasukan' => Tab::make('Pemasukan')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis', 'pemasukan'))
                ->badge(fn() => $this->getModel()::where('jenis', 'pemasukan')->count())
                ->badgeColor('success'),

            'pengeluaran' => Tab::make('Pengeluaran')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis', 'pengeluaran'))
                ->badge(fn() => $this->getModel()::where('jenis', 'pengeluaran')->count())
                ->badgeColor('danger'),
        ];
    }
}
