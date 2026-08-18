<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\AuthenticationLogResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthenticationLogResource extends Resource
{
    protected static ?string $model = AuthenticationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Security Logs';

    protected static ?string $navigationGroup = 'Bantuan';

    protected static ?int $navigationGroupSort = 999;

    protected static ?int $navigationSort = 1000;

    protected static ?string $pluralModelLabel = 'Security Logs';

    protected static ?string $slug = 'security-logs';

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canView($record): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->latest('login_at');

        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query
            ->where('authenticatable_type', User::class)
            ->where('authenticatable_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('authenticatable.name')
                    ->label('User')
                    ->default('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),

                TextColumn::make('login_successful')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state): string => (bool) $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state): string => (bool) $state ? 'Success' : 'Failed'),

                TextColumn::make('login_at')
                    ->label('Login At')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('logout_at')
                    ->label('Logout At')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(45)
                    ->tooltip(fn(TextColumn $column): ?string => $column->getState()),
            ])
            ->defaultSort('login_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthenticationLogs::route('/'),
            'view' => Pages\ViewAuthenticationLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
