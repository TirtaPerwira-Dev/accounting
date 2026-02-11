<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalRekeningAirResource\Pages;
use App\Filament\Widgets\JurnalRekeningAirStatsWidget;
use App\Models\JurnalRekeningAir;
use App\Models\JurnalRekeningAirDetail;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Imports\JurnalRekeningAirImport;
use App\Exports\JurnalRekeningAirTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class JurnalRekeningAirResource extends Resource
{
    protected static ?string $model = JurnalRekeningAirDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Jurnal Rekening Air';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralModelLabel = 'Jurnal Rekening Air & Non Air';

    protected static ?string $slug = 'jurnal-rekening-air';

    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Models\JurnalRekeningAir::where('is_confirmed', 0)->count();
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
                'jurnalRekeningAir.company',
                'kelompok',
                'rekening.kelompok',
                'nomorBantu',
                'kodeProyek',
            ]);
    }

    // Authorization helpers

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === SECTION INFORMASI ===
                Forms\Components\Section::make('Informasi Jurnal Rekening Air')
                    ->description('Masukkan informasi dasar jurnal rekening air')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('bukti')
                                    ->label('No. Bukti')
                                    ->placeholder('Contoh: RKA-001, INV-001')
                                    ->required()
                                    ->maxLength(255)
                                    ->autofocus()
                                    ->helperText('Input kode sesuai dengan ketentuan perusahaan.')
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->helperText('Tanggal memilih hari ini secara default.')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Contoh: Rekening air bulan November 2024, Pembayaran supplier, dll')
                            ->rows(2)
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->collapsible(),

                // === SECTION 2: ITEM TRANSAKSI ===
                Forms\Components\Section::make('Item Transaksi')
                    ->description(fn (string $context): string => $context === 'create' 
                        ? 'Tambahkan item transaksi satu per satu' 
                        : 'Masukkan rincian akun (Debit & Kredit harus seimbang)')
                    ->schema(function (string $context) {
                        if ($context === 'create') {
                            return [
                                Forms\Components\Grid::make(5)->schema([
                                    // Kode Proyek
                                    Forms\Components\Select::make('temp_kode_proyek_id')
                                        ->label('Kode Proyek')
                                        ->options(KodeProyek::all()->pluck('name', 'id'))
                                        ->searchable()
                                        ->placeholder('Pilih Proyek')
                                        ->dehydrated(false),

                                    // Rekening
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
                                        ->dehydrated(false),

                                    // Nomor Bantu
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

                                    // D/K
                                    Forms\Components\Select::make('temp_position')
                                        ->label('D/K')
                                        ->options([
                                            'debit' => 'Debit',
                                            'kredit' => 'Kredit',
                                        ])
                                        ->default('debit')
                                        ->dehydrated(false),

                                    // Jumlah
                                    Forms\Components\TextInput::make('temp_jumlah')
                                        ->label('Jumlah (Rp)')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->extraAttributes(['style' => 'text-align: right;'])
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('add_item')
                                        ->label('Tambah Item')
                                        ->icon('heroicon-o-plus-circle')
                                        ->color('warning')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $tempData = [
                                                'rekening_id' => $get('temp_rekening_id'),
                                                'nomor_bantu_id' => $get('temp_nomor_bantu_id'),
                                                'kode_proyek_id' => $get('temp_kode_proyek_id'),
                                                'position' => $get('temp_position'),
                                                'jumlah' => (float) ($get('temp_jumlah') ?? 0),
                                            ];

                                            if (empty($tempData['rekening_id']) || empty($tempData['jumlah'])) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Data tidak lengkap!')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $currentItems = $get('rekening_air_items') ?? [];
                                            $currentItems[] = $tempData;
                                            $set('rekening_air_items', $currentItems);

                                            // Clear temp fields
                                            $set('temp_rekening_id', null);
                                            $set('temp_nomor_bantu_id', null);
                                            $set('temp_kode_proyek_id', null);
                                            $set('temp_jumlah', null);
                                        }),
                                ])->alignment('center'),

                                Forms\Components\ViewField::make('rekening_air_items')
                                    ->view('filament.forms.components.rekening-air-items-table'),
                            ];
                        }

                        // EDIT OPERATION: Use Repeater
                        return [
                            Forms\Components\Repeater::make('rekening_air_items')
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

                                        Forms\Components\Select::make('kode_proyek_id')
                                            ->label('Kode Proyek')
                                            ->options(KodeProyek::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->placeholder('Pilih Proyek'),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('position')
                                            ->label('D/K')
                                            ->options([
                                                'debit' => 'Debit',
                                                'kredit' => 'Kredit',
                                            ])
                                            ->required()
                                            ->default('debit'),

                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah (Rp)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('Rp')
                                            ->extraAttributes(['style' => 'text-align: right;']),
                                    ]),
                                ])
                                ->columns(1)
                                ->defaultItems(2)
                                ->addActionLabel('Tambah Baris Transaksi'),
                        ];
                    }),

                // === SECTION 3: RINGKASAN & VALIDASI ===
                Forms\Components\Section::make('Ringkasan & Validasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('summary_preview')
                                    ->label('Total Balance')
                                    ->content(function (Forms\Get $get) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
                                        $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');
                                        $balance = $totalDebit - $totalKredit;

                                        $text = "Debit: Rp " . number_format($totalDebit, 0, ',', '.') . " | Kredit: Rp " . number_format($totalKredit, 0, ',', '.');
                                        if (number_format($totalDebit, 2) === number_format($totalKredit, 2) && count($items) > 0) {
                                            return new \Illuminate\Support\HtmlString("<span class='text-success font-bold'>{$text} (✅ Balanced)</span>");
                                        } elseif (count($items) > 0) {
                                            $diff = number_format(abs($balance), 0, ',', '.');
                                            return new \Illuminate\Support\HtmlString("<span class='text-danger font-bold'>{$text} (⚠️ Selisih: Rp {$diff})</span>");
                                        }
                                        return $text;
                                    }),

                                Forms\Components\Placeholder::make('no_reff_info')
                                    ->label('Nomor Referensi')
                                    ->content('Status: Jurnal Rekening Air (Reff: 2)'),
                            ]),
                    ])
                    ->compact(),

                // === HIDDEN FIELDS ===
                Forms\Components\Hidden::make('no_reff')->default('2'),
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

                            $import = new JurnalRekeningAirImport();
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
                                    ->body("Berhasil mengimport {$import->getImportedCount()} data jurnal rekening air")
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
                            new JurnalRekeningAirTemplateExport(),
                            'template-jurnal-rekening-air.xlsx'
                        );
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('jurnalRekeningAir.bukti')
                    ->label('Bukti')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                Tables\Columns\TextColumn::make('jurnalRekeningAir.tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                Tables\Columns\TextColumn::make('rekening_info')
                    ->label('Kode & Rekening')
                    ->html()
                    ->searchable(['rekenings.nama_rek', 'nomor_bantus.nm_bantu'])
                    ->getStateUsing(function ($record) {
                        if (!$record->rekening) return '-';

                        $kel = str_pad($record->rekening->no_kel, 2, '0', STR_PAD_LEFT);
                        $rek = str_pad($record->rekening->no_rek, 4, '0', STR_PAD_LEFT);
                        $bantu = $record->nomorBantu ? str_pad($record->nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) : '00';
                        $kode = "<span class='font-mono text-xs text-gray-500'>{$kel}.{$rek}.{$bantu}</span>";

                        $namaRek = "<div class='font-medium'>" . \Illuminate\Support\Str::limit($record->rekening->nama_rek, 35) . "</div>";

                        $namaBantu = '';
                        if ($record->nomorBantu) {
                            $namaBantu = "<div class='text-xs text-gray-600 mt-0.5'>" . \Illuminate\Support\Str::limit($record->nomorBantu->nm_bantu, 40) . "</div>";
                        }

                        return $kode . ' ' . $namaRek . $namaBantu;
                    })
                    ->tooltip(fn($record) => $record->nomorBantu ? $record->rekening?->nama_rek . ' - ' . $record->nomorBantu->nm_bantu : $record->rekening?->nama_rek),

                Tables\Columns\TextColumn::make('kodeProyek.kode')
                    ->label('Proyek')
                    ->badge()
                    ->color('info')
                    ->default('-')
                    ->toggleable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                Tables\Columns\TextColumn::make('debit')
                    ->label('Debit')
                    ->getStateUsing(fn($record) => $record->position === 'debit' ? $record->jumlah : null)
                    ->money('IDR')
                    ->alignRight()
                    ->color('danger')
                    ->weight('medium')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                Tables\Columns\TextColumn::make('kredit')
                    ->label('Kredit')
                    ->getStateUsing(fn($record) => $record->position === 'kredit' ? $record->jumlah : null)
                    ->money('IDR')
                    ->alignRight()
                    ->color('success')
                    ->weight('medium')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                Tables\Columns\IconColumn::make('jurnalRekeningAir.is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('jurnalRekeningAir.is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('jurnalRekeningAir.no_reff')
                    ->label('No Reff')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->native(false),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->whereHas('jurnalRekeningAir', function ($q) use ($data) {
                            $q->when($data['dari_tanggal'], fn($query, $date) => $query->whereDate('tanggal', '>=', $date))
                                ->when($data['sampai_tanggal'], fn($query, $date) => $query->whereDate('tanggal', '<=', $date));
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\TernaryFilter::make('is_confirmed')
                    ->label('Status Konfirmasi')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Dikonfirmasi')
                    ->falseLabel('Belum Dikonfirmasi')
                    ->queries(
                        true: fn($query) => $query->whereHas('jurnalRekeningAir', fn($q) => $q->where('is_confirmed', true)),
                        false: fn($query) => $query->whereHas('jurnalRekeningAir', fn($q) => $q->where('is_confirmed', false)),
                    ),

                Tables\Filters\TernaryFilter::make('is_posted')
                    ->label('Status Posting')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Diposting')
                    ->falseLabel('Belum Diposting')
                    ->queries(
                        true: fn($query) => $query->whereHas('jurnalRekeningAir', fn($q) => $q->where('is_posted', true)),
                        false: fn($query) => $query->whereHas('jurnalRekeningAir', fn($q) => $q->where('is_posted', false)),
                    ),

                Tables\Filters\SelectFilter::make('position')
                    ->label('Posisi')
                    ->options([
                        'debit' => 'Debit',
                        'kredit' => 'Kredit',
                    ]),

                Tables\Filters\SelectFilter::make('rekening_id')
                    ->label('Rekening')
                    ->relationship('rekening', 'nama_rek')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('kode_proyek_id')
                    ->label('Kode Proyek')
                    ->relationship('kodeProyek', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->visible(fn($record) => !$record->jurnalRekeningAir->is_confirmed),

                    Tables\Actions\Action::make('confirm')
                        ->label('✓ Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->jurnalRekeningAir->confirm();
                            Notification::make()
                                ->title('Jurnal berhasil dikonfirmasi')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn($record) => !$record->jurnalRekeningAir->is_confirmed && auth()->user()->can('confirm', $record->jurnalRekeningAir)),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('↶ Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($record) {
                            $record->jurnalRekeningAir->unconfirm();
                            Notification::make()
                                ->title('Konfirmasi jurnal dibatalkan')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record->jurnalRekeningAir->is_confirmed && !$record->jurnalRekeningAir->is_posted && auth()->user()->can('unconfirm', $record->jurnalRekeningAir)),

                    Tables\Actions\Action::make('post_to_ledger')
                        ->label('Post ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record, \App\Services\JournalPostingService $service) {
                            try {
                                $service->post($record->jurnalRekeningAir);
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
                        ->visible(fn($record) => $record->jurnalRekeningAir->is_confirmed && !$record->jurnalRekeningAir->is_posted),

                    Tables\Actions\Action::make('exportPdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->url(fn($record) => route('jurnal-rekening-air.single-pdf', $record->id))
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->visible(fn($record) => !$record->jurnalRekeningAir->is_confirmed),
                ])
                    ->button()
                    ->label('Action')
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_confirm')
                        ->label('Konfirmasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $parentIds = $records->pluck('jurnal_rekening_air_id')->unique();
                            $journals = \App\Models\JurnalRekeningAir::whereIn('id', $parentIds)
                                ->where('is_confirmed', false)
                                ->get();

                            foreach ($journals as $journal) {
                                $journal->confirm();
                            }

                            Notification::make()
                                ->title("{$journals->count()} jurnal berhasil dikonfirmasi")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_post_to_ledger')
                        ->label('Post ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, \App\Services\JournalPostingService $service) {
                            $parentIds = $records->pluck('jurnal_rekening_air_id')->unique();
                            $journals = \App\Models\JurnalRekeningAir::whereIn('id', $parentIds)
                                ->where('is_confirmed', true)
                                ->where('is_posted', false)
                                ->get();

                            $success = 0;
                            $failed = 0;
                            foreach ($journals as $journal) {
                                try {
                                    $service->post($journal);
                                    $success++;
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title("Berhasil: {$success}, Gagal: {$failed}")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100]);
    }

    public static function getWidgets(): array
    {
        return [
            JurnalRekeningAirStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            // RelationManager tidak diperlukan karena kita sudah menampilkan per baris detail
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalRekeningAir::route('/'),
            'create' => Pages\CreateJurnalRekeningAir::route('/create'),
            'view' => Pages\ViewJurnalRekeningAir::route('/{record}'),
            'edit' => Pages\EditJurnalRekeningAir::route('/{record}/edit'),
        ];
    }
}
