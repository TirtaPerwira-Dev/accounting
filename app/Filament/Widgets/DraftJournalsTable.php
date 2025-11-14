<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Journal;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class DraftJournalsTable extends BaseWidget
{
    protected static ?string $heading = 'Jurnal Draft (Menunggu Posting)';
    protected static ?int $sort = 6;
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
                    ->with(['createdBy'])
                    ->where('status', 'draft')
                    ->orderBy('created_at', 'desc')
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
                    ->limit(40)
                    ->tooltip(fn(Journal $record): string => $record->description)
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->alignEnd()
                    ->getStateUsing(function (Journal $record): float {
                        return $record->details->sum('debit');
                    }),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->default('-'),

                TextColumn::make('created_at')
                    ->label('Waktu Input')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->since(),

                TextColumn::make('is_balanced')
                    ->label('Balanced')
                    ->badge()
                    ->getStateUsing(function (Journal $record): string {
                        $totalDebit = $record->details->sum('debit');
                        $totalCredit = $record->details->sum('credit');
                        return abs($totalDebit - $totalCredit) < 0.01 ? 'Yes' : 'No';
                    })
                    ->color(fn(string $state): string => $state === 'Yes' ? 'success' : 'danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn(Journal $record): string => route('filament.admin.resources.journals.edit', $record)),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Journal')
                    ->modalDescription('Are you sure you want to approve this journal?')
                    ->action(function (Journal $record) {
                        if ($record->canBePosted()) {
                            $record->post();
                            $this->dispatch('journal-approved');
                        }
                    })
                    ->visible(fn(Journal $record): bool => $record->canBePosted()),
            ])
            ->emptyStateDescription('Tidak ada jurnal yang menunggu approval.')
            ->emptyStateIcon('heroicon-o-document-check')
            ->defaultSort('created_at', 'desc');
    }
}
