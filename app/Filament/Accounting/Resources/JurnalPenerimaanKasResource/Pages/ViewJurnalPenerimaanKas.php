<?php

namespace App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Accounting\Resources\JurnalPenerimaanKasResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewJurnalPenerimaanKas extends ViewRecord
{
    protected static string $resource = JurnalPenerimaanKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_confirmed),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalPenerimaanKas->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_confirmed && auth()->user()->can('confirm', $record->jurnalPenerimaanKas)),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->action(function ($record) {
                    $record->jurnalPenerimaanKas->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => $record->jurnalPenerimaanKas->is_confirmed && !$record->jurnalPenerimaanKas->is_posted && auth()->user()->can('unconfirm', $record->jurnalPenerimaanKas)),

            Actions\Action::make('exportPdf')
                ->label('PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function ($record) {
                    $parent = $record->jurnalPenerimaanKas;
                    $parent->load([
                        'kasBank.rekening.kelompok',
                        'details.rekening.kelompok',
                        'details.nomorBantu',
                        'details.kodeProyek',
                        'createdBy',
                        'confirmedBy',
                        'postedBy'
                    ]);

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.jurnal-penerimaan-kas', [
                        'record' => $parent,
                    ]);

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $parent->nomor_bukti ?? $parent->id);

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        'jurnal-penerimaan-kas-' . $safeFilename . '.pdf'
                    );
                }),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalPenerimaanKas);
                        Notification::make()
                            ->title('Jurnal berhasil diposting ke Buku Besar')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal posting')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn($record) => $record->jurnalPenerimaanKas->is_confirmed && !$record->jurnalPenerimaanKas->is_posted),

            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_confirmed),
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
                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.no_reff")
                                ->label("Referensi")
                                ->copyable()
                                ->badge()
                                ->color("primary")
                                ->icon("heroicon-m-hashtag"),

                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.tanggal")
                                ->label("Tanggal")
                                ->date("d F Y")
                                ->icon("heroicon-m-calendar-days"),

                            Infolists\Components\TextEntry::make("kasBank")
                                ->label("Kas/Bank (Tujuan)")
                                ->state(fn($record) => $record->jurnalPenerimaanKas?->kasBank ? 
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
                                                ->placeholder("-"),

                                            Infolists\Components\TextEntry::make("kodeProyek")
                                                ->label("Proyek")
                                                ->placeholder("-")
                                                ->state(fn($record) => $record->kodeProyek ? 
                                                    $record->kodeProyek->kode . " - " . $record->kodeProyek->name : "-"),

                                            Infolists\Components\TextEntry::make("rekening")
                                                ->label("Rekening (Sumber)")
                                                ->state(fn($record) => $record->rekening ?
                                                    $record->rekening->kelompok->no_kel . "-" .
                                                    $record->rekening->no_rek . " " .
                                                    $record->rekening->nama_rek : "-")
                                                ->columnSpan(2),

                                            Infolists\Components\TextEntry::make("nomorBantu")
                                                ->label("Nomor Bantu")
                                                ->placeholder("-")
                                                ->state(fn($record) => $record->nomorBantu ?
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
                                                ->placeholder("-")
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
