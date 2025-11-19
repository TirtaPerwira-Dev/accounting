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
                ->label('✓ Konfirmasi')
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
                    ->schema([
                        Components\TextEntry::make('no_reff')
                            ->label('No. Referensi'),

                        Components\TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d M Y'),

                        Components\TextEntry::make('bukti')
                            ->label('No. Bukti'),

                        Components\TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Components\Section::make('Akun Hutang/Kredit')
                    ->schema([
                        Components\TextEntry::make('kode_sakep_kredit')
                            ->label('Kode SAKEP'),

                        Components\TextEntry::make('nama_akun_kredit')
                            ->label('Nama Akun')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Components\Section::make('Detail Pembelian')
                    ->schema([
                        Components\RepeatableEntry::make('pembelian_items_with_details')
                            ->label('')
                            ->schema([
                                Components\TextEntry::make('keterangan')
                                    ->label('Keterangan'),

                                Components\TextEntry::make('kode_sakep_debit')
                                    ->label('Kode SAKEP'),

                                Components\TextEntry::make('nama_akun_debit')
                                    ->label('Akun Debit'),

                                Components\TextEntry::make('jumlah')
                                    ->label('Jumlah')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                    ->alignRight(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Total & Status')
                    ->schema([
                        Components\TextEntry::make('rp')
                            ->label('Total')
                            ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                            ->size('lg')
                            ->weight('bold'),

                        Components\IconEntry::make('is_confirmed')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-clock')
                            ->trueColor('success')
                            ->falseColor('warning'),

                        Components\TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(3),
            ]);
    }
}
