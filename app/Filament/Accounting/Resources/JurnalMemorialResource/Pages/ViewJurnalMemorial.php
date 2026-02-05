<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewJurnalMemorial extends ViewRecord
{
    protected static string $resource = JurnalMemorialResource::class;

    /**
     * Override resolveRecord untuk mendapatkan detail pertama dari parent JurnalMemorial
     * karena resource model adalah JurnalMemorialDetail tapi URL menggunakan parent ID
     */
    public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        // Key adalah ID dari JurnalMemorial (parent)
        // Kita perlu ambil detail pertama dari parent tersebut
        $detail = \App\Models\JurnalMemorialDetail::whereHas('jurnalMemorial', function($query) use ($key) {
            $query->where('id', $key);
        })->with(['jurnalMemorial', 'rekening.kelompok', 'nomorBantu', 'kodeProyek'])->first();
        
        if (!$detail) {
            abort(404);
        }
        
        return $detail;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->visible(fn($record) => !$record->jurnalMemorial->is_confirmed),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalMemorial->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => !$record->jurnalMemorial->is_confirmed && auth()->user()->can('confirm', $record->jurnalMemorial)),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->action(function ($record) {
                    $record->jurnalMemorial->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => $record->jurnalMemorial->is_confirmed && !$record->jurnalMemorial->is_posted && auth()->user()->can('unconfirm', $record->jurnalMemorial)),

            Actions\Action::make('exportPdf')
                ->label('PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function ($record) {
                    $record->load(['rekening.kelompok', 'nomorBantu', 'kodeProyek', 'details.rekening.kelompok', 'details.nomorBantu']);

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-memorial-single', [
                        'jurnal' => $record,
                        'generatedAt' => now()->format('d M Y H:i'),
                    ]);

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->bukti ?? $record->id);

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        'jurnal-memorial-' . $safeFilename . '.pdf'
                    );
                }),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalMemorial);
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
                ->visible(fn($record) => $record->jurnalMemorial->is_confirmed && !$record->jurnalMemorial->is_posted),

            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(fn($record) => !$record->jurnalMemorial->is_confirmed),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===================== INFORMASI JURNAL =====================
                Components\Section::make('Informasi Jurnal')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('jurnalMemorial.no_reff')
                                ->label('No. Referensi')
                                ->copyable()
                                ->icon('heroicon-m-hashtag'),

                            Components\TextEntry::make('jurnalMemorial.bukti')
                                ->label('No. Bukti')
                                ->weight('bold')
                                ->copyable()
                                ->icon('heroicon-m-document-magnifying-glass'),

                            Components\TextEntry::make('jurnalMemorial.tanggal')
                                ->label('Tanggal')
                                ->date('d F Y')
                                ->icon('heroicon-m-calendar-days'),
                        ]),

                        Components\TextEntry::make('jurnalMemorial.keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                // ===================== DETAIL TRANSAKSI =====================
                Components\Section::make('Detail Transaksi')
                    ->icon('heroicon-o-table-cells')
                    ->description(function ($record) {
                        $parentJurnal = $record->jurnalMemorial;
                        $parentJurnal->loadMissing('details');
                        return 'Total baris: ' . $parentJurnal->details->count();
                    })
                    ->schema([
                        Components\RepeatableEntry::make('jurnalMemorial.details')
                            ->label(false)
                            ->grid(1)
                            ->schema([
                                Components\Section::make()
                                    ->schema([
                                        Components\Grid::make(6)->schema([

                                            // KODE PROYEK
                                            Components\TextEntry::make('kodeProyek.name')
                                                ->label('Proyek')
                                                ->default('-')
                                                ->formatStateUsing(fn($record) => $record->kodeProyek ? 
                                                    $record->kodeProyek->kode . ' - ' . $record->kodeProyek->name : '-')
                                                ->columnSpan(2),

                                            // REKENING
                                            Components\TextEntry::make('rekening.nama_rek')
                                                ->label('Rekening')
                                                ->formatStateUsing(fn($record) => $record->rekening ?
                                                    $record->rekening->kelompok->no_kel . '-' .
                                                    $record->rekening->no_rek . ' - ' .
                                                    $record->rekening->nama_rek : '-')
                                                ->columnSpan(3),

                                            // NOMOR BANTU
                                            Components\TextEntry::make('nomorBantu.nm_bantu')
                                                ->label('Nomor Bantu')
                                                ->default('-')
                                                ->formatStateUsing(fn($record) => $record->nomorBantu ?
                                                    $record->nomorBantu->no_bantu . ' - ' .
                                                    $record->nomorBantu->nm_bantu : '-')
                                                ->columnSpan(2),

                                            // POSISI D/K (BADGE)
                                            Components\TextEntry::make('posisi')
                                                ->label('Posisi')
                                                ->badge()
                                                ->size('lg')
                                                ->color(fn($state) => $state === 'D' ? 'danger' : 'success')
                                                ->formatStateUsing(fn($state) => $state === 'D' ? 'DEBIT' : 'KREDIT')
                                                ->columnSpan(1),

                                            // JUMLAH
                                            Components\TextEntry::make('jumlah')
                                                ->label('')
                                                ->money('IDR')
                                                ->size('xl')
                                                ->weight('bold')
                                                ->alignEnd()
                                                ->columnSpan(2)
                                                ->color(fn($record) => $record->posisi === 'D' ? 'danger' : 'success'),
                                        ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->description(fn($record) => $record->posisi === 'D'
                                        ? 'Transaksi Debit — Mengurangi saldo rekening'
                                        : 'Transaksi Kredit — Menambah saldo rekening')
                                    ->icon(fn($record) => $record->posisi === 'D'
                                        ? 'heroicon-o-arrow-up-right'
                                        : 'heroicon-o-arrow-down-right')
                                    ->iconColor(fn($record) => $record->posisi === 'D' ? 'danger' : 'success'),
                            ])
                            ->columnSpanFull(),
                    ]),

                // ===================== RINGKASAN TOTAL =====================
                Components\Section::make('Ringkasan')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('total_debit')
                                ->label('Total Debit')
                                ->state(function ($record) {
                                    $parentJurnal = $record->jurnalMemorial;
                                    $parentJurnal->loadMissing('details');
                                    return $parentJurnal->details->where('posisi', 'D')->sum('jumlah');
                                })
                                ->money('IDR')
                                ->color('danger')
                                ->size('xl')
                                ->weight('bold'),

                            Components\TextEntry::make('total_kredit')
                                ->label('Total Kredit')
                                ->state(function ($record) {
                                    $parentJurnal = $record->jurnalMemorial;
                                    $parentJurnal->loadMissing('details');
                                    return $parentJurnal->details->where('posisi', 'K')->sum('jumlah');
                                })
                                ->money('IDR')
                                ->color('success')
                                ->size('xl')
                                ->weight('bold'),

                            Components\TextEntry::make('balance_status')
                                ->label('Status Jurnal')
                                ->state(function ($record) {
                                    $parentJurnal = $record->jurnalMemorial;
                                    $parentJurnal->loadMissing('details');
                                    $debit = $parentJurnal->details->where('posisi', 'D')->sum('jumlah');
                                    $kredit = $parentJurnal->details->where('posisi', 'K')->sum('jumlah');
                                    return $debit === $kredit && $debit > 0 ? 'JURNAL BALANCE' : 'TIDAK BALANCE';
                                })
                                ->badge()
                                ->color(fn($state) => str_contains($state, 'BALANCE') ? 'success' : 'danger')
                                ->size('xl'),
                        ]),
                    ]),

                // ===================== STATUS & AUDIT =====================
                Components\Section::make('Status & Audit')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Components\Grid::make(4)->schema([
                            Components\IconEntry::make('jurnalMemorial.is_confirmed')
                                ->label('Status Konfirmasi')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-badge')
                                ->falseIcon('heroicon-o-clock')
                                ->trueColor('success')
                                ->falseColor('warning'),

                            Components\TextEntry::make('jurnalMemorial.confirmed_at')
                                ->label('Dikonfirmasi Pada')
                                ->dateTime('d F Y H:i')
                                ->placeholder('Belum dikonfirmasi'),

                            Components\IconEntry::make('jurnalMemorial.is_posted')
                                ->label('Status Posting')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-badge')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('gray'),

                            Components\TextEntry::make('jurnalMemorial.created_at')
                                ->label('Dibuat Pada')
                                ->dateTime('d F Y H:i'),
                        ]),
                    ]),
            ]);
    }
}
