<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekeningResource\Pages;
use App\Models\Rekening;
use App\Models\Kelompok;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RekeningResource extends Resource
{
    protected static ?string $model = Rekening::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Master Penomoran';
    protected static ?int $navigationGroupSort = 1;
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Rekening';
    protected static ?string $pluralModelLabel = 'Rekening';
    protected static ?string $slug = 'rekening';

    // Kategori Akuntansi (bisa dipindah ke config/enum)
    const KATEGORI_AKUNTANSI = [
        1 => '1 - Aktiva',
        2 => '2 - Kewajiban',
        3 => '3 - Pendapatan',
        4 => '4 - Biaya Operasional',
        5 => '5 - Biaya Administrasi',
        6 => '6 - Biaya Luar Usaha',
    ];

    const SALDO_NORMAL = [
        'D' => 'D - Debet',
        'K' => 'K - Kredit',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Rekening')
                ->description('Lengkapi data rekening dengan benar. Nomor rekening harus unik.')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Grid::make(['sm' => 1, 'lg' => 12])->schema([

                        Select::make('kelompok_id')
                            ->label('Kelompok')
                            ->relationship(
                                name: 'kelompok',
                                titleAttribute: 'nama_kel',
                                modifyQueryUsing: fn(Builder $query) => $query->orderBy('no_kel')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, $set) =>
                                $set('kel', $state ? Kelompok::find($state)?->kel : null)
                            )
                            ->createOptionForm(fn() => static::getKelompokFormSchema())
                            ->createOptionUsing(function (array $data): int {
                                return Kelompok::create($data)->id;
                            })
                            ->helperText('Pilih atau buat kelompok baru.')
                            ->columnSpan(['lg' => 6]),

                        TextInput::make('no_rek')
                            ->label('Nomor Rekening')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999)
                            ->rules(['digits_between:1,4'])
                            ->unique(Rekening::class, 'no_rek', ignoreRecord: true)
                            ->placeholder('0101')
                            ->prefix('#')
                            ->helperText('Format: 4 digit (contoh: 1101, 2102)')
                            ->columnSpan(['lg' => 6]),

                        TextInput::make('nama_rek')
                            ->label('Nama Rekening')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Kas Kecil, Piutang Dagang')
                            ->helperText('Nama harus jelas dan sesuai standar akuntansi.')
                            ->columnSpan(['lg' => 8]),

                        Select::make('kode')
                            ->label('Saldo Normal')
                            ->options(self::SALDO_NORMAL)
                            ->required()
                            ->native(false)
                            ->placeholder('Pilih saldo normal...')
                            ->columnSpan(['lg' => 4]),

                        Select::make('kel')
                            ->label('Kategori')
                            ->options(self::KATEGORI_AKUNTANSI)
                            ->required()
                            ->native(false)
                            ->disabled(fn(Forms\Get $get) => filled($get('kelompok_id')))
                            ->dehydrated(
                                fn(Forms\Get $get, $state) =>
                                filled($get('kelompok_id')) ? Kelompok::find($get('kelompok_id'))?->kel : $state
                            )
                            ->helperText('Otomatis dari kelompok. Bisa diubah jika diperlukan.')
                            ->columnSpan(['lg' => 12]),

                    ]),
                ])
                ->columns(12)
                ->collapsible()
                ->collapsed(false),
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
                    ->formatStateUsing(fn($state) => str_pad($state, 4, '0', STR_PAD_LEFT))
                    ->badge()
                    ->color('success'),

                TextColumn::make('nama_rek')
                    ->label('Nama Rekening')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->nama_rek),

                BadgeColumn::make('kode')
                    ->label('Saldo Normal')
                    ->formatStateUsing(fn($state) => self::SALDO_NORMAL[$state] ?? $state)
                    ->colors([
                        'success' => 'D',
                        'danger' => 'K',
                    ])
                    ->icons([
                        'heroicon-o-arrow-up' => 'D',
                        'heroicon-o-arrow-down' => 'K',
                    ])
                    ->sortable(),

                BadgeColumn::make('nomor_bantus_count')
                    ->label('Nomor Bantu')
                    ->counts('nomorBantus')
                    ->color('primary')
                    ->icon('heroicon-o-document-duplicate'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kelompok')
                    ->relationship('kelompok', 'nama_kel')
                    ->searchable()
                    ->preload()
                    ->label('Filter Kelompok'),

                SelectFilter::make('kode')
                    ->options(self::SALDO_NORMAL)
                    ->label('Saldo Normal'),

                SelectFilter::make('kel')
                    ->options(self::KATEGORI_AKUNTANSI)
                    ->label('Kategori'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->color('primary'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('no_rek')
        ;
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\NomorBantusRelationManager::class,
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
        return number_format(static::getModel()::count());
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total rekening terdaftar di sistem';
    }

    // Helper: Form untuk Create Option Kelompok
    protected static function getKelompokFormSchema(): array
    {
        return [
            TextInput::make('no_kel')
                ->label('Nomor Kelompok')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(99)
                ->rules(['digits_between:1,2'])
                ->unique(Kelompok::class, 'no_kel')
                ->placeholder('01')
                ->prefix('#')
                ->helperText('2 digit, unik.'),

            TextInput::make('nama_kel')
                ->label('Nama Kelompok')
                ->required()
                ->maxLength(255)
                ->placeholder('Contoh: Kas, Piutang'),

            Select::make('kel')
                ->label('Kategori')
                ->options(self::KATEGORI_AKUNTANSI)
                ->required()
                ->native(false),
        ];
    }
}
