<?php

namespace App\Filament\Accounting\Resources\JurnalPemakaianBahanResource\Pages;

use App\Filament\Accounting\Resources\JurnalPemakaianBahanResource;
use App\Services\JournalPostingService;
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

            Actions\EditAction::make()->visible(fn($record) => !$record->jurnalPemakaianBahan?->is_confirmed),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalPemakaianBahan->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => !$record->jurnalPemakaianBahan?->is_confirmed),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(function ($record) {
                    $record->jurnalPemakaianBahan->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => $record->jurnalPemakaianBahan?->is_confirmed && !$record->jurnalPemakaianBahan?->is_posted),

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
                ->visible(fn($record) => $record->jurnalPemakaianBahan?->is_confirmed && !$record->jurnalPemakaianBahan?->is_posted),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $parentJurnal = $this->record->jurnalPemakaianBahan;
        
        return $infolist
            ->schema([
                Infolists\Components\Section::make("Informasi Jurnal")
                    ->icon("heroicon-o-document-text")
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
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

                            Infolists\Components\TextEntry::make("status")
                                ->label("Status")
                                ->state(fn($record) => $record->jurnalPemakaianBahan?->is_confirmed ? 'Dikonfirmasi' : 'Pending')
                                ->badge()
                                ->color(fn($record) => $record->jurnalPemakaianBahan?->is_confirmed ? 'success' : 'warning')
                                ->icon(fn($record) => $record->jurnalPemakaianBahan?->is_confirmed ? 'heroicon-m-check-circle' : 'heroicon-m-clock'),
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

                Infolists\Components\Section::make("Informasi Sistem")
                    ->icon("heroicon-o-clock")
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.createdBy.name")
                                ->label("Dibuat Oleh")
                                ->placeholder("-")
                                ->icon("heroicon-m-user"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.created_at")
                                ->label("Dibuat Pada")
                                ->dateTime("d F Y H:i")
                                ->icon("heroicon-m-clock"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.confirmedBy.name")
                                ->label("Dikonfirmasi Oleh")
                                ->placeholder("-")
                                ->icon("heroicon-m-check-badge"),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.confirmed_at")
                                ->label("Dikonfirmasi Pada")
                                ->dateTime("d F Y H:i")
                                ->placeholder("-")
                                ->icon("heroicon-m-clock"),

                            Infolists\Components\TextEntry::make("isPosted")
                                ->label("Status Posting")
                                ->state(fn($record) => $record->jurnalPemakaianBahan?->is_posted ? 'Sudah Diposting' : 'Belum Diposting')
                                ->badge()
                                ->color(fn($record) => $record->jurnalPemakaianBahan?->is_posted ? 'success' : 'gray')
                                ->icon(fn($record) => $record->jurnalPemakaianBahan?->is_posted ? 'heroicon-m-check-badge' : 'heroicon-m-clock'),

                            Infolists\Components\TextEntry::make("jurnalPemakaianBahan.posted_at")
                                ->label("Diposting Pada")
                                ->dateTime("d F Y H:i")
                                ->placeholder("-")
                                ->icon("heroicon-m-arrow-up-tray"),
                        ]),
                    ]),
            ]);
    }
}
