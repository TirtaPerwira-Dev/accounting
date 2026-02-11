<?php

namespace App\Filament\Accounting\Resources\JurnalPembelianResource\Pages;

use App\Filament\Accounting\Resources\JurnalPembelianResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewJurnalPembelian extends ViewRecord
{
    protected static string $resource = JurnalPembelianResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relationships through the header relation
        $this->record->load([
            'jurnalPembelian.kelompokKredit',
            'jurnalPembelian.rekeningKredit',
            'jurnalPembelian.nomorBantuDebit',
            'jurnalPembelian.kodeProyek',
            'jurnalPembelian.details.kelompokDebit',
            'jurnalPembelian.details.rekeningDebit',
            'jurnalPembelian.details.nomorBantuDebit',
            'jurnalPembelian.details.kodeProyek',
        ]);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->visible(fn($record) => !($record->jurnalPembelian->is_confirmed ?? $record->is_confirmed)),

            Actions\Action::make('confirm')
                ->label('✓ Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($record) {
                    $record->jurnalPembelian->confirm();
                    Notification::make()
                        ->title('Jurnal berhasil dikonfirmasi')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Jurnal Pembelian')
                ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Jurnal yang sudah dikonfirmasi tidak bisa diedit.')
                ->modalSubmitActionLabel('Ya, Konfirmasi')
                ->visible(fn($record) => !($record->jurnalPembelian->is_confirmed ?? $record->is_confirmed) && auth()->user()->can('confirm_jurnal::pembelian')),

            Actions\Action::make('unconfirm')
                ->label('↶ Batal Konfirmasi')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->action(function ($record) {
                    $record->jurnalPembelian->unconfirm();
                    Notification::make()
                        ->title('Konfirmasi jurnal dibatalkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Batal Konfirmasi Jurnal')
                ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                ->modalSubmitActionLabel('Ya, Batalkan')
                ->visible(fn($record) => ($record->jurnalPembelian->is_confirmed ?? $record->is_confirmed) && !($record->jurnalPembelian->is_posted ?? $record->is_posted) && auth()->user()->can('unconfirm_jurnal::pembelian')),

            Actions\Action::make('exportPdf')
                ->label('PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function ($record) {
                    return $this->generateJurnalPdf($record);
                }),

            Actions\Action::make('post_to_ledger')
                ->label('Post ke Buku Besar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record, JournalPostingService $service) {
                    try {
                        $service->post($record->jurnalPembelian);
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
                ->visible(fn($record) => ($record->jurnalPembelian->is_confirmed ?? $record->is_confirmed) && !($record->jurnalPembelian->is_posted ?? $record->is_posted)),

            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(fn($record) => !($record->jurnalPembelian->is_confirmed ?? $record->is_confirmed)),
        ];
    }

    protected function generateJurnalPdf($record): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $header = $record->jurnalPembelian;
        $header->load(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek', 'details.rekeningDebit.kelompok', 'details.nomorBantuDebit', 'createdBy']);

        $items = [];
        // Debit Items (Details)
        foreach ($header->details as $detail) {
            $items[] = [
                'code' => $detail->kode_sakep_debit,
                'name' => $detail->nama_akun_debit,
                'description' => $detail->keterangan ?? $header->keterangan,
                'debit' => $detail->jumlah,
                'credit' => 0,
            ];
        }

        // Credit Item (Hutang)
        $items[] = [
            'code' => $header->kode_sakep_kredit,
            'name' => $header->nama_akun_kredit,
            'description' => $header->keterangan,
            'debit' => 0,
            'credit' => $header->rp,
        ];

        $voucher = [
            'title' => 'BUKTI JURNAL PEMBELIAN',
            'number' => $header->bukti ?? $header->no_reff,
            'date' => $header->tanggal,
            'reference' => $header->no_reff,
            'description' => $header->keterangan,
            'payee' => $header->nama_nomor_bantu_kredit ?? '-',
            'created_by' => $header->createdBy?->name,
            'items' => $items,
        ];

        $pdf = Pdf::loadView('pdf.voucher', [
            'voucher' => $voucher,
            'company' => \App\Models\Company::first(),
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->no_reff);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'voucher-pembelian-' . $safeFilename . '.pdf'
        );
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informasi Jurnal')
                    ->description('Informasi dasar transaksi jurnal pembelian')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('jurnalPembelian.no_reff')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('primary'),

                                Components\TextEntry::make('jurnalPembelian.tanggal')
                                    ->label('Tanggal')
                                    ->date('d/m/Y')
                                    ->badge()
                                    ->color('info'),

                                Components\IconEntry::make('jurnalPembelian.is_confirmed')
                                    ->label('Status Konfirmasi')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-clock')
                                    ->trueColor('success')
                                    ->falseColor('warning'),
                            ]),
                    ])
                    ->collapsible(),

                Components\Section::make('Akun Hutang/Kredit')
                    ->description('Informasi rekening yang dikreditkan')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('jurnalPembelian.kode_sakep_kredit')
                                    ->label('Kode SAKEP')
                                    ->badge()
                                    ->color('success'),

                                Components\TextEntry::make('jurnalPembelian.nama_akun_kredit')
                                    ->label('Nama Akun')
                                    ->size('lg')
                                    ->weight('semibold'),
                            ]),
                    ])
                    ->collapsible(),

                Components\Section::make('Daftar Item Pembelian')
                    ->description('Detail item barang/jasa yang dibeli')
                    ->schema([
                        Components\RepeatableEntry::make('jurnalPembelian.details')
                            ->hiddenLabel()
                            ->schema([
                                Components\Grid::make(3)
                                    ->schema([
                                        Components\TextEntry::make('nama_akun_debit')
                                            ->label('Kode/Nama Rekening')
                                            ->formatStateUsing(fn ($state, $record) => "[$record->kode_sakep_debit] $state")
                                            ->weight('medium'),

                                        Components\TextEntry::make('kodeProyek.name')
                                            ->label('Proyek')
                                            ->placeholder('-')
                                            ->weight('medium'),

                                        Components\TextEntry::make('jumlah')
                                            ->label('Nominal')
                                            ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                            ->alignRight()
                                            ->weight('bold')
                                            ->color('success'),

                                        Components\TextEntry::make('keterangan')
                                            ->label('Keterangan')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->grid(1)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Components\Section::make('Total Transaksi')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('jurnalPembelian.rp')
                                    ->label('Total Nilai Pembelian')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('primary'),

                                Components\TextEntry::make('jurnalPembelian.created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
                    ->compact(),

                // ===================== STATUS & AUDIT =====================
                Components\Section::make('Status & Audit')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Components\Grid::make(4)->schema([
                            Components\IconEntry::make('jurnalPembelian.is_confirmed')
                                ->label('Status Konfirmasi')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-badge')
                                ->falseIcon('heroicon-o-clock')
                                ->trueColor('success')
                                ->falseColor('warning'),

                            Components\TextEntry::make('jurnalPembelian.confirmed_at')
                                ->label('Dikonfirmasi Pada')
                                ->dateTime('d F Y H:i')
                                ->placeholder('Belum dikonfirmasi'),

                            Components\IconEntry::make('jurnalPembelian.is_posted')
                                ->label('Status Posting')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-badge')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('gray'),

                            Components\TextEntry::make('jurnalPembelian.updated_at')
                                ->label('Terakhir Diubah')
                                ->dateTime('d F Y H:i'),
                        ]),
                    ]),
            ]);
    }
}
