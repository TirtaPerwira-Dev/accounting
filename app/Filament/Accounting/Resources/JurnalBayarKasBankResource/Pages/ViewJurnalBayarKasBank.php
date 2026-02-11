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
        // Load relationships through the header relation
        $this->record->load([
            'jurnalBayarKasBank.rekening.kelompok',
            'jurnalBayarKasBank.nomorBantu',
            'jurnalBayarKasBank.kodeProyek',
            'jurnalBayarKasBank.createdBy',
            'jurnalBayarKasBank.confirmedBy',
            'jurnalBayarKasBank.postedBy',
            'jurnalBayarKasBank.journal',
            'jurnalBayarKasBank.details.rekening.kelompok',
            'jurnalBayarKasBank.details.nomorBantu',
            'jurnalBayarKasBank.details.kodeProyek',
        ]);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->visible(fn($record) => !($record->jurnalBayarKasBank->is_confirmed ?? $record->is_confirmed)),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalBayarKasBank->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->body('Jurnal sudah dikonfirmasi dan siap untuk diposting ke buku besar.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Jurnal')
                ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                ->visible(fn($record) => !($record->jurnalBayarKasBank->is_confirmed ?? $record->is_confirmed) && auth()->user()->can('confirm_jurnal::bayar::kas::bank')),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->action(function ($record) {
                    $record->jurnalBayarKasBank->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->body('Jurnal kembali ke status draft dan dapat diedit kembali.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Batalkan Konfirmasi')
                ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                ->visible(fn($record) => ($record->jurnalBayarKasBank->is_confirmed ?? $record->is_confirmed) && !($record->jurnalBayarKasBank->is_posted ?? $record->is_posted) && auth()->user()->can('unconfirm_jurnal::bayar::kas::bank')),

            Actions\Action::make('exportPdf')
                ->label('PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function ($record) {
                    $header = $record->jurnalBayarKasBank;
                    $header->load(['rekening.kelompok', 'nomorBantu', 'kodeProyek', 'details.rekening.kelompok', 'details.nomorBantu', 'createdBy']);

                    $items = [];
                    // Debit Items (Details)
                    foreach ($header->details as $detail) {
                        $code = '-';
                        if ($detail->rekening) {
                            $code = ($detail->rekening->kelompok->no_kel ?? '') . 
                                    ($detail->rekening->no_rek ?? '') . 
                                    ($detail->nomorBantu->no_bantu ?? '');
                        }
                        $items[] = [
                            'code' => $code,
                            'name' => $detail->rekening->nama_rek ?? '-',
                            'description' => $detail->keterangan ?? $header->keterangan,
                            'debit' => $detail->debit > 0 ? $detail->debit : $detail->jumlah,
                            'credit' => $detail->credit,
                        ];
                    }

                    // Credit Item (Bank)
                    $bankCode = '-';
                    if ($header->rekening) {
                        $bankCode = ($header->rekening->kelompok->no_kel ?? '') . 
                                    ($header->rekening->no_rek ?? '') . 
                                    ($header->nomorBantu->no_bantu ?? '');
                    }
                    $items[] = [
                        'code' => $bankCode,
                        'name' => $header->rekening->nama_rek ?? '-',
                        'description' => $header->keterangan,
                        'debit' => 0,
                        'credit' => $header->rp,
                    ];

                    $voucher = [
                        'title' => 'BUKTI PENGELUARAN KAS / BANK',
                        'number' => $header->no_voucher ?? $header->bukti,
                        'date' => $header->tanggal,
                        'reference' => $header->no_reff,
                        'description' => $header->keterangan,
                        'payee' => $header->dibayar_kepada ?? '-',
                        'created_by' => $header->createdBy?->name,
                        'items' => $items,
                    ];

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.voucher', [
                        'voucher' => $voucher,
                        'company' => \App\Models\Company::first(),
                    ])->setPaper('a4', 'portrait');

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->no_voucher ?? $header->id);

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        'voucher-bayar-kas-bank-' . $safeFilename . '.pdf'
                    );
                }),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalBayarKasBank);
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
                ->visible(fn($record) => ($record->jurnalBayarKasBank->is_confirmed ?? $record->is_confirmed) && !($record->jurnalBayarKasBank->is_posted ?? $record->is_posted)),

            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(fn($record) => !($record->jurnalBayarKasBank->is_confirmed ?? $record->is_confirmed)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Section 1: Informasi Transaksi
                Components\Section::make('Informasi Transaksi')
                    ->schema([
                        // Baris 1: No Voucher dan Tanggal
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('jurnalBayarKasBank.no_voucher')
                                    ->label('No. Voucher')
                                    ->icon('heroicon-m-document-text')
                                    ->copyable()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->columnSpan(1),

                                Components\TextEntry::make('jurnalBayarKasBank.tanggal_check')
                                    ->label('Tanggal Check')
                                    ->icon('heroicon-m-calendar')
                                    ->date('d/m/Y')
                                    ->color('success')
                                    ->columnSpan(1),

                                Components\TextEntry::make('jurnalBayarKasBank.no_reff')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('gray')
                                    ->columnSpan(1),
                            ]),

                        // Baris 2: Bank Info
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('jurnalBayarKasBank.nama_bank_display')
                                    ->label('Nama Bank (Sumber Dana)')
                                    ->icon('heroicon-m-building-library')
                                    ->weight(FontWeight::SemiBold)
                                    ->color('info')
                                    ->columnSpan(1),

                                Components\TextEntry::make('jurnalBayarKasBank.no_cek')
                                    ->label('No. Cek/Giro')
                                    ->icon('heroicon-m-document-check')
                                    ->placeholder('-')
                                    ->copyable()
                                    ->columnSpan(1),
                            ]),

                        // Baris 3: Additional Info
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('jurnalBayarKasBank.dibayar_kepada')
                                    ->label('Boleh Dibayar Kepada')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-')
                                    ->columnSpan(1),

                                Components\TextEntry::make('jurnalBayarKasBank.beban_bagian')
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
                        Components\RepeatableEntry::make('jurnalBayarKasBank.details')
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

                                        Components\TextEntry::make('jumlah')
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
                                            ->label('No.')
                                            ->badge()
                                            ->color('gray'),
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
                                            ->state(fn($record) => $record->jurnalBayarKasBank->details->count() . ' Item')
                                            ->icon('heroicon-m-list-bullet')
                                            ->badge()
                                            ->color('info')
                                            ->size(Components\TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold),

                                        Components\TextEntry::make('grand_total')
                                            ->label('Total Pembayaran')
                                            ->state(fn($record) => 'Rp ' . number_format($record->jurnalBayarKasBank->rp, 0, ',', '.'))
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

                // Section 3: Status & Approval (Simplified)
                Components\Section::make('Status Transaksi')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('jurnalBayarKasBank.is_confirmed')
                                    ->label('Status Konfirmasi')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state ? '✓ Dikonfirmasi' : '⏳ Pending')
                                    ->color(fn($state) => $state ? 'success' : 'warning'),

                                Components\TextEntry::make('jurnalBayarKasBank.is_posted')
                                    ->label('Status Posting')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state ? '✓ Diposting' : '⏳ Belum')
                                    ->color(fn($state) => $state ? 'success' : 'gray'),

                                Components\TextEntry::make('jurnalBayarKasBank.confirmed_at')
                                    ->label('Dikonfirmasi')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-clock'),

                                Components\TextEntry::make('jurnalBayarKasBank.posted_at')
                                    ->label('Diposting')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-clock'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('jurnalBayarKasBank.createdBy.name')
                                    ->label('Dibuat Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user')
                                    ->badge()
                                    ->color('gray'),

                                Components\TextEntry::make('jurnalBayarKasBank.confirmedBy.name')
                                    ->label('Dikonfirmasi Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user')
                                    ->badge()
                                    ->color('success'),

                                Components\TextEntry::make('jurnalBayarKasBank.postedBy.name')
                                    ->label('Diposting Oleh')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-user')
                                    ->badge()
                                    ->color('info'),
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
                                Components\TextEntry::make('jurnalBayarKasBank.journal.no_bukti')
                                    ->label('No. Bukti Journal')
                                    ->icon('heroicon-m-document-duplicate')
                                    ->copyable()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                Components\TextEntry::make('jurnalBayarKasBank.journal_id')
                                    ->label('Journal ID')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->icon('heroicon-o-link')
                    ->compact()
                    ->visible(fn($record) => ($record->jurnalBayarKasBank->is_posted ?? $record->is_posted) && ($record->jurnalBayarKasBank->journal_id ?? $record->journal_id))
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
