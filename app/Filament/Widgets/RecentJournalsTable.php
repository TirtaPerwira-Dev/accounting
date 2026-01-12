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
    protected static ?string $pollingInterval = '60s'; // Reduced polling frequency
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Journal::query()
                    ->select(['id', 'transaction_date', 'reference', 'transaction_type', 'description', 'total_amount', 'status', 'created_by', 'posted_by', 'created_at'])
                    ->with(['createdBy:id,name', 'postedBy:id,name']) // Select only needed columns
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
                // Actions removed - Journal resource has been deleted
            ])
            ->defaultSort('created_at', 'desc');
    }
}
