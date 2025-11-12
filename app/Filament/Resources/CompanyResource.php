<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\AccountingStandard;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Profil Perusahaan';

    protected static ?string $modelLabel = 'Perusahaan';

    protected static ?string $pluralModelLabel = 'Perusahaan';

    protected static ?string $navigationGroup = '1. Setup & Konfigurasi';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        // Only allow create if no company exists
        return Company::count() === 0;
    }

    public static function getNavigationUrl(): string
    {
        $company = Company::first();

        if ($company) {
            // If company exists, go directly to view page
            return static::getUrl('view', ['record' => $company]);
        }

        // If no company exists, go to create page
        return static::getUrl('create');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Perusahaan')
                    ->description('Masukkan detail dasar perusahaan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('contoh: PDAM Tirta Jaya')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('npwp')
                            ->label('NPWP')
                            ->required()
                            ->maxLength(20)
                            ->unique(Company::class, 'npwp', ignoreRecord: true)
                            ->regex('/^[0-9]{2}\.[0-9]{3}\.[0-9]{3}\.[0-9]-[0-9]{3}\.[0-9]{3}$/')
                            ->placeholder('XX.XXX.XXX.X-XXX.XXX')
                            ->helperText('Format: XX.XXX.XXX.X-XXX.XXX')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Alamat lengkap perusahaan')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+62-XXX-XXXX-XXXX')
                            ->columnSpan(1),

                        Forms\Components\Select::make('accounting_standard_id')
                            ->label('Standar Akuntansi')
                            ->relationship('standard', 'name')
                            ->options(
                                AccountingStandard::where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih standar akuntansi untuk perusahaan ini')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Hanya perusahaan aktif yang dapat melakukan transaksi')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Logo & Konfigurasi')
                    ->description('Upload logo perusahaan dan atur konfigurasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('logo')
                                    ->label('Logo Perusahaan')
                                    ->image()
                                    ->maxSize(2048) // 2MB max
                                    ->directory('company-logos')
                                    ->visibility('public')
                                    ->disk('public')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '1:1',
                                        '16:9',
                                        '4:3',
                                    ])
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('300')
                                    ->imageResizeTargetHeight('300')
                                    ->helperText('Upload logo perusahaan. Maks 2MB, format JPG/PNG. Rasio 1:1 disarankan.')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->uploadingMessage('Mengupload logo...')
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('current_logo')
                                    ->label('Logo Saat Ini')
                                    ->content(function (?Company $record): string {
                                        if (!$record || !$record->logo) {
                                            return '<div class="text-gray-500 italic">Belum ada logo yang diupload</div>';
                                        }

                                        $logoUrl = asset('storage/' . $record->logo);
                                        return '<div class="flex flex-col space-y-2">
                                            <img src="' . $logoUrl . '" alt="Logo Perusahaan" class="w-24 h-24 object-cover rounded-lg shadow-sm border">
                                            <div class="text-sm text-gray-600">
                                                <div><strong>File:</strong> ' . basename($record->logo) . '</div>
                                                <div><strong>URL:</strong> <a href="' . $logoUrl . '" target="_blank" class="text-blue-600 hover:underline">Lihat Logo</a></div>
                                            </div>
                                        </div>';
                                    })
                                    ->columnSpan(1)
                                    ->visible(fn(string $operation): bool => $operation === 'edit'),
                            ]),

                        Forms\Components\KeyValue::make('config')
                            ->label('Konfigurasi')
                            ->addActionLabel('Tambah item konfigurasi')
                            ->keyLabel('Pengaturan')
                            ->valueLabel('Nilai')
                            ->default([
                                'ppn_rate' => '11',
                                'currency' => 'IDR',
                                'fiscal_year_start' => '01-01'
                            ])
                            ->columnSpan(2),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Statistik')
                    ->description('Statistik penggunaan perusahaan')
                    ->schema([
                        Forms\Components\Placeholder::make('journals_count')
                            ->label('Total Jurnal')
                            ->content(
                                fn(Company $record): string =>
                                $record->exists ? $record->journals()->count() . ' jurnal' : 'Belum ada jurnal'
                            ),

                        Forms\Components\Placeholder::make('standard_info')
                            ->label('Standar Akuntansi')
                            ->content(
                                fn(Company $record): string =>
                                $record->exists && $record->standard ? $record->standard->name : 'Belum dipilih'
                            ),

                        Forms\Components\Placeholder::make('sakep_initialized')
                            ->label('Struktur SAKEP')
                            ->content(
                                fn(Company $record): string =>
                                $record->exists && $record->isSakepInitialized() ? 'Telah diinisialisasi' : 'Belum diinisialisasi'
                            ),
                    ])
                    ->columns(3)
                    ->visible(fn(string $operation): bool => $operation === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Perusahaan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn(Company $record): string =>
                        $record->address ? str($record->address)->limit(30) : ''
                    ),

                Tables\Columns\TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->copyable()
                    ->placeholder('Belum diatur'),

                Tables\Columns\TextColumn::make('standard.code')
                    ->label('Standar')
                    ->badge()
                    ->color('info')
                    ->placeholder('Belum diatur'),

                Tables\Columns\TextColumn::make('journals_count')
                    ->label('Jurnal')
                    ->badge()
                    ->color('success')
                    ->counts('journals'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->placeholder('Belum diatur')
                    ->toggleable(),

                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(function () {
                        // Create a simple default image URL or use a placeholder
                        return 'data:image/svg+xml;base64,' . base64_encode('
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" fill="#f3f4f6"/>
                                <path d="M20 12C16.6863 12 14 14.6863 14 18C14 21.3137 16.6863 24 20 24C23.3137 24 26 21.3137 26 18C26 14.6863 23.3137 12 20 12Z" fill="#9ca3af"/>
                                <path d="M10 32C10 28.6863 13.5817 26 18 26H22C26.4183 26 30 28.6863 30 32V34H10V32Z" fill="#9ca3af"/>
                            </svg>
                        ');
                    })
                    ->tooltip(function ($record): ?string {
                        return $record && $record->logo ? 'Logo: ' . basename($record->logo) : 'Belum ada logo';
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua perusahaan')
                    ->trueLabel('Hanya aktif')
                    ->falseLabel('Hanya tidak aktif'),

                Tables\Filters\SelectFilter::make('accounting_standard_id')
                    ->label('Standar Akuntansi')
                    ->relationship('standard', 'name')
                    ->preload(),

                Tables\Filters\Filter::make('has_journals')
                    ->label('Memiliki Jurnal')
                    ->query(fn(Builder $query): Builder => $query->has('journals'))
                    ->toggle(),

                Tables\Filters\Filter::make('has_logo')
                    ->label('Memiliki Logo')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('logo'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Profil')
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit Profil')
                        ->color('warning'),
                    // Remove delete action and toggle status for single company mode
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                // No bulk actions for single company mode
            ])
            ->defaultSort('name')
            ->striped()
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['journals'])
            ->with(['standard']);
    }

    public static function getRelations(): array
    {
        return [
            // AccountsRelationManager removed - using SAKEP structure now
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Add any data mutation needed before creating
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Add any data mutation needed before saving
        return $data;
    }
}
