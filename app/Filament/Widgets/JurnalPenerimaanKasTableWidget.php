<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\JurnalPenerimaanKas;
use App\Filament\Resources\JurnalPenerimaanKasResource;
use Illuminate\Database\Eloquent\Builder;

class JurnalPenerimaanKasTableWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JurnalPenerimaanKas::query()
                    ->with(['kasBank.rekening.kelompok'])
                    ->latest('created_at')
                    ->limit(10)
            )
            ->heading('📋 Jurnal Penerimaan Kas Terbaru')
            ->description('10 transaksi JPK terbaru yang diinput')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar-days')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nomor_bukti')
                    ->label('No. Bukti')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-document-text')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('kasBank.nm_bantu')
                    ->label('Kas/Bank')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn($record) => $record->kasBank?->nm_bantu ?? 'N/A')
                    ->icon('heroicon-m-banknotes')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_penerimaan')
                    ->label('Total')
                    ->getStateUsing(function ($record) {
                        $total = collect($record->detail_penerimaan ?? [])->sum('jumlah');
                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->sortable()
                    ->alignEnd()
                    ->icon('heroicon-m-currency-dollar')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('detail_count')
                    ->label('Item')
                    ->getStateUsing(function ($record) {
                        return count($record->detail_penerimaan ?? []) . ' item';
                    })
                    ->alignCenter()
                    ->icon('heroicon-m-list-bullet')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->keterangan ?: 'Tidak ada keterangan')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('reff')
                    ->label('Reff')
                    ->color('info')
                    ->icon('heroicon-m-hashtag'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->icon('heroicon-m-clock')
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->url(fn ($record) => route('jurnal-penerimaan-kas.pdf', $record))
                        ->openUrlInNewTab(),
                        
                    Tables\Actions\Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->url(fn ($record) => JurnalPenerimaanKasResource::getUrl('edit', ['record' => $record])),
                ])
                    ->label('Actions')
                    ->color('gray')
                    ->size('sm'),
            ])
            ->emptyStateHeading('Belum ada transaksi JPK')
            ->emptyStateDescription('Mulai dengan membuat jurnal penerimaan kas pertama')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->poll('30s') // Auto refresh setiap 30 detik
            ->striped()
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        return true;
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10];
    }
}
