<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthenticationLogResource\Pages;
use App\Filament\Resources\AuthenticationLogResource\RelationManagers;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class AuthenticationLogResource extends Resource
{
    protected static ?string $model = AuthenticationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Authentication Logs';
    
    protected static ?string $navigationGroup = 'Monitoring';
    
    protected static ?int $navigationSort = 10;

    // Use permission-based access
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_authentication::log') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_authentication::log') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_authentication::log') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_any_authentication::log') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Authentication logs are read-only
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('authenticatable.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        
                        return $state;
                    }),
                TextColumn::make('login_at')
                    ->label('Login At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('login_successful')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Success',
                        '0' => 'Failed',
                    }),
                TextColumn::make('logout_at')
                    ->label('Logout At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Location')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('login_successful')
                    ->label('Status')
                    ->options([
                        '1' => 'Success',
                        '0' => 'Failed',
                    ]),
                Filter::make('login_at')
                    ->form([
                        Forms\Components\DatePicker::make('login_from')
                            ->label('Login From'),
                        Forms\Components\DatePicker::make('login_until')
                            ->label('Login Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['login_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('login_at', '>=', $date),
                            )
                            ->when(
                                $data['login_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('login_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('login_at', 'desc');
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
            'index' => Pages\ListAuthenticationLogs::route('/'),
            'view' => Pages\ViewAuthenticationLog::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Authentication logs are created automatically
    }
}
