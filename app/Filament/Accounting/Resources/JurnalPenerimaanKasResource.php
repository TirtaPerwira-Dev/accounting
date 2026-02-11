<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\Pages;
use App\Filament\Widgets\JurnalPenerimaanKasStatsWidget;
use App\Models\JurnalPenerimaanKas;
use App\Models\JurnalPenerimaanKasDetail;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Imports\JurnalPenerimaanKasImport;
use App\Exports\JurnalPenerimaanKasTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class JurnalPenerimaanKasResource extends Resource
{
    protected static ?string $model = JurnalPenerimaanKasDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Jurnal Penerimaan Kas';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralModelLabel = 'Jurnal Penerimaan Kas/Bank';

    protected static ?string $slug = 'jurnal-penerimaan-kas';

    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Models\JurnalPenerimaanKas::where('is_confirmed', 0)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : 'success';
    }

    // Eager load relationships for better performance
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'jurnalPenerimaanKas' => function ($query) {
                    $query->with([
                        'kasBank.rekening.kelompok',
                        'confirmedBy',
                        'createdBy',
                        'details.kelompok',
                        'details.rekening.kelompok',
                        'details.nomorBantu',
                        'details.kodeProyek'
                    ]);
                },
                'kelompok',
                'rekening.kelompok',
                'nomorBantu',
                'kodeProyek'
            ]);
    }

    // Authorization helpers

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === SECTION 1: KAS/BANK TUJUAN (DEBIT) ===
                Forms\Components\Section::make('Kas/Bank Tujuan (DEBIT)')
                    ->description('Pilih rekening kas atau bank tempat uang masuk')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                // Rekening
                                Forms\Components\Select::make('rekening_id')
                                    ->label('Rekening')
                                    ->options(function () {
                                        return Rekening::whereHas('kelompok', fn($q) => $q->where('no_kel', '10'))
                                            ->where(function ($q) {
                                                $q->where('no_rek', 'like', '1101%') // Kas
                                                    ->orWhere('no_rek', 'like', '1102%'); // Bank
                                            })
                                            ->get()
                                            ->mapWithKeys(fn($rekening) => [
                                                $rekening->id => "{$rekening->no_rek} - {$rekening->nama_rek}"
                                            ]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                // Nomor Bantu
                                Forms\Components\Select::make('kas_bank_id')
                                    ->label('Nomor Bantu')
                                    ->options(function (Forms\Get $get) {
                                        $rekeningId = $get('rekening_id');
                                        if (!$rekeningId) return [];

                                        return NomorBantu::where('rekening_id', $rekeningId)
                                            ->get()
                                            ->mapWithKeys(fn($item) => [
                                                $item->id => "{$item->no_bantu} - {$item->nm_bantu}"
                                            ]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Pilih Nomor Bantu'),

                                // Tanggal
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->native(false),
                            ]),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan Umum')
                            ->placeholder('Contoh: Penerimaan dari penjualan, Penerimaan bunga bank, dll')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // === SECTION 2: ITEM SUMBER PENERIMAAN ===
                Forms\Components\Section::make('Item Sumber Penerimaan (KREDIT)')
                    ->description(fn (string $context): string => $context === 'create'
                        ? 'Tambahkan item sumber penerimaan satu per satu'
                        : 'Masukkan satu atau lebih sumber penerimaan')
                    ->schema(function (string $context) {
                        if ($context === 'create') {
                            return [
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('temp_nomor_bukti')
                                        ->label('No. Bukti')
                                        ->placeholder('BKM-XXX')
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_kode_proyek_id')
                                        ->label('Kode Proyek')
                                        ->options(KodeProyek::all()->pluck('name', 'id'))
                                        ->searchable()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('temp_jumlah')
                                        ->label('Jumlah (Rp)')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->extraAttributes(['style' => 'text-align: right;'])
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('temp_rekening_id')
                                        ->label('Rekening (Sumber)')
                                        ->options(function () {
                                            return Rekening::with('kelompok')
                                                ->get()
                                                ->mapWithKeys(fn($rekening) => [
                                                    $rekening->id => "{$rekening->no_rek} - {$rekening->nama_rek}"
                                                ]);
                                        })
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(fn(callable $set) => $set('temp_nomor_bantu_id', null))
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_nomor_bantu_id')
                                        ->label('Nomor Bantu')
                                        ->options(function (Forms\Get $get) {
                                            $rekeningId = $get('temp_rekening_id');
                                            if (!$rekeningId) return [];

                                            return NomorBantu::where('rekening_id', $rekeningId)
                                                ->get()
                                                ->mapWithKeys(fn($item) => [
                                                    $item->id => $item->no_bantu . ' - ' . $item->nm_bantu
                                                ]);
                                        })
                                        ->searchable()
                                        ->placeholder('Pilih No. Bantu')
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Textarea::make('temp_keterangan_item')
                                    ->label('Keterangan Item')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->dehydrated(false),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('add_item')
                                        ->label('Tambah Item')
                                        ->icon('heroicon-o-plus-circle')
                                        ->color('warning')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $tempData = [
                                                'nomor_bukti' => $get('temp_nomor_bukti'),
                                                'kode_proyek' => $get('temp_kode_proyek_id'),
                                                'jumlah' => (float) ($get('temp_jumlah') ?? 0),
                                                'rekening' => $get('temp_rekening_id'),
                                                'nomor_bantu' => $get('temp_nomor_bantu_id'),
                                                'keterangan_item' => $get('temp_keterangan_item'),
                                            ];

                                            if (empty($tempData['rekening']) || empty($tempData['jumlah'])) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Data tidak lengkap!')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $currentItems = $get('penerimaan_items') ?? [];
                                            $currentItems[] = $tempData;
                                            $set('penerimaan_items', $currentItems);

                                            // Clear temp fields
                                            $set('temp_nomor_bukti', null);
                                            $set('temp_kode_proyek_id', null);
                                            $set('temp_jumlah', null);
                                            $set('temp_rekening_id', null);
                                            $set('temp_nomor_bantu_id', null);
                                            $set('temp_keterangan_item', null);
                                        }),
                                ])->alignment('center'),

                                Forms\Components\ViewField::make('penerimaan_items')
                                    ->view('filament.forms.components.penerimaan-kas-items-table'),
                            ];
                        }

                        // EDIT OPERATION: Use Repeater
                        return [
                            Forms\Components\Repeater::make('penerimaan_items')
                                ->label('Items')
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('nomor_bukti')
                                            ->label('No. Bukti')
                                            ->placeholder('BKM-XXX'),

                                        Forms\Components\Select::make('kode_proyek_id')
                                            ->label('Kode Proyek')
                                            ->options(KodeProyek::all()->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah (Rp)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('Rp')
                                            ->extraAttributes(['style' => 'text-align: right;']),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('rekening_id')
                                            ->label('Rekening (Sumber)')
                                            ->options(function () {
                                                return Rekening::with('kelompok')
                                                    ->get()
                                                    ->mapWithKeys(fn($rekening) => [
                                                        $rekening->id => "{$rekening->no_rek} - {$rekening->nama_rek}"
                                                    ]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('nomor_bantu_id')
                                            ->label('Nomor Bantu')
                                            ->options(function (Forms\Get $get) {
                                                $rekeningId = $get('rekening_id');
                                                if (!$rekeningId) return [];

                                                return NomorBantu::where('rekening_id', $rekeningId)
                                                    ->get()
                                                    ->mapWithKeys(fn($item) => [
                                                        $item->id => $item->no_bantu . ' - ' . $item->nm_bantu
                                                    ]);
                                            })
                                            ->searchable()
                                            ->placeholder('Pilih No. Bantu'),
                                    ]),

                                    Forms\Components\Textarea::make('keterangan_item')
                                        ->label('Keterangan Item')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                                ->defaultItems(1)
                                ->addActionLabel('Tambah Item Penerimaan'),
                        ];
                    }),

                Forms\Components\Section::make('Nomor Referensi')
                    ->schema([
                        Forms\Components\Placeholder::make('no_reff_preview')
                            ->label('Nomor Referensi')
                            ->content('Nomor Reff Jurnal Penerimaan Kas adalah = 3')
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(),

                // Hidden Fields
                Forms\Components\Hidden::make('no_reff')->default('3'),
                Forms\Components\Hidden::make('company_id')->default(1),
                Forms\Components\Hidden::make('created_by')->default(fn() => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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

                            $import = new JurnalPenerimaanKasImport();
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
                                    ->body("Berhasil mengimport {$import->getImportedCount()} data jurnal penerimaan kas")
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

                Tables\Actions\Action::make('download_template')
                    ->label('Download Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function () {
                        return Excel::download(
                            new JurnalPenerimaanKasTemplateExport(),
                            'template-jurnal-penerimaan-kas.xlsx'
                        );
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('nomor_bukti')
                    ->label('Bukti')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('keterangan_item')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('kodeProyekRekening')
                    ->label('Kode Proyek/Rekening')
                    ->html()
                    ->getStateUsing(function ($record) {
                        // Format 2 baris: AA BBBB + Nama
                        $kodeProyek = $record->kodeProyek?->kode ?? '';
                        $namaProyek = $record->kodeProyek?->name ?? '';
                        $rekening = $record->rekening?->no_rek ?? '';
                        $namaRekening = $record->rekening?->nama_rek ?? '';

                        $kode = ($kodeProyek && $rekening)
                            ? sprintf('%02d %04d', intval($kodeProyek), intval($rekening))
                            : ($rekening ? sprintf('-- %04d', intval($rekening)) : '-');

                        $nama = trim(($namaProyek ? $namaProyek : '') . ($namaProyek && $namaRekening ? ' - ' : '') . ($namaRekening ? $namaRekening : ''));

                        return "<div class='font-medium'>{$kode}</div><div class='text-xs text-gray-500'>{$nama}</div>";
                    })
                    ->searchable(false)
                    ->wrap(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight(),

                Tables\Columns\IconColumn::make('jurnalPenerimaanKas.is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('jurnalPenerimaanKas.is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('jurnalPenerimaanKas.no_reff')
                    ->label('No Reff')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn($q) =>
                                $q->whereHas(
                                    'jurnalPenerimaanKas',
                                    fn($query) =>
                                    $query->whereDate('tanggal', '>=', $data['dari_tanggal'])
                                )
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn($q) =>
                                $q->whereHas(
                                    'jurnalPenerimaanKas',
                                    fn($query) =>
                                    $query->whereDate('tanggal', '<=', $data['sampai_tanggal'])
                                )
                            );
                    }),
                Tables\Filters\TernaryFilter::make('is_posted')
                    ->label('Status Posting')
                    ->placeholder('Semua Status')
                    ->trueLabel('Sudah Diposting')
                    ->falseLabel('Belum Diposting')
                    ->queries(
                        true: fn($query) => $query->whereHas('jurnalPenerimaanKas', fn($q) => $q->where('is_posted', true)),
                        false: fn($query) => $query->whereHas('jurnalPenerimaanKas', fn($q) => $q->where('is_posted', false)),
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
                        ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_confirmed),

                    Tables\Actions\Action::make('confirm')
                        ->label('✓ Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->jurnalPenerimaanKas->confirm();
                            Notification::make()
                                ->title('Jurnal berhasil dikonfirmasi')
                                ->body("No. Reff: {$record->jurnalPenerimaanKas->no_reff}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription(fn($record) => "Apakah Anda yakin ingin mengkonfirmasi jurnal {$record->jurnalPenerimaanKas->no_reff}?")
                        ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_confirmed && auth()->user()->can('confirm', $record->jurnalPenerimaanKas)),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('↶ Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($record) {
                            $record->jurnalPenerimaanKas->unconfirm();
                            Notification::make()
                                ->title('Konfirmasi jurnal dibatalkan')
                                ->body("No. Reff: {$record->jurnalPenerimaanKas->no_reff}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Batal Konfirmasi Jurnal')
                        ->modalDescription(fn($record) => "Apakah Anda yakin ingin membatalkan konfirmasi jurnal {$record->jurnalPenerimaanKas->no_reff}?")
                        ->visible(fn($record) => $record->jurnalPenerimaanKas->is_confirmed && auth()->user()->can('unconfirm', $record->jurnalPenerimaanKas)),

                    Tables\Actions\Action::make('exportPdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($record) {
                            $parent = $record->jurnalPenerimaanKas;
                            $parent->load([
                                'kasBank.rekening.kelompok',
                                'details.rekening.kelompok',
                                'details.nomorBantu',
                                'details.kodeProyek',
                                'createdBy',
                                'confirmedBy',
                                'postedBy'
                            ]);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.jurnal-penerimaan-kas', [
                                'record' => $parent,
                            ]);

                            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $parent->nomor_bukti ?? $parent->id);

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                'jurnal-penerimaan-kas-' . $safeFilename . '.pdf'
                            );
                        }),

                    Tables\Actions\Action::make('post_to_ledger')
                        ->label('Post ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record, \App\Services\JournalPostingService $service) {
                            try {
                                $service->post($record->jurnalPenerimaanKas);
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
                        ->visible(fn($record) => $record->jurnalPenerimaanKas->is_confirmed && !$record->jurnalPenerimaanKas->is_posted),


                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->modalHeading('Hapus Item Transaksi')
                        ->modalDescription(fn($record) => "Item ini akan dihapus dari jurnal {$record->jurnalPenerimaanKas->no_reff}")
                        ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_confirmed)
                        ->after(function ($record) {
                            // Check if parent jurnal still has details
                            $parent = $record->jurnalPenerimaanKas;
                            if ($parent && $parent->details()->count() === 0) {
                                // Delete parent if no more details
                                $parent->delete();
                                Notification::make()
                                    ->title('Jurnal dihapus')
                                    ->body('Jurnal header juga dihapus karena tidak memiliki item lagi')
                                    ->warning()
                                    ->send();
                            }
                        }),
                ])
                    ->button()
                    ->label('Action')
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
                            $parents = $records->map(fn($record) => $record->jurnalPenerimaanKas)
                                ->filter(fn($parent) => $parent && $parent->is_confirmed && !$parent->is_posted)
                                ->unique('id');

                            if ($parents->isEmpty()) {
                                Notification::make()
                                    ->title('Tidak ada jurnal yang valid untuk diposting')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $count = $service->postBulk($parents);

                            Notification::make()
                                ->title("{$count} Jurnal berhasil diposting ke Buku Besar")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            // Tidak perlu DetailsRelationManager karena details sudah ditampilkan di infolist
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\JurnalPenerimaanKasStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalPenerimaanKas::route('/'),
            'create' => Pages\CreateJurnalPenerimaanKas::route('/create'),
            'view' => Pages\ViewJurnalPenerimaanKas::route('/{record}'),
            'edit' => Pages\EditJurnalPenerimaanKas::route('/{record}/edit'),
        ];
    }
}
