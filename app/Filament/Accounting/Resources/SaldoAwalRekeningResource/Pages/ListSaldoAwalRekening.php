<?php

namespace App\Filament\Accounting\Resources\SaldoAwalRekeningResource\Pages;

use App\Filament\Accounting\Resources\SaldoAwalRekeningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSaldoAwalRekening extends ListRecords
{
    protected static string $resource = SaldoAwalRekeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Saldo Awal')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Data')
                ->badge(fn() => $this->getModel()::count()),

            'debit' => Tab::make('Debit')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('posisi', 'D'))
                ->badge(fn() => $this->getModel()::where('posisi', 'D')->count())
                ->badgeColor('primary'),

            'kredit' => Tab::make('Kredit')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('posisi', 'K'))
                ->badge(fn() => $this->getModel()::where('posisi', 'K')->count())
                ->badgeColor('danger'),

            'tahun_' . now()->year => Tab::make('Tahun ' . now()->year)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tahun', now()->year))
                ->badge(fn() => $this->getModel()::where('tahun', now()->year)->count())
                ->badgeColor('success'),
        ];
    }
}
