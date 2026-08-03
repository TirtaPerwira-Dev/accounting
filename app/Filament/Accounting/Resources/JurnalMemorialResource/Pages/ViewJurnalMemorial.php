<?php

namespace App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;

use App\Filament\Accounting\Resources\JurnalMemorialResource;
use App\Services\JournalPostingService;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewJurnalMemorial extends ViewRecord
{
    protected static string $resource = JurnalMemorialResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load([
            'jurnalMemorial.rekening.kelompok',
            'jurnalMemorial.nomorBantu',
            'jurnalMemorial.kodeProyek',
            'jurnalMemorial.details.rekening.kelompok',
            'jurnalMemorial.details.nomorBantu',
            'jurnalMemorial.details.kodeProyek',
            'jurnalMemorial.confirmedBy',
            'jurnalMemorial.createdBy',
            'jurnalMemorial.postedBy',
        ]);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn($record) => $record->jurnalMemorial && !$record->jurnalMemorial->is_posted && auth()->user()->can('postToLedger', $record->jurnalMemorial)),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible(fn($record) => $record->jurnalMemorial && auth()->user()->can('postToLedger', $record->jurnalMemorial))
                ->action(function ($record) {
                    $header = $record->jurnalMemorial;
                    if (!$header) {
                        Notification::make()
                            ->title('Data header jurnal tidak ditemukan')
                            ->danger()
                            ->send();

                        return null;
                    }

                    $header->load(['rekening.kelompok', 'nomorBantu', 'kodeProyek', 'details.rekening.kelompok', 'details.nomorBantu']);

                    $pdf = Pdf::loadView('reports.jurnal-memorial-single', [
                        'jurnal' => $header,
                        'generatedAt' => now()->format('d M Y H:i'),
                    ]);

                    $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->bukti ?? $header->id);

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
                        if (!$record->jurnalMemorial) {
                            throw new \RuntimeException('Data header jurnal tidak ditemukan.');
                        }

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
                ->visible(fn($record) => $record->jurnalMemorial && !$record->jurnalMemorial->is_posted && auth()->user()->can('postToLedger', $record->jurnalMemorial)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $header = $this->record->jurnalMemorial;

        return $infolist
            ->schema([
                Components\Section::make('Informasi Jurnal')
                    ->description('Informasi dasar transaksi jurnal memorial')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('jurnalMemorial.no_reff')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('primary'),

                                Components\TextEntry::make('jurnalMemorial.tanggal')
                                    ->label('Tanggal')
                                    ->date('d/m/Y')
                                    ->badge()
                                    ->color('info'),

                                Components\IconEntry::make('jurnalMemorial.is_confirmed')
                                    ->label('Status Konfirmasi')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-clock')
                                    ->trueColor('success')
                                    ->falseColor('warning'),
                            ]),

                        Components\TextEntry::make('jurnalMemorial.bukti')
                            ->label('No. Bukti')
                            ->copyable(),

                        Components\TextEntry::make('jurnalMemorial.keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->collapsible(),

                Components\Section::make('Akun Header Memorial')
                    ->description('Akun utama di header transaksi memorial')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('header_kode_akun')
                                    ->label('Kode Akun')
                                    ->state(function () use ($header) {
                                        if (!$header?->rekening) {
                                            return '-';
                                        }

                                        $noKel = (int) ($header->rekening->kelompok?->no_kel ?? 0);
                                        $noRek = (int) $header->rekening->no_rek;
                                        $noBantu = $header->nomorBantu?->no_bantu;

                                        return $noBantu
                                            ? sprintf('%02d-%04d-%s', $noKel, $noRek, $noBantu)
                                            : sprintf('%02d-%04d', $noKel, $noRek);
                                    })
                                    ->badge()
                                    ->color('success'),

                                Components\TextEntry::make('jurnalMemorial.rekening.nama_rek')
                                    ->label('Nama Rekening')
                                    ->placeholder('-')
                                    ->weight('semibold'),

                                Components\TextEntry::make('jurnalMemorial.kode')
                                    ->label('Posisi Header')
                                    ->badge()
                                    ->color(fn($state) => $state === 'D' ? 'danger' : 'success')
                                    ->formatStateUsing(fn($state) => $state === 'D' ? 'Debit' : 'Kredit'),
                            ]),
                    ])
                    ->collapsible(),

                Components\Section::make('Daftar Item Memorial')
                    ->description(fn() => 'Total baris item: ' . ($header?->details?->count() ?? 0))
                    ->schema([
                        Components\RepeatableEntry::make('jurnalMemorial.details')
                            ->hiddenLabel()
                            ->schema([
                                Components\Grid::make(3)
                                    ->schema([
                                        Components\TextEntry::make('kode_akun')
                                            ->label('Kode/Nama Rekening')
                                            ->state(function ($record) {
                                                if (!$record?->rekening) {
                                                    return '-';
                                                }

                                                $noKel = (int) ($record->rekening->kelompok?->no_kel ?? 0);
                                                $noRek = (int) $record->rekening->no_rek;
                                                $noBantu = $record->nomorBantu?->no_bantu;
                                                $kode = $noBantu
                                                    ? sprintf('%02d-%04d-%s', $noKel, $noRek, $noBantu)
                                                    : sprintf('%02d-%04d', $noKel, $noRek);

                                                return '[' . $kode . '] ' . ($record->rekening->nama_rek ?? '-');
                                            })
                                            ->weight('medium'),

                                        Components\TextEntry::make('kodeProyek.name')
                                            ->label('Proyek')
                                            ->placeholder('-'),

                                        Components\TextEntry::make('jumlah')
                                            ->label('Nominal')
                                            ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                            ->alignRight()
                                            ->weight('bold')
                                            ->color('success'),

                                        Components\TextEntry::make('posisi')
                                            ->label('Posisi')
                                            ->badge()
                                            ->color(fn($state) => $state === 'D' ? 'danger' : 'success')
                                            ->formatStateUsing(fn($state) => $state === 'D' ? 'Debit' : 'Kredit'),

                                        Components\TextEntry::make('keterangan')
                                            ->label('Keterangan')
                                            ->columnSpanFull()
                                            ->placeholder('-'),
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
                                Components\TextEntry::make('jurnalMemorial.rp')
                                    ->label('Total Nilai Memorial')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('primary'),

                                Components\TextEntry::make('jurnalMemorial.created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
                    ->compact(),
            ]);
    }
}
