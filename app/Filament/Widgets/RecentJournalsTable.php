<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Journal;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class RecentJournalsTable extends BaseWidget
{
    protected static ?string $heading = 'Jurnal Terbaru (10 Terakhir)';
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Journal::query()
                    ->with(['createdBy', 'postedBy'])
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('reference')
                    ->label('No. Ref')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('transaction_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'penerimaan' => 'success',
                        'pengeluaran' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'penerimaan' => 'Penerimaan',
                        'pengeluaran' => 'Pengeluaran',
                        default => $state,
                    }),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->tooltip(fn(Journal $record): string => $record->description)
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->alignEnd(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'posted' => 'success',
                        'draft' => 'warning',
                        'reversed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'posted' => 'Posted',
                        'draft' => 'Draft',
                        'reversed' => 'Reversed',
                        default => $state,
                    }),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->default('-'),

                TextColumn::make('created_at')
                    ->label('Waktu Input')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Journal $record): string => route('filament.admin.resources.journals.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
