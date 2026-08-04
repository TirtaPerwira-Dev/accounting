<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;
use App\Filament\Widgets\JurnalMemorialStatsWidget;
use App\Models\JurnalMemorialDetail;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Imports\JurnalMemorialImport;
use App\Exports\JurnalMemorialTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class JurnalMemorialResource extends Resource
{
    protected static ?string $model = JurnalMemorialDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Jurnal Memorial';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralModelLabel = 'Jurnal Memorial';

    protected static ?string $slug = 'jurnal-memorial';

    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Models\JurnalMemorial::where('is_posted', 0)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : 'success';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with([
            'jurnalMemorial',
            'kelompok',
            'rekening.kelompok',
            'nomorBantu',
            'kodeProyek',
        ]);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: No Bukti dan Tanggal
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('bukti')
                                ->label('No. Bukti')
                                ->maxLength(255)
                                ->required(),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required(),
                        ]),

                        Forms\Components\Hidden::make('no_reff'),
                    ]),

                // SECTION 2: CARI DATA SUMBER
                Forms\Components\Section::make('Cari Data Memorial')
                    ->description('Cari data memorial terlebih dahulu, lalu pilih item untuk dimuat ke form edit debit/kredit.')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('search_memorial_item')
                                ->label('Cari Bukti / Rekening / Nomor Bantu')
                                ->placeholder('Contoh: MEM-001, 1102, bank, 020')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Forms\Set $set) => $set('selected_memorial_source', null))
                                ->dehydrated(false),

                            Forms\Components\Select::make('selected_memorial_source')
                                ->label('Hasil Pencarian Item Sumber')
                                ->placeholder('Pilih item sumber...')
                                ->searchable()
                                ->options(function (Forms\Get $get): array {
                                    $keyword = trim((string) ($get('search_memorial_item') ?? ''));
                                    if ($keyword === '') {
                                        return [];
                                    }

                                    return \App\Models\JurnalMemorialDetail::query()
                                        ->with(['jurnalMemorial', 'rekening', 'nomorBantu'])
                                        ->where(function ($query) use ($keyword) {
                                            $query->where('bukti', 'like', "%{$keyword}%")
                                                ->orWhere('keterangan', 'like', "%{$keyword}%")
                                                ->orWhereHas('rekening', function ($rekeningQuery) use ($keyword) {
                                                    $rekeningQuery->where('no_rek', 'like', "%{$keyword}%")
                                                        ->orWhere('nama_rek', 'like', "%{$keyword}%");
                                                })
                                                ->orWhereHas('nomorBantu', function ($nbQuery) use ($keyword) {
                                                    $nbQuery->where('no_bantu', 'like', "%{$keyword}%")
                                                        ->orWhere('nm_bantu', 'like', "%{$keyword}%");
                                                });
                                        })
                                        ->latest('id')
                                        ->limit(100)
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            $noRek = str_pad((string) ($item->rekening?->no_rek ?? ''), 4, '0', STR_PAD_LEFT);
                                            $noBantu = $item->nomorBantu ? str_pad((string) $item->nomorBantu->no_bantu, 3, '0', STR_PAD_LEFT) : '---';
                                            $posisi = strtoupper((string) $item->posisi);
                                            $jumlah = number_format((float) $item->jumlah, 0, ',', '.');
                                            $bukti = $item->bukti ?: ($item->jurnalMemorial?->bukti ?? '-');

                                            return [
                                                $item->id => "{$bukti} | {$noRek}-{$noBantu} | {$posisi} | Rp {$jumlah}",
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state): void {
                                    if (!$state) {
                                        return;
                                    }

                                    $source = \App\Models\JurnalMemorialDetail::find($state);
                                    if (!$source) {
                                        return;
                                    }

                                    $set('temp_rekening', $source->rekening_id);
                                    $set('temp_nomor_bantu', $source->nomor_bantu_id);
                                    $set('temp_kode_proyek', $source->kode_proyek_id);
                                    $set('temp_position', strtoupper((string) $source->posisi) === 'K' ? 'kredit' : 'debit');
                                    $set('temp_jumlah', number_format((float) $source->jumlah, 0, ',', '.'));
                                    $set('temp_keterangan', $source->keterangan);
                                })
                                ->dehydrated(false),
                        ]),
                    ]),

                // SECTION 3: FORM TAMBAH ITEM MEMORIAL
                Forms\Components\Section::make('Edit Data dan Debit/Kredit')
                    ->description('Setelah memilih data sumber, sesuaikan akun, nominal, dan posisi debit/kredit lalu tambahkan item.')
                    ->schema([
                        // Form Input untuk menambah item
                        Forms\Components\Grid::make(5)->schema([
                            // Nama Rekening
                            Forms\Components\Select::make('temp_rekening')
                                ->label('Nama Rekening')
                                ->options(function () {
                                    return \App\Models\Rekening::with('kelompok')
                                        ->get()
                                        ->mapWithKeys(fn($r) => [
                                            $r->id => "[{$r->kelompok->no_kel}-{$r->no_rek}] {$r->nama_rek}"
                                        ]);
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    $set('temp_nomor_bantu', null);
                                    if ($state) {
                                        $rekening = \App\Models\Rekening::find($state);
                                        if ($rekening) {
                                            $set('temp_position', $rekening->kode === 'K' ? 'kredit' : 'debit');
                                        }
                                    }
                                })
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Kode Proyek
                            Forms\Components\Select::make('temp_kode_proyek')
                                ->label('Kode Proyek')
                                ->options(function () {
                                    return KodeProyek::query()
                                        ->select(['id', 'kode', 'name'])
                                        ->orderBy('kode')
                                        ->get()
                                        ->mapWithKeys(fn($proyek) => [
                                            $proyek->id => $proyek->kode . ' - ' . $proyek->name,
                                        ]);
                                })
                                ->searchable()
                                ->placeholder('Pilih Proyek')
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Nomor Bantu / Rekening
                            Forms\Components\Select::make('temp_nomor_bantu')
                                ->label('Rekening (No. Bantu)')
                                ->options(function (Forms\Get $get) {
                                    if (!$get('temp_rekening')) return [];
                                    return NomorBantu::where('rekening_id', $get('temp_rekening'))
                                        ->get()
                                        ->mapWithKeys(fn($nb) => [$nb->id => "[{$nb->no_bantu}] {$nb->nm_bantu}"]);
                                })
                                ->searchable()
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
                                ->label('Jumlah')
                                ->prefix('Rp')
                                ->default(0)
                                ->live()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'style' => 'text-align: right;',
                                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");',
                                ])
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),
                        ]),

                        // Keterangan
                        Forms\Components\Textarea::make('temp_keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                            ->dehydrated(false),

                        // Tombol Tambah Item
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('add_item')
                                ->label('Tambah Item')
                                ->icon('heroicon-o-plus-circle')
                                ->color('warning')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !($get('items_completed') ?? false))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $tempRekening = $get('temp_rekening');
                                    $tempNomorBantu = $get('temp_nomor_bantu');
                                    $tempKodeProyek = $get('temp_kode_proyek');
                                    $tempPosition = $get('temp_position') ?? 'debit';
                                    $tempJumlah = (float) preg_replace('/[^0-9]/', '', (string) ($get('temp_jumlah') ?? '0'));
                                    $tempKeterangan = $get('temp_keterangan');

                                    if (!$tempRekening || !$tempJumlah || $tempJumlah <= 0) {
                                        Notification::make()
                                            ->title('Validasi Gagal')
                                            ->body('Rekening dan Jumlah harus diisi dengan benar')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $currentItems = $get('detail_rekening') ?? [];
                                    $currentItems[] = [
                                        'rekening' => $tempRekening,
                                        'nomor_bantu' => $tempNomorBantu,
                                        'kode_proyek' => $tempKodeProyek,
                                        'position' => $tempPosition,
                                        'jumlah' => $tempJumlah,
                                        'keterangan' => $tempKeterangan,
                                    ];

                                    $set('detail_rekening', $currentItems);

                                    // Reset temp fields
                                    $set('temp_rekening', null);
                                    $set('temp_nomor_bantu', null);
                                    $set('temp_kode_proyek', null);
                                    $set('temp_position', 'debit');
                                    $set('temp_jumlah', 0);
                                    $set('temp_keterangan', null);

                                    // Reset konfirmasi
                                    $set('items_completed', false);

                                    Notification::make()
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

                // SECTION 3: PREVIEW ITEMS
                Forms\Components\Section::make('Daftar Item Jurnal Memorial')
                    ->description('Preview item yang telah ditambahkan')
                    ->schema([

                        // Display Items Table
                        Forms\Components\ViewField::make('detail_rekening')
                            ->view('filament.forms.components.memorial-items-table'),

                        // Action untuk konfirmasi selesai menambah item
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('confirm_items_complete')
                                ->label('Konfirmasi Selesai Menambah Item')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !$get('items_completed') && !empty($get('detail_rekening')))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $items = $get('detail_rekening') ?? [];

                                    if (empty($items)) {
                                        Notification::make()
                                            ->title('Tidak ada item!')
                                            ->body('Tambahkan minimal 1 item terlebih dahulu.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
                                    $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');

                                    if ($totalDebit != $totalKredit) {
                                        Notification::make()
                                            ->title('Balance tidak valid!')
                                            ->body('Total Debit (Rp ' . number_format($totalDebit, 0, ',', '.') . ') harus sama dengan Total Kredit (Rp ' . number_format($totalKredit, 0, ',', '.') . ')')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $set('items_completed', true);

                                    Notification::make()
                                        ->title('Item dikonfirmasi!')
                                        ->body('Silakan klik tombol "Buat" untuk menyimpan jurnal.')
                                        ->success()
                                        ->send();
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Konfirmasi Item Selesai')
                                ->modalDescription('Apakah Anda yakin sudah selesai menambahkan semua item dan balance sudah benar?')
                                ->modalSubmitActionLabel('Ya, Selesai'),

                            Forms\Components\Actions\Action::make('reset_items_confirmation')
                                ->label('Reset Konfirmasi')
                                ->icon('heroicon-o-arrow-path')
                                ->color('warning')
                                ->size('md')
                                ->visible(fn(Forms\Get $get) => $get('items_completed'))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $set('items_completed', false);

                                    Notification::make()
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
                                    $count = count($get('detail_rekening') ?? []);
                                    if ($count > 0) {
                                        $items = $get('detail_rekening') ?? [];
                                        $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
                                        $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');
                                        $balance = $totalDebit - $totalKredit;
                                        $balanceText = $balance == 0 ? '✅ Balance' : '⚠️ Selisih: Rp ' . number_format(abs($balance), 0, ',', '.');
                                        return "📋 {$count} item ditambahkan | Debit: Rp " . number_format($totalDebit, 0, ',', '.') . " | Kredit: Rp " . number_format($totalKredit, 0, ',', '.') . " | {$balanceText}";
                                    }
                                    return '📋 Belum ada item yang ditambahkan';
                                }
                            })
                            ->visible(fn(Forms\Get $get) => !empty($get('detail_rekening')))
                            ->columnSpanFull(),

                        // Hidden field untuk status konfirmasi
                        Forms\Components\Hidden::make('items_completed')
                            ->default(false)
                            ->dehydrated(true),

                        // Hidden field untuk menyimpan array items
                        Forms\Components\Hidden::make('detail_rekening')
                            ->dehydrated(true),
                    ])
                    ->visible(fn(Forms\Get $get) => !empty($get('detail_rekening')))
                    ->collapsible(),

                // SECTION 4: Nomor Referensi
                Forms\Components\Section::make('Nomor Referensi')
                    ->schema([
                        Forms\Components\Placeholder::make('no_reff_preview')
                            ->label('Nomor Referensi')
                            ->content('Nomor Reff Jurnal Memorial adalah = 6')
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(),

                // Hidden Fields
                Forms\Components\Hidden::make('no_reff')->default('6'),
                Forms\Components\Hidden::make('company_id')->default(1),
                Forms\Components\Hidden::make('created_by')->default(fn() => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jurnalMemorial.bukti')
                    ->label('No Bukti')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jurnalMemorial.tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Nama Rekening')
                    ->limit(30)
                    ->wrap(),

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
                    })
                    ->searchable(false)
                    ->wrap(),

                Tables\Columns\TextColumn::make('posisi')
                    ->label('D/K')
                    ->badge()
                    ->color(fn($state) => $state === 'D' ? 'info' : ($state === 'K' ? 'success' : 'gray')),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\IconColumn::make('jurnalMemorial.is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('jurnalMemorial.is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('jurnalMemorial.no_reff')
                    ->label('No Reff')
                    ->searchable(),
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

                            $import = new JurnalMemorialImport();
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
                                    ->body("Berhasil mengimport {$import->getImportedCount()} data jurnal memorial")
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
                    ->action(fn() => Excel::download(new JurnalMemorialTemplateExport(), 'template-jurnal-memorial.xlsx')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_confirmed')
                    ->label('Status')
                    ->options([1 => 'Dikonfirmasi', 0 => 'Pending']),

                Tables\Filters\TernaryFilter::make('is_posted')
                    ->label('Status Posting')
                    ->placeholder('Semua Status')
                    ->trueLabel('Sudah Diposting')
                    ->falseLabel('Belum Diposting')
                    ->queries(
                        true: fn($query) => $query->whereHas('jurnalMemorial', fn($q) => $q->where('is_posted', true)),
                        false: fn($query) => $query->whereHas('jurnalMemorial', fn($q) => $q->where('is_posted', false)),
                        blank: fn($query) => $query,
                    ),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(
                        fn($query, $data) => $query->whereHas('jurnalMemorial', function ($q) use ($data) {
                            $q->when($data['from'] ?? null, fn($qq, $d) => $qq->whereDate('tanggal', '>=', $d))
                                ->when($data['until'] ?? null, fn($qq, $d) => $qq->whereDate('tanggal', '<=', $d));
                        })
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()->visible(fn($record) => $record->jurnalMemorial && !$record->jurnalMemorial->is_posted && !$record->jurnalMemorial->is_confirmed && auth()->user()->can('postToLedger', $record->jurnalMemorial)),

                    Tables\Actions\Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(false)
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                        ->action(fn($record) => $record->jurnalMemorial->confirm())
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Jurnal berhasil dikonfirmasi')
                        ),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->visible(false)
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Konfirmasi')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                        ->action(fn($record) => $record->jurnalMemorial->unconfirm())
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Konfirmasi berhasil dibatalkan')
                        ),

                    Tables\Actions\Action::make('exportPdf')
                        ->label('PDF')
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
                                return;
                            }

                            $header->load(['rekening.kelompok', 'nomorBantu', 'kodeProyek', 'details.rekening.kelompok', 'details.nomorBantu']);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-memorial-single', [
                                'jurnal' => $header,
                                'generatedAt' => now()->format('d M Y H:i'),
                            ]);

                            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->bukti ?? $header->id);

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                'jurnal-memorial-' . $safeFilename . '.pdf'
                            );
                        }),

                    Tables\Actions\Action::make('post_to_ledger')
                        ->label('Post ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record, \App\Services\JournalPostingService $service) {
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

                    Tables\Actions\DeleteAction::make()->visible(fn($record) => $record->jurnalMemorial && !$record->jurnalMemorial->is_posted && !$record->jurnalMemorial->is_confirmed && auth()->user()->can('postToLedger', $record->jurnalMemorial)),
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
                            $headers = $records
                                ->pluck('jurnalMemorial')
                                ->filter()
                                ->unique('id')
                                ->values()
                                ->filter(fn($record) => auth()->user()->can('postToLedger', $record));

                            $validRecords = $headers->filter(fn($record) => !$record->is_posted);

                            if ($validRecords->isEmpty()) {
                                Notification::make()
                                    ->title('Tidak ada jurnal yang valid untuk diposting')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $count = $service->postBulk($validRecords);

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
                            foreach ($records->pluck('jurnalMemorial')->filter()->unique('id') as $record) {
                                if (!$record->is_confirmed && auth()->user()->can('confirm', $record)) {
                                    $record->confirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Jurnal terpilih berhasil dikonfirmasi')
                        ->visible(false),

                    Tables\Actions\BulkAction::make('unconfirm_selected')
                        ->label('↶ Batal Konfirmasi Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records->pluck('jurnalMemorial')->filter()->unique('id') as $record) {
                                if ($record->is_confirmed && auth()->user()->can('unconfirm', $record)) {
                                    $record->unconfirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Konfirmasi dibatalkan')
                        ->visible(false),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Accounting\Resources\JurnalMemorialResource\RelationManagers\DetailsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            JurnalMemorialStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalMemorials::route('/'),
            'create' => Pages\CreateJurnalMemorial::route('/create'),
            'view' => Pages\ViewJurnalMemorial::route('/{record}'),
            'edit' => Pages\EditJurnalMemorial::route('/{record}/edit'),
        ];
    }
}
