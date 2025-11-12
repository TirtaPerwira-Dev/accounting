<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KelompokResource\Pages;
use App\Filament\Resources\KelompokResource\RelationManagers;
use App\Models\Kelompok;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class KelompokResource extends Resource
{
    protected static ?string $model = Kelompok::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Master Penomoran';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Kelompok Akun';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('no_kel')
                    ->label('Nomor Kelompok')
                    ->required()
                    ->numeric()
                    ->maxLength(2)
                    ->unique(ignoreRecord: true),

                TextInput::make('nama_kel')
                    ->label('Nama Kelompok')
                    ->required()
                    ->maxLength(255),

                Select::make('kel')
                    ->label('Kategori')
                    ->options([
                        1 => '1 - Aktiva',
                        2 => '2 - Kewajiban',
                        3 => '3 - Pendapatan',
                        4 => '4 - Biaya Operasional',
                        5 => '5 - Biaya Administrasi',
                        6 => '6 - Biaya Luar Usaha'
                    ])
                    ->required()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_kel')
                    ->label('No. Kelompok')
                    ->sortable()
                    ->searchable()
                    ->badge(),

                TextColumn::make('nama_kel')
                    ->label('Nama Kelompok')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('kel')
                    ->label('Kategori')
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '1' => '1 - Aktiva',
                        '2' => '2 - Kewajiban',
                        '3' => '3 - Pendapatan',
                        '4' => '4 - Biaya Operasional',
                        '5' => '5 - Biaya Administrasi',
                        '6' => '6 - Biaya Luar Usaha',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'success',
                        '2' => 'danger',
                        '3' => 'primary',
                        '4' => 'warning',
                        '5' => 'info',
                        '6' => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('rekenings_count')
                    ->label('Jumlah Rekening')
                    ->counts('rekenings')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kel')
                    ->label('Kategori')
                    ->options([
                        1 => '1 - Aktiva',
                        2 => '2 - Kewajiban',
                        3 => '3 - Pendapatan',
                        4 => '4 - Biaya Operasional',
                        5 => '5 - Biaya Administrasi',
                        6 => '6 - Biaya Luar Usaha'
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('no_kel');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelompoks::route('/'),
            'create' => Pages\CreateKelompok::route('/create'),
            'edit' => Pages\EditKelompok::route('/{record}/edit'),
        ];
    }
}
