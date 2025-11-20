<?php

namespace App\Filament\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Resources\JurnalRekeningAirResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewJurnalRekeningAir extends ViewRecord
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->canBeEdited()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                // ===================== INFORMASI JURNAL =====================
                Infolists\Components\Section::make('Informasi Jurnal')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('no_reff')
                                ->label('No. Referensi')
                                ->copyable()
                                ->icon('heroicon-m-hashtag'),

                            Infolists\Components\TextEntry::make('bukti')
                                ->label('No. Bukti')
                                ->weight('bold')
                                ->icon('heroicon-m-document-magnifying-glass'),

                            Infolists\Components\TextEntry::make('tanggal')
                                ->label('Tanggal')
                                ->date('d F Y')
                                ->icon('heroicon-m-calendar-days'),
                        ]),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                // ===================== DETAIL TRANSAKSI (YANG DIPERBAIKI TOTAL) =====================
                Infolists\Components\Section::make('Detail Transaksi')
                    ->icon('heroicon-o-table-cells')
                    ->description('Total baris: ' . collect($this->record->rekening_air_items)->count())
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('rekening_air_items')
                            ->label(false)
                            ->grid(1)
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Grid::make(6)->schema([

                                            // KODE PROYEK
                                            Infolists\Components\TextEntry::make('kode_proyek')
                                                ->label('Proyek')
                                                ->default('-')
                                                ->formatStateUsing(fn($state) => $state ?
                                                    optional(\App\Models\KodeProyek::find($state))->kode . ' - ' .
                                                    optional(\App\Models\KodeProyek::find($state))->name : '-')
                                                ->columnSpan(2),

                                            // REKENING
                                            Infolists\Components\TextEntry::make('rekening')
                                                ->label('Rekening')
                                                ->formatStateUsing(fn($state) => $state ?
                                                    optional(\App\Models\Rekening::with('kelompok')->find($state))
                                                    ->kelompok->no_kel . '-' .
                                                    optional(\App\Models\Rekening::find($state))->no_rek . ' - ' .
                                                    optional(\App\Models\Rekening::find($state))->nama_rek : '-')
                                                ->columnSpan(3),

                                            // NOMOR BANTU
                                            Infolists\Components\TextEntry::make('nomor_bantu')
                                                ->label('Nomor Bantu')
                                                ->default('-')
                                                ->formatStateUsing(fn($state) => $state ?
                                                    optional(\App\Models\NomorBantu::find($state))
                                                    ->no_bantu . ' - ' .
                                                    optional(\App\Models\NomorBantu::find($state))->nm_bantu : '-')
                                                ->columnSpan(2),

                                            // POSISI D/K (BADGE)
                                            Infolists\Components\TextEntry::make('position')
                                                ->label('Posisi')
                                                ->badge()
                                                ->size('lg')
                                                ->color(fn($state) => $state === 'debit' ? 'danger' : 'success')
                                                ->formatStateUsing(fn($state) => $state === 'debit' ? 'DEBIT' : 'KREDIT')
                                                ->columnSpan(1),

                                            // JUMLAH — INI YANG DIPERBAIKI 100%!
                                            Infolists\Components\TextEntry::make('jumlah')
                                                ->label('')
                                                ->money('IDR')
                                                ->size('xl')
                                                ->weight('bold')
                                                ->alignEnd()
                                                ->columnSpan(2)
                                                // INI BARIS YANG BENAR-BENAR JALAN (PAKAI $state dari position + get())
                                                ->color(fn($state, $record) => data_get($record, 'position') === 'debit' ? 'danger' : 'success'),
                                        ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->description(fn($state) => $state['position'] === 'debit'
                                        ? 'Transaksi Debit — Mengurangi saldo rekening'
                                        : 'Transaksi Kredit — Menambah saldo rekening')
                                    ->icon(fn($state) => $state['position'] === 'debit'
                                        ? 'heroicon-o-arrow-up-right'
                                        : 'heroicon-o-arrow-down-right')
                                    ->iconColor(fn($state) => $state['position'] === 'debit' ? 'danger' : 'success'),
                            ])
                            ->columnSpanFull(),
                    ]),

                // ===================== RINGKASAN TOTAL =====================
                Infolists\Components\Section::make('Ringkasan')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('total_debit')
                                ->label('Total Debit')
                                ->state(function ($record) {
                                    return collect($record->rekening_air_items)
                                        ->where('position', 'debit')
                                        ->sum('jumlah');
                                })
                                ->money('IDR')
                                ->color('danger')
                                ->size('xl')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('total_kredit')
                                ->label('Total Kredit')
                                ->state(function ($record) {
                                    return collect($record->rekening_air_items)
                                        ->where('position', 'kredit')
                                        ->sum('jumlah');
                                })
                                ->money('IDR')
                                ->color('success')
                                ->size('xl')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('balance_status')
                                ->label('Status Jurnal')
                                ->state(function ($record) {
                                    $items = $record->rekening_air_items ?? [];
                                    $debit = collect($items)->where('position', 'debit')->sum('jumlah');
                                    $kredit = collect($items)->where('position', 'kredit')->sum('jumlah');
                                    return $debit === $kredit && $debit > 0 ? 'JURNAL BALANCE' : 'TIDAK BALANCE';
                                })
                                ->badge()
                                ->color(fn($state) => str_contains($state, 'BALANCE') ? 'success' : 'danger')
                                ->size('xl'),
                        ]),
                    ]),

                // ===================== STATUS & AUDIT =====================
                Infolists\Components\Section::make('Status & Audit')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\IconEntry::make('is_confirmed')
                                ->label('Status Konfirmasi')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-badge')
                                ->falseIcon('heroicon-o-clock')
                                ->trueColor('success')
                                ->falseColor('warning'),

                            Infolists\Components\TextEntry::make('confirmed_at')
                                ->label('Dikonfirmasi Pada')
                                ->dateTime('d F Y H:i')
                                ->placeholder('Belum dikonfirmasi'),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Dibuat Pada')
                                ->dateTime('d F Y H:i'),
                        ]),
                    ]),
            ]);
    }
}
