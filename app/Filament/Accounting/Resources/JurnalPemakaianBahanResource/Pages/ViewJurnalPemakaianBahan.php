<?php

namespace App\Filament\Accounting\Resources\JurnalPemakaianBahanResource\Pages;

use App\Filament\Accounting\Resources\JurnalPemakaianBahanResource;
use App\Services\JournalPostingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewJurnalPemakaianBahan extends ViewRecord
{
    protected static string $resource = JurnalPemakaianBahanResource::class;

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
                ->visible(fn($record) => $record->jurnalPemakaianBahan && auth()->user()->can('postToLedger', $record->jurnalPemakaianBahan))
                ->action(function ($record) {
                    $header = $record->jurnalPemakaianBahan;
                    $header->load(['details.rekeningDebit.kelompok', 'details.rekeningKredit.kelompok', 'details.nomorBantuDebit', 'details.nomorBantuKredit', 'kodeProyek']);

                    $pdf = Pdf::loadView('reports.jurnal-pemakaian-bahan-detail', [
                        'jurnal' => $header,
                        'generatedAt' => now()->format('d M Y H:i'),
                    ]);

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->no_reff ?? $header->id);

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        'jurnal-pemakaian-bahan-' . $safeFilename . '.pdf'
                    );
                }),

            Actions\EditAction::make()->visible(fn($record) => $record->jurnalPemakaianBahan && !$record->jurnalPemakaianBahan->is_posted && auth()->user()->can('postToLedger', $record->jurnalPemakaianBahan)),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalPemakaianBahan);
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
                ->visible(fn($record) => !$record->jurnalPemakaianBahan?->is_posted && $record->jurnalPemakaianBahan && auth()->user()->can('postToLedger', $record->jurnalPemakaianBahan)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $parentJurnal = $this->record->jurnalPemakaianBahan;

        return $infolist
            ->schema([
                Infolists\Components\Section::make("Informasi Jurnal")
                    ->icon("heroicon-o-document-text")
                    ->description('Informasi utama jurnal pemakaian bahan.')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.no_reff")
                                ->label("No. Referensi")
                                ->copyable()
                                ->badge()
                                ->color("primary")
                                ->icon("heroicon-m-hashtag"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.bukti")
                                ->label("No. Bukti")
                                ->copyable()
                                ->badge()
                                ->color("info")
                                ->icon("heroicon-m-document"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.tanggal")
                                ->label("Tanggal")
                                ->date("d F Y")
                                ->icon("heroicon-m-calendar-days"),
                        ]),

                        Infolists\Components\TextEntry::make("jurnalPemakaianBahan.keterangan")
                            ->label("Keterangan")
                            ->columnSpanFull()
                            ->placeholder("-"),
                    ]),

                Infolists\Components\Section::make("Detail Pemakaian Bahan")
                    ->icon("heroicon-o-table-cells")
                    ->description(function () use ($parentJurnal) {
                        if (!$parentJurnal) return "Tidak ada detail";
                        $parentJurnal->loadMissing("details");
                        return "Total item: " . $parentJurnal->details->count();
                    })
                    ->schema([
                        Infolists\Components\RepeatableEntry::make("jurnalPemakaianBahan.details")
                            ->label(false)
                            ->grid(1)
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Grid::make(4)->schema([
                                            Infolists\Components\TextEntry::make("bukti")
                                                ->label("No. Bukti Item")
                                                ->placeholder("-"),

                                            Infolists\Components\TextEntry::make("kodeProyek")
                                                ->label("Kode Proyek")
                                                ->placeholder("-")
                                                ->state(fn($record) => $record->kodeProyek ?
                                                    $record->kodeProyek->kode . " - " . $record->kodeProyek->name : "-"),

                                            Infolists\Components\TextEntry::make("posisi")
                                                ->label("D/K")
                                                ->state(fn($record) => $record->rekening_debit_id ? 'Debit' : ($record->rekening_kredit_id ? 'Kredit' : '-'))
                                                ->badge()
                                                ->color(fn($record) => $record->rekening_debit_id ? 'info' : 'success'),

                                            Infolists\Components\TextEntry::make("jumlah")
                                                ->label("Jumlah")
                                                ->money("IDR")
                                                ->size("xl")
                                                ->weight("bold")
                                                ->alignEnd()
                                                ->color(fn($record) => $record->rekening_debit_id ? 'info' : 'success'),
                                        ]),

                                        Infolists\Components\Grid::make(2)->schema([
                                            Infolists\Components\TextEntry::make("rekeningDebit")
                                                ->label("Rekening Debit")
                                                ->state(fn($record) => $record->rekeningDebit ?
                                                    ($record->rekeningDebit->kelompok->no_kel ?? '') . "-" .
                                                    $record->rekeningDebit->no_rek . " " .
                                                    $record->rekeningDebit->nama_rek : "-")
                                                ->icon("heroicon-m-arrow-up")
                                                ->iconColor("info"),

                                            Infolists\Components\TextEntry::make("nomorBantuDebit")
                                                ->label("Nomor Bantu Debit")
                                                ->placeholder("-")
                                                ->state(fn($record) => $record->nomorBantuDebit ?
                                                    $record->nomorBantuDebit->no_bantu . " - " .
                                                    $record->nomorBantuDebit->nm_bantu : "-"),

                                            Infolists\Components\TextEntry::make("rekeningKredit")
                                                ->label("Rekening Kredit")
                                                ->state(fn($record) => $record->rekeningKredit ?
                                                    ($record->rekeningKredit->kelompok->no_kel ?? '') . "-" .
                                                    $record->rekeningKredit->no_rek . " " .
                                                    $record->rekeningKredit->nama_rek : "-")
                                                ->icon("heroicon-m-arrow-down")
                                                ->iconColor("success"),

                                            Infolists\Components\TextEntry::make("nomorBantuKredit")
                                                ->label("Nomor Bantu Kredit")
                                                ->placeholder("-")
                                                ->state(fn($record) => $record->nomorBantuKredit ?
                                                    $record->nomorBantuKredit->no_bantu . " - " .
                                                    $record->nomorBantuKredit->nm_bantu : "-"),
                                        ]),

                                        Infolists\Components\TextEntry::make("keterangan")
                                            ->label("Keterangan")
                                            ->placeholder("-")
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->icon("heroicon-o-wrench-screwdriver")
                                    ->iconColor("warning"),
                            ])
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make("Ringkasan Total")
                    ->icon("heroicon-o-calculator")
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make("totalDebit")
                                ->label("Total Debit")
                                ->state(function () use ($parentJurnal) {
                                    if (!$parentJurnal) return 0;
                                    $parentJurnal->loadMissing("details");
                                    return $parentJurnal->details->whereNotNull('rekening_debit_id')->sum("jumlah");
                                })
                                ->money("IDR")
                                ->color("info")
                                ->size("xl")
                                ->weight("bold"),

                            Infolists\Components\TextEntry::make("totalKredit")
                                ->label("Total Kredit")
                                ->state(function () use ($parentJurnal) {
                                    if (!$parentJurnal) return 0;
                                    $parentJurnal->loadMissing("details");
                                    return $parentJurnal->details->whereNotNull('rekening_kredit_id')->sum("jumlah");
                                })
                                ->money("IDR")
                                ->color("success")
                                ->size("xl")
                                ->weight("bold"),
                        ]),

                        Infolists\Components\TextEntry::make("balance")
                            ->label("Status Balance")
                            ->state(function () use ($parentJurnal) {
                                if (!$parentJurnal) return "N/A";
                                $parentJurnal->loadMissing("details");
                                $debit = $parentJurnal->details->whereNotNull('rekening_debit_id')->sum("jumlah");
                                $kredit = $parentJurnal->details->whereNotNull('rekening_kredit_id')->sum("jumlah");
                                return $debit == $kredit ? "✅ Balance (Debit = Kredit)" : "⚠️ Tidak Balance (Selisih: Rp " . number_format(abs($debit - $kredit), 0, ',', '.') . ")";
                            })
                            ->badge()
                            ->color(function () use ($parentJurnal) {
                                if (!$parentJurnal) return "gray";
                                $parentJurnal->loadMissing("details");
                                $debit = $parentJurnal->details->whereNotNull('rekening_debit_id')->sum("jumlah");
                                $kredit = $parentJurnal->details->whereNotNull('rekening_kredit_id')->sum("jumlah");
                                return $debit == $kredit ? "success" : "danger";
                            }),
                    ]),

                Infolists\Components\Section::make("Status & Audit")
                    ->icon("heroicon-o-shield-check")
                    ->description('Riwayat input, posting, perubahan, dan penghapusan data jurnal.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.createdBy.name")
                                ->label("Di Input Oleh")
                                ->placeholder("-")
                                ->icon("heroicon-m-user"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.created_at")
                                ->label("Di Input Pada")
                                ->dateTime("d/m/Y H:i")
                                ->icon("heroicon-m-clock"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.posted_at")
                                ->label("Di Posting Tanggal")
                                ->dateTime("d/m/Y H:i")
                                ->placeholder("-")
                                ->icon("heroicon-m-arrow-up-tray"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.postedBy.name")
                                ->label("Di Posting Oleh")
                                ->placeholder("-")
                                ->icon("heroicon-m-user-plus"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.updated_at")
                                ->label("Di Edit Pada")
                                ->dateTime("d/m/Y H:i")
                                ->placeholder("-")
                                ->icon("heroicon-m-pencil-square"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.edit_by_display")
                                ->label("Di Edit Oleh")
                                ->state('-')
                                ->placeholder("-")
                                ->icon("heroicon-m-user-circle"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.deleted_at")
                                ->label("Di Hapus Pada")
                                ->dateTime("d/m/Y H:i")
                                ->placeholder("-")
                                ->icon("heroicon-m-clock"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.deletedBy.name")
                                ->label("Di Hapus Oleh")
                                ->placeholder("-")
                                ->icon("heroicon-m-user-minus"),
                        ]),
                    ]),
            ]);
    }
}
