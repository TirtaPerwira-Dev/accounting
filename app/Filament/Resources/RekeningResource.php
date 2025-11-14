<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekeningResource\Pages;
use App\Filament\Resources\RekeningResource\RelationManagers;
use App\Models\Rekening;
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

class RekeningResource extends Resource
{
    protected static ?string $model = Rekening::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Master Penomoran';

    protected static ?int $navigationGroupSort = 1;

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Rekening';

    protected static ?string $navigationLabel = 'Rekening';

    protected static ?string $pluralModelLabel = 'Rekening';

    protected static ?string $slug = 'rekening';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('kelompok_id')
                    ->label('Kelompok')
                    ->relationship('kelompok', 'nama_kel')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('no_kel')
                            ->label('Nomor Kelompok')
                            ->required()
                            ->numeric()
                            ->maxLength(2),
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
                    ]),

                TextInput::make('no_rek')
                    ->label('Nomor Rekening')
                    ->required()
                    ->numeric()
                    ->maxLength(4)
                    ->unique(ignoreRecord: true),

                TextInput::make('nama_rek')
                    ->label('Nama Rekening')
                    ->required()
                    ->maxLength(255),

                Select::make('kode')
                    ->label('Saldo Normal')
                    ->options([
                        'D' => 'D - Debet',
                        'K' => 'K - Kredit',
                    ])
                    ->required()
                    ->native(false),

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
                TextColumn::make('kelompok.nama_kel')
                    ->label('Kelompok')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('no_rek')
                    ->label('No. Rekening')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('nama_rek')
                    ->label('Nama Rekening')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('kode')
                    ->label('Saldo Normal')
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'D' => 'D - Debet',
                        'K' => 'K - Kredit',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'D' => 'success',
                        'K' => 'danger',
                        default => 'secondary',
                    }),

                TextColumn::make('nomor_bantus_count')
                    ->label('Jumlah Nomor Bantu')
                    ->counts('nomorBantus')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kelompok')
                    ->relationship('kelompok', 'nama_kel')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('kode')
                    ->label('Saldo Normal')
                    ->options([
                        'D' => 'D - Debet',
                        'K' => 'K - Kredit',
                    ]),

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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->color('primary') // Ini yang membuat warnanya biru (primary)
                    ->icon('heroicon-o-ellipsis-vertical') // Opsional: ganti icon
                    ->size('sm')
                    ->button()
                    ->color('primary'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('no_rek');
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
            'index' => Pages\ListRekenings::route('/'),
            'create' => Pages\CreateRekening::route('/create'),
            'edit' => Pages\EditRekening::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total Nomor Rekening Terdaftar';
    }
}
