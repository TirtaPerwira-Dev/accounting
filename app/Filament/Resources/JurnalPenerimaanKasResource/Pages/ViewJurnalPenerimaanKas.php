<?php

namespace App\Filament\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Resources\JurnalPenerimaanKasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewJurnalPenerimaanKas extends ViewRecord
{
    protected static string $resource = JurnalPenerimaanKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("back_to_list")
                ->label("Kembali ke List")
                ->icon("heroicon-o-arrow-left")
                ->color("gray")
                ->url(fn() => static::getResource()::getUrl("index")),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $parentJurnal = $this->record->jurnalPenerimaanKas;
        
        return $infolist
            ->schema([
                Infolists\Components\Section::make("Informasi Jurnal")
                    ->icon("heroicon-o-document-text")
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.reff")
                                ->label("Referensi")
                                ->copyable()
                                ->badge()
                                ->color("primary")
                                ->icon("heroicon-m-hashtag"),

                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.tanggal")
                                ->label("Tanggal")
                                ->date("d F Y")
                                ->icon("heroicon-m-calendar-days"),

                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.kasBank.nm_bantu")
                                ->label("Kas/Bank (Tujuan)")
                                ->formatStateUsing(fn($record) => $record->jurnalPenerimaanKas?->kasBank ? 
                                    $record->jurnalPenerimaanKas->kasBank->no_bantu . " - " . 
                                    $record->jurnalPenerimaanKas->kasBank->nm_bantu : "-")
                                ->icon("heroicon-m-banknotes"),
                        ]),

                        Infolists\Components\TextEntry::make("jurnalPenerimaanKas.keterangan")
                            ->label("Keterangan")
                            ->columnSpanFull()
                            ->placeholder("-"),
                    ]),

                Infolists\Components\Section::make("Detail Sumber Penerimaan")
                    ->icon("heroicon-o-table-cells")
                    ->description(function () use ($parentJurnal) {
                        $parentJurnal->loadMissing("details");
                        return "Total sumber: " . $parentJurnal->details->count();
                    })
                    ->schema([
                        Infolists\Components\RepeatableEntry::make("jurnalPenerimaanKas.details")
                            ->label(false)
                            ->grid(1)
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Grid::make(4)->schema([
                                            Infolists\Components\TextEntry::make("nomor_bukti")
                                                ->label("Nomor Bukti")
                                                ->default("-"),

                                            Infolists\Components\TextEntry::make("kodeProyek.name")
                                                ->label("Proyek")
                                                ->default("-")
                                                ->formatStateUsing(fn($record) => $record->kodeProyek ? 
                                                    $record->kodeProyek->kode . " - " . $record->kodeProyek->name : "-"),

                                            Infolists\Components\TextEntry::make("rekening.nama_rek")
                                                ->label("Rekening (Sumber)")
                                                ->formatStateUsing(fn($record) => $record->rekening ?
                                                    $record->rekening->kelompok->no_kel . "-" .
                                                    $record->rekening->no_rek . " " .
                                                    $record->rekening->nama_rek : "-")
                                                ->columnSpan(2),

                                            Infolists\Components\TextEntry::make("nomorBantu.nm_bantu")
                                                ->label("Nomor Bantu")
                                                ->default("-")
                                                ->formatStateUsing(fn($record) => $record->nomorBantu ?
                                                    $record->nomorBantu->no_bantu . " - " .
                                                    $record->nomorBantu->nm_bantu : "-"),

                                            Infolists\Components\TextEntry::make("jumlah")
                                                ->label("Jumlah")
                                                ->money("IDR")
                                                ->size("xl")
                                                ->weight("bold")
                                                ->alignEnd()
                                                ->color("success"),

                                            Infolists\Components\TextEntry::make("keterangan_item")
                                                ->label("Keterangan")
                                                ->default("-")
                                                ->columnSpanFull(),
                                        ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->icon("heroicon-o-currency-dollar")
                                    ->iconColor("success"),
                            ])
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make("Total Penerimaan")
                    ->icon("heroicon-o-calculator")
                    ->schema([
                        Infolists\Components\TextEntry::make("total")
                            ->label("Total Penerimaan")
                            ->state(function () use ($parentJurnal) {
                                $parentJurnal->loadMissing("details");
                                return $parentJurnal->details->sum("jumlah");
                            })
                            ->money("IDR")
                            ->color("success")
                            ->size("xl")
                            ->weight("bold"),
                    ]),

                Infolists\Components\Section::make("Informasi Sistem")
                    ->icon("heroicon-o-clock")
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.created_at")
                                ->label("Dibuat Pada")
                                ->dateTime("d F Y H:i"),

                            Infolists\Components\TextEntry::make("created_at")
                                ->label("Item Ditambahkan")
                                ->dateTime("d F Y H:i"),
                        ]),
                    ]),
            ]);
    }
}
