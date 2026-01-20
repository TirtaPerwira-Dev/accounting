<?php

namespace App\Filament\Admin\Widgets;

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAuthenticationLogTableWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AuthenticationLog::query()
                    ->with('authenticatable')
                    ->latest('login_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('authenticatable.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),

                Tables\Columns\TextColumn::make('login_successful')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state): string => match ((string) $state) {
                        '1' => 'success',
                        '0' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state): string => match ((string) $state) {
                        '1' => 'Success',
                        '0' => 'Failed',
                        default => 'Unknown',
                    }),

                Tables\Columns\TextColumn::make('login_at')
                    ->label('Login')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('logout_at')
                    ->label('Logout')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->heading('Authentication Log Terbaru')
            ->description('10 aktivitas login terakhir')
            ->paginated(false);
    }
}
