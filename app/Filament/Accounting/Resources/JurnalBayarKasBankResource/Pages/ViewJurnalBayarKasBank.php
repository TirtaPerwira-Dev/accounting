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

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn($record) => !$record->is_confirmed),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => !$record->is_confirmed && auth()->user()->can('confirm', $record)),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(function ($record) {
                    $record->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn($record) => $record->is_confirmed && !$record->is_posted && auth()->user()->can('unconfirm', $record)),

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
                ->visible(fn($record) => $record->is_confirmed && !$record->is_posted),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
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
                // Section 1: Informasi Header
                Components\Section::make('Informasi Pembayaran')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('no_voucher')
                                    ->label('No. Voucher')
                                    ->icon('heroicon-m-document-text')
                                    ->copyable()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                Components\TextEntry::make('tanggal_check')
                                    ->label('Tanggal Check')
                                    ->icon('heroicon-m-calendar')
                                    ->date('d F Y')
                                    ->color('success'),

                                Components\TextEntry::make('no_reff')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('gray'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('nama_bank_display')
                                    ->label('Nama Bank')
                                    ->icon('heroicon-m-building-library')
                                    ->weight(FontWeight::SemiBold)
                                    ->color('info'),

                                Components\TextEntry::make('no_cek')
                                    ->label('No. Cek/Giro')
                                    ->icon('heroicon-m-document-check')
                                    ->placeholder('-')
                                    ->copyable(),

                                Components\TextEntry::make('beban_bagian')
                                    ->label('Beban Bagian')
                                    ->placeholder('-'),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('dibayar_kepada')
                                    ->label('Boleh Dibayar Kepada')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-')
                                    ->columnSpan(1),

                                Components\TextEntry::make('rp')
                                    ->label('Total Pembayaran')
                                    ->icon('heroicon-m-currency-dollar')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->size(Components\TextEntry\TextEntrySize::Large)
                                    ->color('warning')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->icon('heroicon-o-information-circle')
                    ->collapsible(),

                // Section 2: Detail Rekening
                Components\Section::make('Detail Rekening Bank')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('kelompok.nama_kel')
                                    ->label('Kelompok')
                                    ->badge()
                                    ->color('success')
                                    ->formatStateUsing(fn($record) => 
                                        ($record->kelompok?->no_kel ?? '') . ' - ' . ($record->kelompok?->nama_kel ?? '-')
                                    ),

                                Components\TextEntry::make('rekening.nama_rek')
                                    ->label('Rekening')
                                    ->formatStateUsing(fn($record) => 
                                        ($record->rekening?->no_rek ?? '') . ' - ' . ($record->rekening?->nama_rek ?? '-')
                                    ),

                                Components\TextEntry::make('nomorBantu.nm_bantu')
                                    ->label('Nomor Bantu')
                                    ->placeholder('Tidak ada')
                                    ->formatStateUsing(fn($record) => 
                                        $record->nomorBantu 
                                            ? ($record->nomorBantu->no_bantu . ' - ' . $record->nomorBantu->nm_bantu)
                                            : '-'
                                    ),
                            ]),

                        Components\Grid::make(1)
                            ->schema([
                                Components\TextEntry::make('kodeProyek.name')
                                    ->label('Kode Proyek')
                                    ->placeholder('Tidak ada')
                                    ->icon('heroicon-m-folder')
                                    ->formatStateUsing(fn($record) => 
                                        $record->kodeProyek 
                                            ? ($record->kodeProyek->kode . ' - ' . $record->kodeProyek->name)
                                            : '-'
                                    ),
                            ]),
                    ])
                    ->icon('heroicon-o-credit-card')
                    ->collapsible(),

                // Section 3: Detail Pembayaran (Items)
                Components\Section::make('Detail Item Pembayaran')
                    ->schema([
                        Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Components\Grid::make(4)
                                    ->schema([
                                        Components\TextEntry::make('rekening.nama_rek')
                                            ->label('Rekening')
                                            ->formatStateUsing(function ($record) {
                                                if (!$record->rekening) return '-';
                                                
                                                $noKel = $record->rekening->kelompok?->no_kel ?? '';
                                                $noRek = $record->rekening->no_rek ?? '';
                                                $namaRek = $record->rekening->nama_rek ?? '';
                                                
                                                return ($noKel ? $noKel . '-' : '') . $noRek . ' - ' . $namaRek;
                                            })
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpan(2),

                                        Components\TextEntry::make('nomorBantu.nm_bantu')
                                            ->label('No. Bantu')
                                            ->placeholder('-')
                                            ->formatStateUsing(function ($record) {
                                                if (!$record->nomorBantu) return '-';
                                                return $record->nomorBantu->no_bantu . ' - ' . $record->nomorBantu->nm_bantu;
                                            })
                                            ->columnSpan(1),

                                        Components\TextEntry::make('jumlah')
                                            ->label('Jumlah')
                                            ->money('IDR')
                                            ->weight(FontWeight::Bold)
                                            ->color('warning')
                                            ->columnSpan(1),
                                    ]),

                                Components\Grid::make(2)
                                    ->schema([
                                        Components\TextEntry::make('kodeProyek.name')
                                            ->label('Proyek')
                                            ->placeholder('-')
                                            ->formatStateUsing(function ($record) {
                                                if (!$record->kodeProyek) return '-';
                                                return $record->kodeProyek->kode . ' - ' . $record->kodeProyek->name;
                                            }),

                                        Components\TextEntry::make('keterangan')
                                            ->label('Keterangan')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->contained(true)
                            ->columnSpanFull(),

                        // Summary Total
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('details_count')
                                    ->label('Total Item')
                                    ->state(fn($record) => $record->details->count() . ' item')
                                    ->icon('heroicon-m-list-bullet'),

                                Components\TextEntry::make('details_total')
                                    ->label('Total Pembayaran Detail')
                                    ->state(fn($record) => 'Rp ' . number_format($record->details->sum('jumlah'), 0, ',', '.'))
                                    ->icon('heroicon-m-currency-dollar')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),
                            ]),
                    ])
                    ->icon('heroicon-o-list-bullet')
                    ->collapsible(),

                // Section 4: Status & Approval
                Components\Section::make('Status & Approval')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('is_confirmed')
                                    ->label('Status Konfirmasi')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state ? 'Dikonfirmasi' : 'Belum Dikonfirmasi')
                                    ->color(fn($state) => $state ? 'success' : 'warning'),

                                Components\TextEntry::make('is_posted')
                                    ->label('Status Posting')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state ? 'Sudah Diposting' : 'Belum Diposting')
                                    ->color(fn($state) => $state ? 'success' : 'gray'),

                                Components\TextEntry::make('confirmed_at')
                                    ->label('Waktu Konfirmasi')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-clock'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('createdBy.name')
                                    ->label('Dibuat Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user'),

                                Components\TextEntry::make('confirmedBy.name')
                                    ->label('Dikonfirmasi Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user-check'),

                                Components\TextEntry::make('postedBy.name')
                                    ->label('Diposting Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user-plus'),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label('Tanggal Dibuat')
                                    ->dateTime('d F Y, H:i:s')
                                    ->icon('heroicon-m-calendar-days'),

                                Components\TextEntry::make('posted_at')
                                    ->label('Tanggal Posting')
                                    ->dateTime('d F Y, H:i:s')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-calendar-days'),
                            ]),
                    ])
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->collapsed(),

                // Section 5: Journal Reference
                Components\Section::make('Referensi Journal')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('journal.no_bukti')
                                    ->label('No. Bukti Journal')
                                    ->placeholder('Belum diposting')
                                    ->icon('heroicon-m-document-duplicate')
                                    ->copyable(),

                                Components\TextEntry::make('journal_id')
                                    ->label('Journal ID')
                                    ->placeholder('Belum diposting')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->icon('heroicon-o-link')
                    ->visible(fn($record) => $record->is_posted && $record->journal_id)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
