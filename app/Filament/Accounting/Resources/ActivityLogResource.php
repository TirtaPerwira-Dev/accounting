<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\ActivityLogResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $navigationGroup = 'Bantuan';

    protected static ?int $navigationGroupSort = 999;

    protected static ?int $navigationSort = 999;

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static ?string $slug = 'activity-log';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->latest();

        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query
            ->where('causer_id', $user->id)
            ->where('causer_type', 'App\\Models\\User');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Modul')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Tipe Data')
                    ->formatStateUsing(fn($state) => $state ? class_basename($state) : '-')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('properties')
                    ->label('Detail Perubahan')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';

                        $attributes = $state['attributes'] ?? [];
                        $old = $state['old'] ?? [];

                        if (!empty($attributes) || !empty($old)) {
                            $changes = [];

                            foreach ($attributes as $key => $value) {
                                if (isset($old[$key]) && $old[$key] != $value) {
                                    $changes[] = "$key: {$old[$key]} → $value";
                                } elseif (!isset($old[$key])) {
                                    $changes[] = "$key: $value";
                                }
                            }

                            return implode(', ', $changes) ?: '-';
                        }

                        return '-';
                    })
                    ->limit(60)
                    ->wrap()
                    ->toggleable()
                    ->searchable(false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->since()
                    ->description(fn($record) => $record->created_at->format('d M Y, H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Modul')
                    ->options([
                        'default' => 'Default',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['sampai'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Belum ada aktivitas')
            ->emptyStateDescription('Aktivitas yang Anda lakukan akan tercatat di sini')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return auth()->check();
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
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
