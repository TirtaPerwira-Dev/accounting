<?php

namespace App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Support\Enums\FontWeight;

class ViewJurnalBayarKasBank extends ViewRecord
{
    protected static string $resource = JurnalBayarKasBankResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load grouped items berdasarkan no_voucher untuk ditampilkan di infolist
        $record = $this->getRecord();

        if ($record->no_voucher) {
            $groupedItems = \App\Models\JurnalBayarKasBank::query()
                ->with(['kelompok', 'rekening.kelompok', 'nomorBantu', 'kodeProyek'])
                ->where('no_voucher', $record->no_voucher)
                ->orderBy('item_sequence')
                ->get();

            $record->setRelation('loadedGroupedItems', $groupedItems);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn($record) => !$record->is_posted && !$record->is_confirmed && auth()->user()->can('postToLedger', $record)),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record);
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
                ->visible(fn($record) => !$record->is_posted && auth()->user()->can('postToLedger', $record)),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible(fn($record) => auth()->user()->can('postToLedger', $record))
                ->action(function ($record) {
                    $record->load(['rekening.kelompok', 'nomorBantu', 'kodeProyek', 'details.rekening.kelompok', 'details.nomorBantu']);

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-bayar-kas-bank-single', [
                        'jurnal' => $record,
                        'generatedAt' => now()->format('d M Y H:i'),
                    ]);

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->no_voucher ?? $record->id);

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        'jurnal-bayar-kas-bank-' . $safeFilename . '.pdf'
                    );
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Section 1: Informasi Transaksi
                Components\Section::make('Informasi Transaksi')
                    ->description('Informasi utama jurnal bayar kas/bank.')
                    ->schema([
                        // Baris 1: No Voucher dan Tanggal
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('no_voucher')
                                    ->label('No. Voucher')
                                    ->icon('heroicon-m-document-text')
                                    ->copyable()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->columnSpan(1),

                                Components\TextEntry::make('tanggal_check')
                                    ->label('Tanggal Check')
                                    ->icon('heroicon-m-calendar')
                                    ->date('d/m/Y')
                                    ->color('success')
                                    ->columnSpan(1),

                                Components\TextEntry::make('no_reff')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('primary')
                                    ->columnSpan(1),
                            ]),

                        // Baris 2: Bank Info
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('nama_bank_display')
                                    ->label('Nama Bank (Sumber Dana)')
                                    ->icon('heroicon-m-building-library')
                                    ->weight(FontWeight::SemiBold)
                                    ->color('info')
                                    ->columnSpan(1),

                                Components\TextEntry::make('no_cek')
                                    ->label('No. Cek/Giro')
                                    ->icon('heroicon-m-document-check')
                                    ->placeholder('-')
                                    ->copyable()
                                    ->columnSpan(1),
                            ]),

                        // Baris 3: Additional Info
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('dibayar_kepada')
                                    ->label('Boleh Dibayar Kepada')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-')
                                    ->columnSpan(1),

                                Components\TextEntry::make('beban_bagian')
                                    ->label('Beban Bagian')
                                    ->icon('heroicon-m-briefcase')
                                    ->placeholder('-')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->icon('heroicon-o-information-circle')
                    ->compact()
                    ->columns(1),

                // Section 2: Rincian Item Pembayaran
                Components\Section::make('Rincian Item Pembayaran')
                    ->description(fn($record) => 'Daftar item pembayaran dari voucher ini')
                    ->schema([
                        Components\RepeatableEntry::make('grouped_items')
                            ->label('')
                            ->schema([
                                // Baris 1: Rekening dan Jumlah
                                Components\Grid::make(3)
                                    ->schema([
                                        Components\TextEntry::make('rekening.nama_rek')
                                            ->label('Rekening Tujuan')
                                            ->formatStateUsing(function ($record) {
                                                if (!$record->rekening) return '-';

                                                $noKel = $record->rekening->kelompok?->no_kel ?? '';
                                                $noRek = $record->rekening->no_rek ?? '';
                                                $namaRek = $record->rekening->nama_rek ?? '';

                                                return ($noKel ? $noKel . '-' : '') . $noRek . ' - ' . $namaRek;
                                            })
                                            ->icon('heroicon-m-inbox-stack')
                                            ->weight(FontWeight::SemiBold)
                                            ->color('info')
                                            ->columnSpan(2),

                                        Components\TextEntry::make('rp')
                                            ->label('Jumlah')
                                            ->money('IDR')
                                            ->weight(FontWeight::Bold)
                                            ->size(Components\TextEntry\TextEntrySize::Large)
                                            ->color('warning')
                                            ->badge()
                                            ->columnSpan(1),
                                    ]),

                                // Baris 2: Detail tambahan
                                Components\Grid::make(3)
                                    ->schema([
                                        Components\TextEntry::make('nomorBantu.nm_bantu')
                                            ->label('Nomor Bantu')
                                            ->placeholder('-')
                                            ->icon('heroicon-m-hashtag')
                                            ->formatStateUsing(function ($record) {
                                                if (!$record->nomorBantu) return '-';
                                                return $record->nomorBantu->no_bantu . ' - ' . $record->nomorBantu->nm_bantu;
                                            }),

                                        Components\TextEntry::make('kodeProyek.name')
                                            ->label('Kode Proyek')
                                            ->placeholder('-')
                                            ->icon('heroicon-m-folder')
                                            ->formatStateUsing(function ($record) {
                                                if (!$record->kodeProyek) return '-';
                                                return $record->kodeProyek->kode . ' - ' . $record->kodeProyek->name;
                                            }),

                                        Components\TextEntry::make('item_sequence')
                                            ->label('Item Ke-')
                                            ->badge()
                                            ->color('gray')
                                            ->formatStateUsing(fn($state) => '#' . $state),
                                    ]),

                                // Baris 3: Keterangan
                                Components\TextEntry::make('keterangan')
                                    ->label('Keterangan')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-document-text')
                                    ->columnSpanFull(),
                            ])
                            ->contained(true)
                            ->columnSpanFull(),

                        // Summary di bawah (selalu terlihat)
                        Components\Section::make('')
                            ->schema([
                                Components\Grid::make(2)
                                    ->schema([
                                        Components\TextEntry::make('total_items')
                                            ->label('Total Item')
                                            ->state(fn($record) => $record->grouped_items->count() . ' Item')
                                            ->icon('heroicon-m-list-bullet')
                                            ->badge()
                                            ->color('info')
                                            ->size(Components\TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold),

                                        Components\TextEntry::make('grand_total')
                                            ->label('Total Pembayaran')
                                            ->state(fn($record) => 'Rp ' . number_format($record->grouped_items->sum('rp'), 0, ',', '.'))
                                            ->icon('heroicon-m-currency-dollar')
                                            ->badge()
                                            ->color('warning')
                                            ->size(Components\TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold),
                                    ]),
                            ])
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-list-bullet')
                    ->collapsible(),

                Components\Section::make('Status & Audit')
                    ->description('Riwayat input, posting, perubahan, dan penghapusan data jurnal.')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('createdBy.name')
                                    ->label('Di Input Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user'),

                                Components\TextEntry::make('created_at')
                                    ->label('Di Input Pada')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-clock'),

                                Components\TextEntry::make('posted_at')
                                    ->label('Di Posting Tanggal')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-arrow-up-tray'),

                                Components\TextEntry::make('postedBy.name')
                                    ->label('Di Posting Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user-plus'),

                                Components\TextEntry::make('updated_at')
                                    ->label('Di Edit Pada')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-pencil-square'),

                                Components\TextEntry::make('edit_by_display')
                                    ->label('Di Edit Oleh')
                                    ->state('-')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user-circle'),

                                Components\TextEntry::make('deleted_at')
                                    ->label('Di Hapus Pada')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-trash'),

                                Components\TextEntry::make('deletedBy.name')
                                    ->label('Di Hapus Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user-minus'),
                            ]),
                    ])
                    ->icon('heroicon-o-shield-check')
                    ->compact()
                    ->collapsible()
                    ->collapsed(),

                // Section 4: Journal Reference (Only if posted)
                Components\Section::make('Referensi Journal Buku Besar')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('journal.no_bukti')
                                    ->label('No. Bukti Journal')
                                    ->icon('heroicon-m-document-duplicate')
                                    ->copyable()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                Components\TextEntry::make('journal_id')
                                    ->label('Journal ID')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->icon('heroicon-o-link')
                    ->compact()
                    ->visible(fn($record) => $record->is_posted && $record->journal_id)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
