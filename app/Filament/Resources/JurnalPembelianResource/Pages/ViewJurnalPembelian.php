<?php

namespace App\Filament\Resources\JurnalPembelianResource\Pages;

use App\Filament\Resources\JurnalPembelianResource;
use Filament\Actions;
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
            'nomorBantuKredit',
            'kelompokDebit',
            'rekeningDebit',
            'nomorBantuDebit',
            'kodeProyek'
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

                Components\Section::make('Detail Pembelian')
                    ->description('Informasi detail item pembelian')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('bukti_item')
                                    ->label('No. Bukti')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('gray'),

                                Components\TextEntry::make('kodeProyek.name')
                                    ->label('Kode Proyek')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('info'),

                                Components\TextEntry::make('kode_sakep_debit')
                                    ->label('Kode SAKEP Debit')
                                    ->badge()
                                    ->color('warning'),
                            ]),

                        Components\Fieldset::make('Item Details')
                            ->schema([
                                Components\TextEntry::make('keterangan_item')
                                    ->label('Keterangan Item')
                                    ->size('lg')
                                    ->weight('semibold')
                                    ->columnSpanFull(),

                                Components\TextEntry::make('nama_akun_debit')
                                    ->label('Nama Akun Debit'),

                                Components\TextEntry::make('jumlah_item')
                                    ->label('Nominal Item')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success'),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible(),

                Components\Section::make('Nilai Transaksi')
                    ->description('Informasi nilai dan status item pembelian ini')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('jumlah_item')
                                    ->label('Nilai Item')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success'),

                                Components\TextEntry::make('is_confirmed')
                                    ->label('Status Konfirmasi')
                                    ->formatStateUsing(fn($state) => $state ? 'Dikonfirmasi' : 'Pending')
                                    ->badge()
                                    ->color(fn($state) => $state ? 'success' : 'warning'),

                                Components\TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d/m/Y H:i')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
