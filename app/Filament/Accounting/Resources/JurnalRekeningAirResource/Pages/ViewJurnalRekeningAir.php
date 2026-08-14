<?php

namespace App\Filament\Accounting\Resources\JurnalRekeningAirResource\Pages;

use App\Filament\Accounting\Resources\JurnalRekeningAirResource;
use App\Services\JournalPostingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewJurnalRekeningAir extends ViewRecord
{
    protected static string $resource = JurnalRekeningAirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_list')
                ->label('Kembali ke List')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => static::getResource()::getUrl('index')),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible(fn($record) => auth()->user()->can('postToLedger', $record->jurnalRekeningAir))
                ->action(function ($record) {
                    $header = $record->jurnalRekeningAir;
                    $header->load(['details.rekening.kelompok', 'details.nomorBantu', 'details.kodeProyek']);

                    $pdf = Pdf::loadView('reports.jurnal-rekening-air-single', [
                        'jurnal' => $header,
                        'generatedAt' => now()->format('d M Y H:i'),
                    ]);

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->no_reff ?? $header->id);

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        'jurnal-rekening-air-' . $safeFilename . '.pdf'
                    );
                }),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalRekeningAir);
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
                ->visible(fn($record) => !$record->jurnalRekeningAir->is_posted && auth()->user()->can('postToLedger', $record->jurnalRekeningAir)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                // ===================== INFORMASI JURNAL =====================
                Infolists\Components\Section::make('Informasi Jurnal')
                    ->icon('heroicon-o-document-text')
                    ->description('Informasi utama jurnal rekening air dan non-air.')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('jurnalRekeningAir.no_reff')
                                ->label('No. Referensi')
                                ->copyable()
                                ->icon('heroicon-m-hashtag'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.bukti')
                                ->label('No. Bukti')
                                ->weight('bold')
                                ->copyable()
                                ->icon('heroicon-m-document-magnifying-glass'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.tanggal')
                                ->label('Tanggal')
                                ->date('d F Y')
                                ->icon('heroicon-m-calendar-days'),
                        ]),

                        Infolists\Components\TextEntry::make('jurnalRekeningAir.keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                // ===================== DETAIL TRANSAKSI =====================
                Infolists\Components\Section::make('Detail Transaksi')
                    ->icon('heroicon-o-table-cells')
                    ->description(function ($record) {
                        $parentJurnal = $record->jurnalRekeningAir;
                        $parentJurnal->loadMissing('details');
                        return 'Total baris: ' . $parentJurnal->details->count();
                    })
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('jurnalRekeningAir.details')
                            ->label(false)
                            ->grid(1)
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Grid::make(6)->schema([

                                            // KODE PROYEK
                                            Infolists\Components\TextEntry::make('kodeProyek.name')
                                                ->label('Proyek')
                                                ->default('-')
                                                ->formatStateUsing(fn($record) => $record->kodeProyek ? 
                                                    $record->kodeProyek->kode . ' - ' . $record->kodeProyek->name : '-')
                                                ->columnSpan(2),

                                            // REKENING
                                            Infolists\Components\TextEntry::make('rekening.nama_rek')
                                                ->label('Rekening')
                                                ->formatStateUsing(fn($record) => $record->rekening ?
                                                    $record->rekening->kelompok->no_kel . '-' .
                                                    $record->rekening->no_rek . ' - ' .
                                                    $record->rekening->nama_rek : '-')
                                                ->columnSpan(3),

                                            // NOMOR BANTU
                                            Infolists\Components\TextEntry::make('nomorBantu.nm_bantu')
                                                ->label('Nomor Bantu')
                                                ->default('-')
                                                ->formatStateUsing(fn($record) => $record->nomorBantu ?
                                                    $record->nomorBantu->no_bantu . ' - ' .
                                                    $record->nomorBantu->nm_bantu : '-')
                                                ->columnSpan(2),

                                            // POSISI D/K (BADGE)
                                            Infolists\Components\TextEntry::make('position')
                                                ->label('Posisi')
                                                ->badge()
                                                ->size('lg')
                                                ->color(fn($state) => $state === 'debit' ? 'danger' : 'success')
                                                ->formatStateUsing(fn($state) => $state === 'debit' ? 'DEBIT' : 'KREDIT')
                                                ->columnSpan(1),

                                            // JUMLAH
                                            Infolists\Components\TextEntry::make('jumlah')
                                                ->label('')
                                                ->money('IDR')
                                                ->size('xl')
                                                ->weight('bold')
                                                ->alignEnd()
                                                ->columnSpan(2)
                                                ->color(fn($record) => $record->position === 'debit' ? 'danger' : 'success'),
                                        ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->description(fn($record) => $record->position === 'debit'
                                        ? 'Transaksi Debit — Mengurangi saldo rekening'
                                        : 'Transaksi Kredit — Menambah saldo rekening')
                                    ->icon(fn($record) => $record->position === 'debit'
                                        ? 'heroicon-o-arrow-up-right'
                                        : 'heroicon-o-arrow-down-right')
                                    ->iconColor(fn($record) => $record->position === 'debit' ? 'danger' : 'success'),
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
                                    $parentJurnal = $record->jurnalRekeningAir;
                                    $parentJurnal->loadMissing('details');
                                    return $parentJurnal->details->where('position', 'debit')->sum('jumlah');
                                })
                                ->money('IDR')
                                ->color('danger')
                                ->size('xl')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('total_kredit')
                                ->label('Total Kredit')
                                ->state(function ($record) {
                                    $parentJurnal = $record->jurnalRekeningAir;
                                    $parentJurnal->loadMissing('details');
                                    return $parentJurnal->details->where('position', 'kredit')->sum('jumlah');
                                })
                                ->money('IDR')
                                ->color('success')
                                ->size('xl')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('balance_status')
                                ->label('Status Jurnal')
                                ->state(function ($record) {
                                    $parentJurnal = $record->jurnalRekeningAir;
                                    $parentJurnal->loadMissing('details');
                                    $debit = $parentJurnal->details->where('position', 'debit')->sum('jumlah');
                                    $kredit = $parentJurnal->details->where('position', 'kredit')->sum('jumlah');
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
                    ->description('Riwayat input, posting, perubahan, dan penghapusan data jurnal.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('jurnalRekeningAir.createdBy.name')
                                ->label('Di Input Oleh')
                                ->icon('heroicon-m-user')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.created_at')
                                ->label('Di Input Pada')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-clock')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.posted_at')
                                ->label('Di Posting Tanggal')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-arrow-up-tray')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.postedBy.name')
                                ->label('Di Posting Oleh')
                                ->icon('heroicon-m-user-plus')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.updated_at')
                                ->label('Di Edit Pada')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-pencil-square')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.edit_by_display')
                                ->label('Di Edit Oleh')
                                ->state('-')
                                ->icon('heroicon-m-user-circle')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.deleted_at')
                                ->label('Di Hapus Pada')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-trash')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('jurnalRekeningAir.deletedBy.name')
                                ->label('Di Hapus Oleh')
                                ->icon('heroicon-m-user-minus')
                                ->placeholder('-'),
                        ]),
                    ]),
            ]);
    }
}
