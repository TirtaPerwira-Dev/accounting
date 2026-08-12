<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalRekeningAirResource\Pages;
use App\Filament\Widgets\JurnalRekeningAirStatsWidget;
use App\Models\JurnalRekeningAirDetail;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
        return (string) \App\Models\JurnalRekeningAir::where('is_posted', 0)->count();
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

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('total_item_input')
                                ->label('Total Item Input (Jumlah Item)')
                                ->default(0)
                                ->live()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'style' => 'text-align: right;',
                                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "");',
                                ]),

                            Forms\Components\TextInput::make('nominal_input')
                                ->label('Nominal Pembayaran (Rp)')
                                ->prefix('Rp')
                                ->default(0)
                                ->live()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'style' => 'text-align: right;',
                                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");',
                                ]),
                        ]),

                        Forms\Components\FileUpload::make('lampiran')
                            ->label('Lampiran (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('lampiran/jurnal-rekening-air')
                            ->disk('public')
                            ->helperText('Opsional. Upload file PDF sebagai lampiran jurnal.'),
                    ])
                    ->collapsible(),

                // === SECTION INPUT ITEM ===
                Forms\Components\Section::make('Input Item Transaksi')
                    ->description(function (Forms\Get $get) {
                        $itemsCompleted = $get('items_completed') ?? false;
                        if ($itemsCompleted) {
                            return '✅ Item sudah dikonfirmasi selesai - Form dinonaktifkan';
                        }
                        return 'Tambahkan item transaksi satu per satu';
                    })
                    ->schema([
                        Forms\Components\Grid::make(5)->schema([
                            // Kode Proyek
                            Forms\Components\Select::make('temp_kode_proyek_id')
                                ->label('Kode Proyek')
                                ->options(function () {
                                    return KodeProyek::query()
                                        ->select(['id', 'kode', 'name'])
                                        ->orderBy('kode')
                                        ->get()
                                        ->mapWithKeys(fn($proyek) => [
                                            $proyek->id => $proyek->kode . ' - ' . $proyek->name
                                        ]);
                                })
                                ->searchable()
                                ->placeholder('Pilih Proyek')
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Rekening
                            Forms\Components\Select::make('temp_rekening_id')
                                ->label('Rekening')
                                ->options(function () {
                                    return Rekening::with('kelompok')
                                        ->get()
                                        ->mapWithKeys(fn($rekening) => [
                                            $rekening->id => "{$rekening->no_rek} - {$rekening->nama_rek}"
                                        ]);
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $rekening = Rekening::find($state);
                                        if ($rekening) {
                                            $set('temp_position', $rekening->kode === 'K' ? 'kredit' : 'debit');
                                            $set('temp_nomor_bantu_id', null);
                                        }
                                    }
                                })
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Nomor Bantu
                            Forms\Components\Select::make('temp_nomor_bantu_id')
                                ->label('Nomor Bantu')
                                ->options(function (callable $get) {
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
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // D/K (Debit/Kredit)
                            Forms\Components\Select::make('temp_position')
                                ->label('D/K')
                                ->options([
                                    'debit' => 'Debit',
                                    'kredit' => 'Kredit',
                                ])
                                ->default('debit')
                                ->live()
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Jumlah
                            Forms\Components\TextInput::make('temp_jumlah')
                                ->label('Jumlah (Rp)')
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->numeric()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'style' => 'text-align: right;',
                                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, \'\').replace(/\B(?=(\d{3})+(?!\d))/g, \'.\');',
                                ])
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),
                        ]),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('add_item')
                                ->label('Tambah Item')
                                ->icon('heroicon-o-plus-circle')
                                ->color('warning')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !($get('items_completed') ?? false))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $tempData = [
                                        'kode_proyek_id' => $get('temp_kode_proyek_id'),
                                        'rekening_id' => $get('temp_rekening_id'),
                                        'nomor_bantu_id' => $get('temp_nomor_bantu_id'),
                                        'position' => $get('temp_position'),
                                        'jumlah' => (float) preg_replace('/[^0-9]/', '', $get('temp_jumlah') ?? '0'),
                                    ];

                                    // Validate required fields
                                    if (empty($tempData['rekening_id']) || empty($tempData['jumlah'])) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Data tidak lengkap!')
                                            ->body('Rekening dan Jumlah harus diisi.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $currentItems = $get('rekening_air_items') ?? [];
                                    $currentItems[] = array_merge($tempData, ['id' => count($currentItems) + 1]);
                                    $set('rekening_air_items', $currentItems);

                                    // Clear form
                                    $set('temp_kode_proyek_id', null);
                                    $set('temp_rekening_id', null);
                                    $set('temp_nomor_bantu_id', null);
                                    $set('temp_position', 'debit');
                                    $set('temp_jumlah', '');

                                    // Reset konfirmasi selesai karena ada item baru
                                    $set('items_completed', false);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Item berhasil ditambahkan!')
                                        ->success()
                                        ->send();
                                })
                                ->requiresConfirmation(false),
                        ])->alignment('center')->columnSpanFull(),

                        // Info saat form disabled
                        Forms\Components\Placeholder::make('form_disabled_info')
                            ->label('')
                            ->content('📝 **Form dinonaktifkan** - Item sudah dikonfirmasi selesai. Klik "Reset Konfirmasi" jika ingin menambah item lagi.')
                            ->visible(fn(Forms\Get $get) => $get('items_completed') ?? false)
                            ->columnSpanFull(),
                    ]),

                // === SECTION PREVIEW ITEMS ===
                Forms\Components\Section::make('Daftar Item Transaksi')
                    ->description('Preview item yang telah ditambahkan')
                    ->schema([
                        Forms\Components\ViewField::make('rekening_air_items')
                            ->view('filament.forms.components.rekening-air-items-table'),

                        // Action untuk konfirmasi selesai menambah item
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('confirm_items_complete')
                                ->label('Konfirmasi')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !$get('items_completed') && !empty($get('rekening_air_items')))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $items = $get('rekening_air_items') ?? [];

                                    if (empty($items)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Tidak ada item!')
                                            ->body('Tambahkan minimal 1 item transaksi terlebih dahulu.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Validasi balance
                                    $totalDebit = collect($items)
                                        ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'debit')
                                        ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
                                    $totalKredit = collect($items)
                                        ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'kredit')
                                        ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

                                    if (abs($totalDebit - $totalKredit) > 0.01 || $totalDebit <= 0 || $totalKredit <= 0) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Jurnal Tidak Balance!')
                                            ->body("Total Debit: Rp " . number_format($totalDebit, 0, ',', '.') .
                                                " | Total Kredit: Rp " . number_format($totalKredit, 0, ',', '.'))
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $set('items_completed', true);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Item dikonfirmasi!')
                                        ->body('Silakan klik tombol "Buat" untuk menyimpan jurnal.')
                                        ->success()
                                        ->send();
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Konfirmasi Item Transaksi')
                                ->modalDescription('Apakah Anda yakin data item transaksi sudah benar? Setelah dikonfirmasi, item tidak bisa diedit atau dihapus.')
                                ->modalSubmitActionLabel('Ya, Konfirmasi'),

                            Forms\Components\Actions\Action::make('reset_items_confirmation')
                                ->label('Reset Konfirmasi')
                                ->icon('heroicon-o-arrow-path')
                                ->color('warning')
                                ->size('md')
                                ->visible(fn(Forms\Get $get) => $get('items_completed'))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $set('items_completed', false);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Konfirmasi direset')
                                        ->body('Anda dapat menambah item lagi atau konfirmasi ulang.')
                                        ->info()
                                        ->send();
                                })
                        ])->alignment('center')->columnSpanFull(),

                        // Status konfirmasi
                        Forms\Components\Placeholder::make('items_status')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                if ($get('items_completed')) {
                                    return '✅ **Item dikonfirmasi selesai** - Siap untuk disimpan';
                                } else {
                                    $count = count($get('rekening_air_items') ?? []);
                                    if ($count > 0) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalDebit = collect($items)
                                            ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'debit')
                                            ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
                                        $totalKredit = collect($items)
                                            ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'kredit')
                                            ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
                                        $balance = abs($totalDebit - $totalKredit) <= 0.01 && $totalDebit > 0 ? '✅' : '⚠️';
                                        return "{$balance} {$count} item ditambahkan - Klik 'Konfirmasi' untuk melanjutkan";
                                    }
                                    return '📋 Belum ada item yang ditambahkan';
                                }
                            })
                            ->visible(fn(Forms\Get $get) => !empty($get('rekening_air_items')))
                            ->columnSpanFull(),

                        // Hidden field untuk status konfirmasi
                        Forms\Components\Hidden::make('items_completed')
                            ->default(false)
                            ->dehydrated(true),
                    ])
                    ->visible(fn(Forms\Get $get) => !empty($get('rekening_air_items')))
                    ->collapsible(),

                // === RINGKASAN ===
                Forms\Components\Section::make('Ringkasan Transaksi')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('total_debit')
                                    ->label('Total Debit')
                                    ->content(function (callable $get) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalDebit = collect($items)
                                            ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'debit')
                                            ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
                                        return 'Rp ' . number_format($totalDebit, 0, ',', '.');
                                    }),

                                Forms\Components\Placeholder::make('total_kredit')
                                    ->label('Total Kredit')
                                    ->content(function (callable $get) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalKredit = collect($items)
                                            ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'kredit')
                                            ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
                                        return 'Rp ' . number_format($totalKredit, 0, ',', '.');
                                    }),

                                Forms\Components\Placeholder::make('status_balance')
                                    ->label('⚖️ Status Balance')
                                    ->content(function (callable $get) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalDebit = collect($items)
                                            ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'debit')
                                            ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));
                                        $totalKredit = collect($items)
                                            ->filter(fn($item) => strtolower(trim((string) ($item['position'] ?? ''))) === 'kredit')
                                            ->sum(fn($item) => (float) ($item['jumlah'] ?? 0));

                                        $isBalance = abs($totalDebit - $totalKredit) <= 0.01 && $totalDebit > 0;
                                        return $isBalance ? '✅ Balance' : '⚠️ Tidak Balance';
                                    }),
                            ]),

                        Forms\Components\Hidden::make('rp'),
                    ])
                    ->visible(fn(Forms\Get $get) => !empty($get('rekening_air_items'))),

                Forms\Components\Section::make('Nomor Referensi')
                    ->schema([
                        Forms\Components\Placeholder::make('no_reff_preview')
                            ->label('Nomor Referensi')
                            ->content('Nomor Reff Jurnal Rekening Air adalah = 2')
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(),

                // === HIDDEN FIELDS ===
                Forms\Components\Hidden::make('no_reff')->default('2'),
                Forms\Components\Hidden::make('company_id')->default(1),
                Forms\Components\Hidden::make('created_by')->default(fn() => Auth::id()),
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
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

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
                        ->icon('heroicon-o-eye')
                        ->color('info'),

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
                        ->visible(false),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('↶ Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->jurnalRekeningAir->unconfirm();
                            Notification::make()
                                ->title('Konfirmasi jurnal dibatalkan')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(false),

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
                        ->visible(fn($record) => !$record->jurnalRekeningAir->is_posted),

                    Tables\Actions\Action::make('exportPdf')
                        ->label('Export PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->visible(fn($record) => Auth::check() ? Gate::forUser(Auth::user())->allows('postToLedger', $record->jurnalRekeningAir) : false)
                        ->url(fn($record) => route('jurnal-rekening-air.single-pdf', $record->id))
                        ->openUrlInNewTab(),
                ])
                    ->button()
                    ->label('Action')
                    ->color('primary')
                    ->icon('heroicon-o-ellipsis-vertical'),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_confirm')
                        ->label('Konfirmasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(false)
                        ->action(function (Collection $records) {
                            $parentIds = $records->pluck('jurnal_rekening_air_id')->unique();
                            $journals = \App\Models\JurnalRekeningAir::whereIn('id', $parentIds)
                                ->where('is_confirmed', false)
                                ->get();

                            foreach ($journals as $journal) {
                                if (Auth::check() && Gate::forUser(Auth::user())->allows('confirm', $journal)) {
                                    $journal->confirm();
                                }
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
                                ->where('is_posted', false)
                                ->get()
                                ->filter(fn($journal) => Auth::check() && Gate::forUser(Auth::user())->allows('postToLedger', $journal));

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
