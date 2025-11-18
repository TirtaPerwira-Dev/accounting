<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KodeProyekResource\Pages;
use App\Filament\Resources\KodeProyekResource\RelationManagers;
use App\Models\KodeProyek;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KodeProyekResource extends Resource
{
    protected static ?string $model = KodeProyek::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Kode Proyek';

    protected static ?string $modelLabel = 'Kode Proyek';

    protected static ?string $pluralModelLabel = 'Kode Proyek';

    protected static ?string $navigationGroup = 'Master Penomoran';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kode Proyek')
                    ->schema([
                        Forms\Components\TextInput::make('kode')
                            ->label('Kode Proyek')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('Contoh: PRJ001')
                            ->helperText('Kode unik untuk proyek'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Proyek')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nama lengkap proyek'),

                        Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2050)
                            ->default(now()->year)
                            ->placeholder('Tahun pelaksanaan proyek'),

                        Forms\Components\Textarea::make('ket')
                            ->label('Keterangan')
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder('Deskripsi atau keterangan tambahan proyek'),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Proyek')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('ket')
                    ->label('Keterangan')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Di buat oleh')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $years = [];
                        for ($i = 2020; $i <= 2050; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Penanggung Jawab')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->button()
                    ->label('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListKodeProyeks::route('/'),
            'create' => Pages\CreateKodeProyek::route('/create'),
            'edit' => Pages\EditKodeProyek::route('/{record}/edit'),
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
        return 'Total Nomor Kelompok Terdaftar';
    }
}
