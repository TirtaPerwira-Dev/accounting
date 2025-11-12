<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingStandardResource\Pages;
use App\Filament\Resources\AccountingStandardResource\RelationManagers;
use App\Models\AccountingStandard;
use App\Services\AccountingValidationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AccountingStandardResource extends Resource
{
    protected static ?string $model = AccountingStandard::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Standar Akuntansi';

    protected static ?string $modelLabel = 'Standar Akuntansi';

    protected static ?string $pluralModelLabel = 'Standar Akuntansi';

    protected static ?string $navigationGroup = '1. Setup & Konfigurasi';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Masukkan detail dasar untuk standar akuntansi')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Standar')
                            ->required()
                            ->maxLength(10)
                            ->unique(AccountingStandard::class, 'code', ignoreRecord: true)
                            ->regex('/^[A-Z]{2,10}$/')
                            ->helperText('Gunakan huruf kapital saja (mis: PSAK, SAKEP)')
                            ->placeholder('PSAK')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Standar')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('mis: PSAK - Perusahaan Publik')
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder('Jelaskan kapan dan dimana standar ini harus digunakan')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Hanya standar aktif yang dapat ditugaskan ke perusahaan')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Usage Statistics')
                    ->description('Statistics about this standard usage')
                    ->schema([
                        Forms\Components\Placeholder::make('companies_count')
                            ->label('Perusahaan Pengguna')
                            ->content(
                                fn(AccountingStandard $record): string =>
                                $record->exists ? $record->companies()->count() . ' perusahaan' : 'Belum dibuat'
                            ),
                    ])
                    ->columns(1)
                    ->visible(fn(string $operation): bool => $operation === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(
                        fn(AccountingStandard $record): string =>
                        str($record->description)->limit(50)
                    ),



                Tables\Columns\TextColumn::make('companies_count')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('success')
                    ->counts('companies'),

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

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua standar')
                    ->trueLabel('Hanya aktif')
                    ->falseLabel('Hanya tidak aktif'),



                Tables\Filters\Filter::make('has_companies')
                    ->label('Digunakan oleh Perusahaan')
                    ->query(fn(Builder $query): Builder => $query->has('companies'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->before(function (AccountingStandard $record) {
                            if ($record->companies()->exists()) {
                                Notification::make()
                                    ->title('Tidak dapat menghapus')
                                    ->body('Standar ini sedang digunakan oleh perusahaan.')
                                    ->danger()
                                    ->send();

                                return false;
                            }
                        }),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn(AccountingStandard $record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                        ->icon(fn(AccountingStandard $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn(AccountingStandard $record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function (AccountingStandard $record) {
                            $record->update(['is_active' => !$record->is_active]);

                            Notification::make()
                                ->title('Status berhasil diperbarui')
                                ->body("Standar {$record->code} sekarang " . ($record->is_active ? 'aktif' : 'tidak aktif'))
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->before(function (Collection $records) {
                            $usedStandards = $records->filter(fn($record) => $record->companies()->exists());

                            if ($usedStandards->count() > 0) {
                                Notification::make()
                                    ->title('Tidak dapat menghapus beberapa standar')
                                    ->body($usedStandards->count() . ' standar sedang digunakan oleh perusahaan.')
                                    ->warning()
                                    ->send();

                                return $records->reject(fn($record) => $record->companies()->exists());
                            }

                            return $records;
                        }),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan yang Dipilih')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn($record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title('Standar berhasil diaktifkan')
                                ->body($records->count() . ' standar telah diaktifkan.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan yang Dipilih')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn($record) => $record->update(['is_active' => false]));

                            Notification::make()
                                ->title('Standar berhasil dinonaktifkan')
                                ->body($records->count() . ' standar telah dinonaktifkan.')
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('code')
            ->striped()
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['companies']);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CompaniesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingStandards::route('/'),
            'create' => Pages\CreateAccountingStandard::route('/create'),
            'view' => Pages\ViewAccountingStandard::route('/{record}'),
            'edit' => Pages\EditAccountingStandard::route('/{record}/edit'),
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
}
