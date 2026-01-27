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
        // Load relationships for proper display
        $this->record->load([
            'kelompokKredit',
            'rekeningKredit',
            'nomorBantuDebit',
            'kodeProyek',
            'details.kelompokDebit',
            'details.rekeningDebit',
            'details.nomorBantuDebit',
            'details.kodeProyek',
        ]);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn($record) => !$record->is_confirmed),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function ($record) {
                    return $this->generateJurnalPdf($record);
                }),

            Actions\Action::make('confirm')
                ->label('Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn($record) => !$record->is_confirmed)
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Jurnal')
                ->modalSubheading('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit.')
                ->action(fn($record) => $record->confirm())
                ->successNotificationTitle('Jurnal berhasil dikonfirmasi'),

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

            Actions\DeleteAction::make()
                ->visible(fn($record) => !$record->is_confirmed),
        ];
    }

    protected function generateJurnalPdf($record): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $record->load(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek']);

        $pdf = Pdf::loadView('reports.jurnal-pembelian-single', [
            'jurnal' => $record,
            'generatedAt' => now()->format('d M Y H:i'),
        ]);

        // Sanitize filename - remove invalid characters
        $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->no_reff);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'jurnal-pembelian-' . $safeFilename . '.pdf'
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
                                Components\TextEntry::make('no_reff')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('primary'),

                                Components\TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d/m/Y')
                                    ->badge()
                                    ->color('info'),

                                Components\IconEntry::make('is_confirmed')
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
                                Components\TextEntry::make('kode_sakep_kredit')
                                    ->label('Kode SAKEP')
                                    ->badge()
                                    ->color('success'),

                                Components\TextEntry::make('nama_akun_kredit')
                                    ->label('Nama Akun')
                                    ->size('lg')
                                    ->weight('semibold'),
                            ]),
                    ])
                    ->collapsible(),

                Components\Section::make('Daftar Item Pembelian')
                    ->description('Detail item barang/jasa yang dibeli')
                    ->schema([
                        Components\RepeatableEntry::make('details')
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
                                Components\TextEntry::make('rp')
                                    ->label('Total Nilai Pembelian')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('primary'),

                                Components\TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
                    ->compact(),
            ]);
    }
}
