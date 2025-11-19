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
                Infolists\Components\Section::make('Informasi Jurnal')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('no_reff')
                                    ->label('No. Referensi'),
                                Infolists\Components\TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d F Y'),
                                Infolists\Components\TextEntry::make('bukti')
                                    ->label('No. Bukti'),
                                Infolists\Components\TextEntry::make('rp')
                                    ->label('Total')
                                    ->money('IDR'),
                            ]),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Akun Kredit (Pendapatan)')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_kredit_account')
                            ->label('Akun Kredit')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Items Piutang')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('rekening_air_items')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('kelompok_debit_id')
                                            ->label('Akun Debit')
                                            ->formatStateUsing(function ($record, $state) {
                                                $kelompok = \App\Models\Kelompok::find($state);
                                                $rekening = \App\Models\Rekening::find($record['rekening_debit_id'] ?? null);
                                                $nomorBantu = null;
                                                if (isset($record['nomor_bantu_debit_id'])) {
                                                    $nomorBantu = \App\Models\NomorBantu::find($record['nomor_bantu_debit_id']);
                                                }

                                                $parts = [];
                                                if ($kelompok) $parts[] = $kelompok->no_kel . ' - ' . $kelompok->nama_kel;
                                                if ($rekening) $parts[] = $rekening->no_rek . ' - ' . $rekening->nama_rek;
                                                if ($nomorBantu) $parts[] = $nomorBantu->no_bantu . ' - ' . $nomorBantu->nm_bantu;

                                                return implode(' | ', $parts);
                                            }),
                                        Infolists\Components\TextEntry::make('rp')
                                            ->label('Jumlah')
                                            ->money('IDR'),
                                    ]),

                                Infolists\Components\TextEntry::make('keterangan')
                                    ->label('Keterangan')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Status & Audit')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('is_confirmed')
                                    ->label('Status Konfirmasi')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Infolists\Components\TextEntry::make('confirmed_at')
                                    ->label('Dikonfirmasi Pada')
                                    ->dateTime('d F Y H:i')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
