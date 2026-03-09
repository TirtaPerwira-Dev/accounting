<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;
use App\Filament\Widgets\JurnalBayarKasBankStatsWidget;
use App\Models\JurnalBayarKasBank;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Imports\JurnalBayarKasBankImport;
use App\Exports\JurnalBayarKasBankTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class JurnalBayarKasBankResource extends Resource
{
    protected static ?string $model = \App\Models\JurnalBayarKasBankDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Jurnal Bayar Kas/Bank';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 4;

    protected static ?string $pluralModelLabel = 'Jurnal Bayar Kas/Bank';

    protected static ?string $slug = 'jurnal-bayar-kas-bank';

    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Models\JurnalBayarKasBank::where('is_confirmed', 0)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : 'success';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with([
            'jurnalBayarKasBank.rekening.kelompok',
            'jurnalBayarKasBank.nomorBantu',
            'rekening.kelompok',
            'nomorBantu',
            'kodeProyek',
        ]);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: Header Transaksi
                Forms\Components\Section::make('Informasi Pembayaran (Header)')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            // No Voucher
                            Forms\Components\TextInput::make('no_voucher')
                                ->label('No. Voucher')
                                ->required()
                                ->maxLength(255),

                            // Tanggal Check
                            Forms\Components\DatePicker::make('tanggal_check')
                                ->label('Tanggal Check')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required(),

                            // Kode Rekening Bank (Rekening + Nomor Bantu)
                            Forms\Components\Select::make('rekening_bank_id')
                                ->label('Akun Pembayar (Bank/Kas)')
                                ->options(function () {
                                    return Rekening::with(['kelompok', 'nomorBantus'])
                                        ->whereHas('kelompok', fn($q) => $q->whereIn('no_kel', ['10']))
                                        ->get()
                                        ->flatMap(function ($rekening) {
                                            if ($rekening->nomorBantus->count() > 0) {
                                                return $rekening->nomorBantus->mapWithKeys(fn($nb) => [
                                                    $rekening->id . '|' . $nb->id => "{$rekening->no_rek} {$nb->no_bantu} - {$nb->nm_bantu}"
                                                ]);
                                            }
                                            return [$rekening->id . '|0' => "{$rekening->no_rek} - {$rekening->nama_rek}"];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (!$state) return;
                                    [$rekeningId, $nomorBantuId] = explode('|', $state);
                                    $set('rekening_id', $rekeningId);
                                    $set('nomor_bantu_id', $nomorBantuId > 0 ? $nomorBantuId : null);
                                }),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('no_cek')
                                ->label('No. Cek')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('beban_bagian')
                                ->label('Beban Bagian')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('dibayar_kepada')
                                ->label('Dibayar Kepada')
                                ->maxLength(255),
                        ]),

                        // Hidden fields for backend
                        Forms\Components\Hidden::make('no_reff')->default('4'),
                        Forms\Components\Hidden::make('rekening_id'),
                        Forms\Components\Hidden::make('nomor_bantu_id'),
                    ]),

                // SECTION 2: Item Transaksi
                Forms\Components\Section::make('Item Pembayaran')
                    ->description(fn (string $context): string => $context === 'create'
                        ? 'Tambahkan item pembayaran satu per satu'
                        : 'Masukkan satu atau lebih item pembayaran')
                    ->schema(function (string $context) {
                        if ($context === 'create') {
                            return [
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('temp_rekening_id')
                                        ->label('Rekening')
                                        ->options(function () {
                                            return Rekening::with('kelompok')
                                                ->get()
                                                ->mapWithKeys(fn($rekening) => [
                                                    $rekening->id => "{$rekening->kelompok->no_kel}-{$rekening->no_rek} - {$rekening->nama_rek}"
                                                ]);
                                        })
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(fn(callable $set) => $set('temp_nomor_bantu_id', null))
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_nomor_bantu_id')
                                        ->label('Nomor Bantu')
                                        ->options(function (callable $get) {
                                            $rekeningId = $get('temp_rekening_id');
                                            if (!$rekeningId) return [];

                                            return NomorBantu::where('rekening_id', $rekeningId)
                                                ->get()
                                                ->mapWithKeys(fn($item) => [$item->id => $item->no_bantu . ' - ' . $item->nm_bantu]);
                                        })
                                        ->searchable()
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_kode_proyek_id')
                                        ->label('Kode Proyek')
                                        ->options(KodeProyek::pluck('name', 'id'))
                                        ->searchable()
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('temp_jumlah')
                                        ->label('Jumlah (Rp)')
                                        ->prefix('Rp')
                                        ->numeric()
                                        ->extraAttributes(['style' => 'text-align: right;'])
                                        ->dehydrated(false),

                                    Forms\Components\Textarea::make('temp_keterangan')
                                        ->label('Keterangan')
                                        ->placeholder('Detail pembayaran...')
                                        ->rows(1)
                                        ->columnSpan(2)
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('add_item')
                                        ->label('Tambah Item')
                                        ->icon('heroicon-o-plus-circle')
                                        ->color('warning')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $tempData = [
                                                'rekening' => $get('temp_rekening_id'),
                                                'nomor_bantu' => $get('temp_nomor_bantu_id'),
                                                'kode_proyek' => $get('temp_kode_proyek_id'),
                                                'jumlah' => (float) ($get('temp_jumlah') ?? 0),
                                                'keterangan' => $get('temp_keterangan'),
                                            ];

                                            if (empty($tempData['rekening']) || empty($tempData['jumlah'])) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Data tidak lengkap!')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $currentItems = $get('pembayaran_items') ?? [];
                                            $currentItems[] = $tempData;
                                            $set('pembayaran_items', $currentItems);

                                            // Clear temp fields
                                            $set('temp_rekening_id', null);
                                            $set('temp_nomor_bantu_id', null);
                                            $set('temp_kode_proyek_id', null);
                                            $set('temp_jumlah', null);
                                            $set('temp_keterangan', null);
                                        }),
                                ])->alignment('center'),

                                Forms\Components\ViewField::make('pembayaran_items')
                                    ->view('filament.forms.components.bayar-kas-bank-items-table'),
                            ];
                        }

                        // EDIT OPERATION: Use Repeater
                        return [
                            Forms\Components\Repeater::make('pembayaran_items')
                                ->label('Items')
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Select::make('rekening_id')
                                            ->label('Rekening')
                                            ->options(function () {
                                                return Rekening::with('kelompok')
                                                    ->get()
                                                    ->mapWithKeys(fn($rekening) => [
                                                        $rekening->id => "{$rekening->kelompok->no_kel}-{$rekening->no_rek} - {$rekening->nama_rek}"
                                                    ]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn(callable $set) => $set('nomor_bantu_id', null)),

                                        Forms\Components\Select::make('nomor_bantu_id')
                                            ->label('Nomor Bantu')
                                            ->options(function (callable $get) {
                                                $rekeningId = $get('rekening_id');
                                                if (!$rekeningId) return [];

                                                return NomorBantu::where('rekening_id', $rekeningId)
                                                    ->get()
                                                    ->mapWithKeys(fn($item) => [$item->id => $item->no_bantu . ' - ' . $item->nm_bantu]);
                                            })
                                            ->searchable(),

                                        Forms\Components\Select::make('kode_proyek_id')
                                            ->label('Kode Proyek')
                                            ->options(KodeProyek::pluck('name', 'id'))
                                            ->searchable(),
                                    ]),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah (Rp)')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->required()
                                            ->extraAttributes(['style' => 'text-align: right;']),

                                        Forms\Components\Textarea::make('keterangan')
                                            ->label('Keterangan')
                                            ->placeholder('Detail pembayaran...')
                                            ->rows(1)
                                            ->columnSpan(2),
                                    ]),
                                ])
                                ->columns(1)
                                ->defaultItems(1)
                                ->addActionLabel('Tambah Item Pembayaran'),
                        ];
                    }),

                Forms\Components\Section::make('Nomor Referensi')
                    ->schema([
                        Forms\Components\Placeholder::make('no_reff_preview')
                            ->label('Nomor Referensi')
                            ->content('Nomor Reff Jurnal Bayar Kas/Bank adalah = 4')
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(),

                // Hidden Fields
                Forms\Components\Hidden::make('company_id')->default(1),
                Forms\Components\Hidden::make('created_by')->default(fn() => auth()->id()),
                Forms\Components\Hidden::make('kelompok_id')
                    ->dehydrateStateUsing(
                        fn(Forms\Get $get) =>
                        $get('rekening_id') ? Rekening::find($get('rekening_id'))?->kelompok_id : null
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jurnalBayarKasBank.no_voucher')
                    ->label('No Voucher')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jurnalBayarKasBank.tanggal_check')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bank_display')
                    ->label('Bank (Pembayar)')
                    ->getStateUsing(fn($record) => $record->jurnalBayarKasBank->nama_bank_display)
                    ->wrap(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan Item')
                    ->limit(40)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('kodeProyekRekening')
                    ->label('Kode Proyek/Rekening')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $kodeProyek = $record->kodeProyek?->kode ?? '';
                        $namaProyek = $record->kodeProyek?->name ?? '';
                        $rekening = $record->rekening?->no_rek ?? '';
                        $namaRekening = $record->rekening?->nama_rek ?? '';

                        $kode = ($kodeProyek && $rekening)
                            ? sprintf('%02d %04d', intval($kodeProyek), intval($rekening))
                            : ($rekening ? sprintf('-- %04d', intval($rekening)) : '-');

                        $nama = trim(($namaProyek ? $namaProyek : '') . ($namaProyek && $namaRekening ? ' - ' : '') . ($namaRekening ? $namaRekening : ''));

                        return "<div class='font-medium'>{$kode}</div><div class='text-xs text-gray-500'>{$nama}</div>";
                    }),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->jurnalBayarKasBank->is_posted)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->jurnalBayarKasBank->is_confirmed),

                Tables\Columns\TextColumn::make('jurnalBayarKasBank.no_reff')
                    ->label('No Reff'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File Excel')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->directory('imports')
                            ->storeFileNamesIn('original_filename')
                            ->required()
                            ->helperText('Upload file Excel dengan format template yang sudah disediakan')
                    ])
                    ->action(function (array $data) {
                        try {
                            // Get the uploaded file path
                            $filePath = storage_path('app/public/' . $data['file']);

                            // Check if file exists
                            if (!file_exists($filePath)) {
                                throw new \Exception("File tidak ditemukan: {$filePath}");
                            }

                            $import = new JurnalBayarKasBankImport();
                            Excel::import($import, $filePath);

                            // Clean up - delete the uploaded file
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }

                            // Show success or error messages
                            if ($import->getErrors()) {
                                $errorMessage = "Import selesai dengan beberapa error:\n" . implode("\n", array_slice($import->getErrors(), 0, 5));
                                if (count($import->getErrors()) > 5) {
                                    $errorMessage .= "\n... dan " . (count($import->getErrors()) - 5) . " error lainnya";
                                }

                                Notification::make()
                                    ->title('Import Selesai dengan Error')
                                    ->body($errorMessage)
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Import Berhasil')
                                    ->body("Berhasil mengimport {$import->getImportedCount()} data jurnal bayar kas/bank")
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Download Template
                Tables\Actions\Action::make('download_template')
                    ->label('Download Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn() => Excel::download(new JurnalBayarKasBankTemplateExport(), 'template-jurnal-bayar-kas-bank.xlsx')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_confirmed')
                    ->label('Status')
                    ->options([1 => 'Dikonfirmasi', 0 => 'Pending']),

                Tables\Filters\TernaryFilter::make('is_posted')
                    ->label('Status Posting')
                    ->placeholder('Semua Status')
                    ->trueLabel('Sudah Diposting')
                    ->falseLabel('Belum Diposting'),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(
                        fn($query, $data) => $query
                            ->when($data['from'], fn($q, $d) => $q->whereDate('tanggal', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->whereDate('tanggal', '<=', $d))
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->visible(fn($record) => !$record->jurnalBayarKasBank->is_confirmed),

                    Tables\Actions\Action::make('confirm')
                        ->label('✓ Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn($record) => !$record->jurnalBayarKasBank->is_confirmed && auth()->user()->can('confirm_jurnal::bayar::kas::bank'))
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                        ->action(fn($record) => $record->jurnalBayarKasBank->confirm())
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Jurnal berhasil dikonfirmasi')
                                ->body('Jurnal sudah dikonfirmasi dan siap untuk diposting ke buku besar.')
                        ),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('↶ Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->visible(fn($record) => $record->jurnalBayarKasBank->is_confirmed && !$record->jurnalBayarKasBank->is_posted && auth()->user()->can('unconfirm_jurnal::bayar::kas::bank'))
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Konfirmasi')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                        ->action(fn($record) => $record->jurnalBayarKasBank->unconfirm())
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Konfirmasi berhasil dibatalkan')
                                ->body('Jurnal kembali ke status draft dan dapat diedit kembali.')
                        ),

                    Tables\Actions\Action::make('exportPdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($record) {
                            $record->load(['rekening.kelompok', 'nomorBantu', 'kodeProyek', 'details.rekening.kelompok', 'details.nomorBantu']);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-bayar-kas-bank-single', [
                                'jurnal' => $record,
                                'generatedAt' => now()->format('d M Y H:i'),
                            ]);

                            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->no_reff ?? $record->id);

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                'jurnal-bayar-kas-bank-' . $safeFilename . '.pdf'
                            );
                        }),

                    Tables\Actions\Action::make('post_to_ledger')
                        ->label('Post ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record, \App\Services\JournalPostingService $service) {
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
                        ->visible(fn($record) => $record->jurnalBayarKasBank->is_confirmed && !$record->jurnalBayarKasBank->is_posted),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->visible(fn($record) => !$record->jurnalBayarKasBank->is_confirmed),
                ])
                    ->label('Action')
                    ->button()
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_post_to_ledger')
                        ->label('Post Terpilih ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, \App\Services\JournalPostingService $service) {
                            $validHeaders = $records->map(fn($record) => $record->jurnalBayarKasBank)
                                ->filter(fn($header) => $header->is_confirmed && !$header->is_posted)
                                ->unique('id');

                            if ($validHeaders->isEmpty()) {
                                Notification::make()
                                    ->title('Tidak ada jurnal yang valid untuk diposting')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $count = $service->postBulk($validHeaders);
                            
                            Notification::make()
                                ->title("{$count} Jurnal berhasil diposting ke Buku Besar")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('confirm_selected')
                        ->label('✓ Konfirmasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->jurnalBayarKasBank->is_confirmed) {
                                    $record->jurnalBayarKasBank->confirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Jurnal terpilih berhasil dikonfirmasi'),

                    Tables\Actions\BulkAction::make('unconfirm_selected')
                        ->label('↶ Batal Konfirmasi Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->jurnalBayarKasBank->is_confirmed) {
                                    $record->jurnalBayarKasBank->unconfirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Konfirmasi dibatalkan'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            JurnalBayarKasBankStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalBayarKasBanks::route('/'),
            'create' => Pages\CreateJurnalBayarKasBank::route('/create'),
            'view' => Pages\ViewJurnalBayarKasBank::route('/{record}'),
            'edit' => Pages\EditJurnalBayarKasBank::route('/{record}/edit'),
        ];
    }
}
