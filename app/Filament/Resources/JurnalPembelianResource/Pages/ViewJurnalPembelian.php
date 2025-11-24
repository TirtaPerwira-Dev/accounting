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

                        Components\TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

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
                        Components\RepeatableEntry::make('pembelian_items')
                            ->label('')
                            ->schema([
                                Components\TextEntry::make('bukti')
                                    ->label('No. Bukti'),

                                Components\TextEntry::make('keterangan')
                                    ->label('Keterangan'),

                                Components\TextEntry::make('kode_proyek_id')
                                    ->label('Kode Proyek')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '-';
                                        $kodeProyek = \App\Models\KodeProyek::find($state);
                                        return $kodeProyek ? $kodeProyek->name : '-';
                                    }),

                                Components\TextEntry::make('nomor_bantu_debit_id')
                                    ->label('Kode SAKEP')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '-';
                                        $nomorBantu = \App\Models\NomorBantu::with(['rekening.kelompok'])->find($state);
                                        if (!$nomorBantu) return '-';

                                        return $nomorBantu->rekening->kelompok->no_kel .
                                            $nomorBantu->rekening->no_rek .
                                            str_pad($nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT);
                                    }),

                                Components\TextEntry::make('nomor_bantu_debit_id')
                                    ->label('Akun Debit')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '-';
                                        $nomorBantu = \App\Models\NomorBantu::find($state);
                                        return $nomorBantu ? $nomorBantu->nm_bantu : '-';
                                    }),

                                Components\TextEntry::make('jumlah')
                                    ->label('Jumlah')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                    ->alignRight(),
                            ])
                            ->columns(6)
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
