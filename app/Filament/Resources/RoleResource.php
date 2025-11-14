<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Peran & Hak Akses';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    protected static ?int $navigationGroupSort = 7;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        // Group permissions by accounting modules/resources
        $permissions = Permission::all()->groupBy(function ($permission) {
            $name = $permission->name;

            // Categorize by accounting modules
            if (str_contains($name, 'journal') || str_contains($name, 'penerimaan') || str_contains($name, 'pengeluaran')) {
                return '📊 Transaksi Jurnal';
            } elseif (str_contains($name, 'kelompok') || str_contains($name, 'rekening') || str_contains($name, 'nomor_bantu')) {
                return '🏦 Chart of Accounts';
            } elseif (str_contains($name, 'opening_balance') || str_contains($name, 'company')) {
                return '⚙️ Setup & Konfigurasi';
            } elseif (str_contains($name, '_user')) {
                return '👥 Manajemen Pengguna';
            } elseif (str_contains($name, '_role')) {
                return '🔐 Manajemen Peran';
            } elseif (str_contains($name, '_authentication') || str_contains($name, 'activity')) {
                return '📋 Log & Audit';
            } elseif (str_contains($name, 'page_')) {
                return '📄 Halaman Sistem';
            } elseif (str_contains($name, 'widget_')) {
                return '📈 Dashboard Widgets';
            } else {
                return '🔧 Lainnya';
            }
        });

        $sections = [
            Section::make('Informasi Peran')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->label('Nama Peran')
                        ->placeholder('Contoh: super_admin, akuntan, kasir, direktur')
                        ->helperText('Gunakan format: huruf kecil, underscore untuk pemisah')
                        ->maxLength(255),
                ])
                ->icon('heroicon-o-identification')
                ->columnSpanFull(),
        ];

        foreach ($permissions as $module => $modulePermissions) {
            $moduleIcon = match ($module) {
                '📊 Transaksi Jurnal' => 'heroicon-o-document-text',
                '🏦 Chart of Accounts' => 'heroicon-o-building-library',
                '⚙️ Setup & Konfigurasi' => 'heroicon-o-cog-6-tooth',
                '👥 Manajemen Pengguna' => 'heroicon-o-users',
                '🔐 Manajemen Peran' => 'heroicon-o-shield-check',
                '📋 Log & Audit' => 'heroicon-o-clipboard-document-list',
                '📄 Halaman Sistem' => 'heroicon-o-document',
                '📈 Dashboard Widgets' => 'heroicon-o-chart-bar-square',
                default => 'heroicon-o-squares-2x2'
            };

            $sections[] = Section::make($module)
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->hiddenLabel()
                        ->options($modulePermissions->mapWithKeys(function ($permission) {
                            return [$permission->id => self::formatPermissionLabel($permission->name)];
                        })->toArray())
                        ->columns([
                            'sm' => 1,
                            'md' => 2,
                            'lg' => 3,
                            'xl' => 4,
                        ])
                        ->gridDirection('row')
                        ->descriptions($modulePermissions->mapWithKeys(function ($permission) {
                            return [$permission->id => self::formatPermissionDescription($permission->name)];
                        })->toArray())
                        ->bulkToggleable(),
                ])
                ->icon($moduleIcon)
                ->collapsible()
                ->collapsed(false)
                ->extraAttributes([
                    'class' => 'border-l-4 border-l-blue-500 bg-blue-50/50'
                ]);
        }

        return $form->schema($sections);
    }

    private static function formatPermissionLabel(string $permission): string
    {
        // Clean permission name for better readability
        $parts = explode('_', $permission);

        // Handle different permission formats
        if (count($parts) >= 2) {
            $action = $parts[0];
            $resource = implode('_', array_slice($parts, 1));

            // Action labels in Indonesian
            $actionLabels = [
                'view' => 'Lihat',
                'view_any' => 'Lihat Semua',
                'create' => 'Buat',
                'update' => 'Edit',
                'delete' => 'Hapus',
                'delete_any' => 'Hapus Semua',
                'restore' => 'Pulihkan',
                'restore_any' => 'Pulihkan Semua',
                'force_delete' => 'Hapus Permanen',
                'force_delete_any' => 'Hapus Permanen Semua',
                'replicate' => 'Duplikasi',
                'reorder' => 'Urutkan',
                'page' => 'Akses',
                'widget' => 'Widget',
            ];

            // Resource labels in Indonesian
            $resourceLabels = [
                'journal' => 'Jurnal Umum',
                'penerimaan_journal' => 'Jurnal Penerimaan',
                'pengeluaran_journal' => 'Jurnal Pengeluaran',
                'kelompok' => 'Kelompok Akun',
                'rekening' => 'Rekening',
                'nomor_bantu' => 'Nomor Bantu',
                'opening_balance' => 'Saldo Awal',
                'company' => 'Perusahaan',
                'user' => 'Pengguna',
                'role' => 'Peran',
                'authentication_log' => 'Log Autentikasi',
                'activity' => 'Aktivitas',
                'dashboard' => 'Dashboard',
            ];

            $actionLabel = $actionLabels[$action] ?? ucfirst($action);
            $resourceLabel = $resourceLabels[$resource] ?? ucwords(str_replace('_', ' ', $resource));

            return $actionLabel . ' ' . $resourceLabel;
        }

        return ucwords(str_replace('_', ' ', $permission));
    }

    private static function formatPermissionDescription(string $permission): string
    {
        // Convert permission name to readable description in Indonesian
        $parts = explode('_', $permission);

        if (count($parts) >= 2) {
            $action = $parts[0];
            $resource = implode('_', array_slice($parts, 1));

            // Detailed descriptions
            $descriptions = [
                'view_journal' => 'Dapat melihat detail jurnal umum',
                'view_any_journal' => 'Dapat melihat semua jurnal umum',
                'create_journal' => 'Dapat membuat jurnal umum baru',
                'update_journal' => 'Dapat mengedit jurnal umum',
                'delete_journal' => 'Dapat menghapus jurnal umum',

                'view_penerimaan_journal' => 'Dapat melihat jurnal penerimaan',
                'view_any_penerimaan_journal' => 'Dapat melihat semua jurnal penerimaan',
                'create_penerimaan_journal' => 'Dapat membuat jurnal penerimaan',
                'update_penerimaan_journal' => 'Dapat mengedit jurnal penerimaan',
                'delete_penerimaan_journal' => 'Dapat menghapus jurnal penerimaan',

                'view_pengeluaran_journal' => 'Dapat melihat jurnal pengeluaran',
                'view_any_pengeluaran_journal' => 'Dapat melihat semua jurnal pengeluaran',
                'create_pengeluaran_journal' => 'Dapat membuat jurnal pengeluaran',
                'update_pengeluaran_journal' => 'Dapat mengedit jurnal pengeluaran',
                'delete_pengeluaran_journal' => 'Dapat menghapus jurnal pengeluaran',

                'view_kelompok' => 'Dapat melihat kelompok akun',
                'view_any_kelompok' => 'Dapat melihat semua kelompok akun',
                'create_kelompok' => 'Dapat membuat kelompok akun baru',
                'update_kelompok' => 'Dapat mengedit kelompok akun',
                'delete_kelompok' => 'Dapat menghapus kelompok akun',

                'view_rekening' => 'Dapat melihat rekening',
                'view_any_rekening' => 'Dapat melihat semua rekening',
                'create_rekening' => 'Dapat membuat rekening baru',
                'update_rekening' => 'Dapat mengedit rekening',
                'delete_rekening' => 'Dapat menghapus rekening',

                'view_user' => 'Dapat melihat data pengguna',
                'view_any_user' => 'Dapat melihat semua pengguna',
                'create_user' => 'Dapat membuat pengguna baru',
                'update_user' => 'Dapat mengedit data pengguna',
                'delete_user' => 'Dapat menghapus pengguna',

                'view_role' => 'Dapat melihat peran',
                'view_any_role' => 'Dapat melihat semua peran',
                'create_role' => 'Dapat membuat peran baru',
                'update_role' => 'Dapat mengedit peran',
                'delete_role' => 'Dapat menghapus peran',
            ];

            if (isset($descriptions[$permission])) {
                return $descriptions[$permission];
            }

            // Fallback generic descriptions
            $actionLabels = [
                'view' => 'Dapat melihat',
                'view_any' => 'Dapat melihat semua',
                'create' => 'Dapat membuat',
                'update' => 'Dapat mengedit',
                'delete' => 'Dapat menghapus',
                'delete_any' => 'Dapat menghapus semua',
                'restore' => 'Dapat memulihkan',
                'restore_any' => 'Dapat memulihkan semua',
                'force_delete' => 'Dapat menghapus permanen',
                'force_delete_any' => 'Dapat menghapus permanen semua',
                'replicate' => 'Dapat menduplikasi',
                'reorder' => 'Dapat mengurutkan',
                'page' => 'Dapat mengakses halaman',
                'widget' => 'Dapat melihat widget',
            ];

            $resourceLabels = [
                'journal' => 'jurnal umum',
                'penerimaan_journal' => 'jurnal penerimaan',
                'pengeluaran_journal' => 'jurnal pengeluaran',
                'kelompok' => 'kelompok akun',
                'rekening' => 'rekening',
                'nomor_bantu' => 'nomor bantu',
                'opening_balance' => 'saldo awal',
                'company' => 'data perusahaan',
                'user' => 'pengguna',
                'role' => 'peran',
                'authentication_log' => 'log autentikasi',
                'activity' => 'log aktivitas',
            ];

            $actionLabel = $actionLabels[$action] ?? 'Dapat ' . $action;
            $resourceLabel = $resourceLabels[$resource] ?? str_replace('_', ' ', $resource);

            return $actionLabel . ' ' . $resourceLabel;
        }

        return 'Akses ' . str_replace('_', ' ', $permission);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Peran')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'akuntan' => 'info',
                        'kasir' => 'success',
                        'direktur' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Total Hak Akses')
                    ->sortable()
                    ->badge()
                    ->color('blue'),

                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Jumlah Pengguna')
                    ->sortable()
                    ->badge()
                    ->color('green'),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('name')
                    ->label('Filter Peran')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'akuntan' => 'Akuntan',
                        'kasir' => 'Kasir',
                        'direktur' => 'Direktur',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->visible(fn(Role $record): bool => $record->name !== 'super_admin')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Peran')
                        ->modalDescription('Apakah Anda yakin ingin menghapus peran ini? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus'),
                    Tables\Actions\ViewAction::make()
                        ->label('View'),
                ])
                    ->color('primary') // Warna tombol utama
                    ->icon('heroicon-o-ellipsis-vertical') // Icon dropdown
                    ->size('sm') // Ukuran kecil
                    ->button(), // Menjadikan dropdown sebagai tombol (bukan dropdown biasa)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->reject(fn(Role $role) => $role->name === 'super_admin')->each->delete();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Peran Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus peran-peran terpilih? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus'),
                ]),
            ])
            ->defaultSort('name')
            ->recordTitleAttribute('name')
            ->emptyStateHeading('Belum ada peran')
            ->emptyStateDescription('Buat peran pertama untuk mengatur hak akses pengguna')
            ->emptyStateIcon('heroicon-o-shield-check');
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['permissions', 'users']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 5 ? 'warning' : 'success';
    }
}
