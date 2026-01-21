<?php

namespace App\Filament\Accounting\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

class UserActivityLogWidget extends BaseWidget
{
    protected static ?int $sort = 10;
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        return auth()->check();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->where('causer_id', auth()->id())
                    ->where('causer_type', 'App\Models\User')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Modul')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Tipe Data')
                    ->formatStateUsing(fn($state) => class_basename($state))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('properties')
                    ->label('Detail')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        
                        $attributes = $state['attributes'] ?? [];
                        $old = $state['old'] ?? [];
                        
                        if (!empty($attributes)) {
                            $preview = collect($attributes)->take(3)->map(function ($value, $key) {
                                return "$key: " . (is_array($value) ? json_encode($value) : $value);
                            })->join(', ');
                            return strlen($preview) > 50 ? substr($preview, 0, 50) . '...' : $preview;
                        }
                        
                        return '-';
                    })
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn($record) => $record->created_at->format('d/m/Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->heading('Aktivitas Saya')
            ->description('Log aktivitas yang Anda lakukan di sistem')
            ->emptyStateHeading('Belum ada aktivitas')
            ->emptyStateDescription('Aktivitas yang Anda lakukan akan tercatat di sini')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
