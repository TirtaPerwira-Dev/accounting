<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalKoreksiResource\Pages;
use App\Models\JurnalMemorial;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JurnalKoreksiResource extends Resource
{
    protected static ?string $model = JurnalMemorial::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Jurnal Koreksi';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 7;

    protected static ?string $pluralModelLabel = 'Jurnal Koreksi';

    protected static ?string $slug = 'jurnal-koreksi';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (!$user) {
            return '0';
        }

        $companyId = (int) ($user->company_id ?? 1);

        return (string) static::getModel()::query()
            ->where('company_id', $companyId)
            ->where('keterangan', 'like', '[KOREKSI]%')
            ->where('is_posted', false)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : 'success';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('keterangan', 'like', '[KOREKSI]%');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bukti')
                    ->label('No Bukti')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rp')
                    ->label('Nominal')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Terkonfirmasi')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_posted')
                    ->label('Posted')
                    ->boolean(),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalKoreksis::route('/'),
            'create' => Pages\CreateJurnalKoreksi::route('/create'),
        ];
    }
}
