<?php

namespace App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\Pages;

use App\Filament\Accounting\Resources\JurnalPenerimaanKasResource;
use App\Services\JournalPostingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible(fn($record) => Auth::check() && Gate::forUser(Auth::user())->allows('postToLedger', $record->jurnalPenerimaanKas))
                ->action(function ($record) {
                    $header = $record->jurnalPenerimaanKas;
                    $header->load([
                        'kasBank.rekening.kelompok',
                        'details.rekening.kelompok',
                        'details.nomorBantu',
                        'details.kodeProyek',
                        'createdBy',
                        'confirmedBy',
                        'postedBy',
                    ]);

                    $pdf = Pdf::loadView('pdf.jurnal-penerimaan-kas', [
                        'record' => $header,
                    ]);

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->nomor_bukti ?? $header->id);

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
                ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_posted && Auth::check() && Gate::forUser(Auth::user())->allows('postToLedger', $record->jurnalPenerimaanKas)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $parentJurnal = $this->record->jurnalPenerimaanKas;
        
        return $infolist
            ->schema([
                Infolists\Components\Section::make("Informasi Jurnal")
                    ->icon("heroicon-o-document-text")
                    ->description('Informasi utama dokumen jurnal.')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.no_reff")
                                ->label("No. Referensi")
                                ->copyable()
                                ->badge()
                                ->color("primary")
                                ->icon("heroicon-m-hashtag"),

                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.tanggal")
                                ->label("Tanggal")
                                ->date("d/m/Y")
                                ->badge()
                                ->color('info')
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
                    ])
                    ->compact(),

                Infolists\Components\Section::make("Detail Transaksi")
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

                Infolists\Components\Section::make("Ringkasan Transaksi")
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

                Infolists\Components\Section::make("Status & Audit")
                    ->icon("heroicon-o-shield-check")
                    ->description('Riwayat input, posting, perubahan, dan penghapusan data jurnal.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.createdBy.name")
                                ->label("Di Input Oleh")
                                ->icon('heroicon-m-user')
                                ->placeholder("-"),

                            Infolists\Components\TextEntry::make("jurnalPenerimaanKas.created_at")
                                ->label("Di Input Pada")
                                ->dateTime("d/m/Y H:i")
                                ->icon('heroicon-m-clock'),

                            Infolists\Components\TextEntry::make('jurnalPenerimaanKas.posted_at')
                                ->label('Di Posting Tanggal')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-arrow-up-tray')
                                ->placeholder('Belum diposting'),

                            Infolists\Components\TextEntry::make('jurnalPenerimaanKas.postedBy.name')
                                ->label('Di Posting Oleh')
                                ->icon('heroicon-m-user-plus')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalPenerimaanKas.updated_at')
                                ->label('Di Edit Pada')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-pencil-square')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalPenerimaanKas.edit_by_display')
                                ->label('Di Edit Oleh')
                                ->state('-')
                                ->icon('heroicon-m-user-circle')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalPenerimaanKas.deleted_at')
                                ->label('Di Hapus Pada')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-trash')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalPenerimaanKas.deletedBy.name')
                                ->label('Di Hapus Oleh')
                                ->icon('heroicon-m-user-minus')
                                ->placeholder('-'),
                        ]),
                    ]),
            ]);
    }
}
