<?php

namespace App\Filament\Admin\Widgets;

use Spatie\Activitylog\Models\Activity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityLogTableWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->with(['causer', 'subject'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('System'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn($state) => class_basename($state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->heading('Activity Log Terbaru')
            ->description('10 aktivitas terakhir yang tercatat dalam sistem')
            ->paginated(false);
    }
}
