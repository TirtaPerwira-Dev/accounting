<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;
use App\Filament\Widgets\JurnalBayarKasBankStatsWidget;
use App\Models\JurnalBayarKasBank;
use App\Models\Company;
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
    protected static ?string $model = JurnalBayarKasBank::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Jurnal Bayar Kas/Bank';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 4;

    protected static ?string $pluralModelLabel = 'Jurnal Bayar Kas/Bank';

    protected static ?string $slug = 'jurnal-bayar-kas-bank';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (!$user) {
            return '0';
        }

        $companyId = (int) ($user->company_id ?? 1);

        return (string) static::getModel()::query()
            ->where('company_id', $companyId)
            ->where('is_posted', false)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : 'success';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with([
            'kelompok',
            'rekening.kelompok',
            'nomorBantu',
            'kodeProyek',
            'createdBy'
        ]);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: Header Transaksi
                Forms\Components\Section::make('Informasi Pembayaran')
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
                                ->label('Kode Rekening Bank')
                                ->options(function () {
                                    return Rekening::with(['kelompok', 'nomorBantus'])
                                        ->whereHas('kelompok', fn($q) => $q->where('no_kel', '10'))
                                        ->where('no_rek', 'like', '1102%') // Bank only
                                        ->get()
                                        ->flatMap(function ($rekening) {
                                            // Jika ada nomor bantu, tampilkan dengan nomor bantu
                                            if ($rekening->nomorBantus->count() > 0) {
                                                return $rekening->nomorBantus->mapWithKeys(fn($nb) => [
                                                    $rekening->id . '|' . $nb->id => "{$rekening->no_rek} {$nb->no_bantu} - {$nb->nm_bantu}"
                                                ]);
                                            }
                                            // Jika tidak ada nomor bantu
                                            return [$rekening->id . '|0' => "{$rekening->no_rek} - {$rekening->nama_rek}"];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if (!$state) return;
                                    [$rekeningId, $nomorBantuId] = explode('|', $state);
                                    $rekening = Rekening::find($rekeningId);

                                    // Get nama bank (hanya nama, tanpa kode)
                                    $namaBank = $rekening?->nama_rek ?? '';
                                    if ($nomorBantuId > 0) {
                                        $nomorBantu = NomorBantu::find($nomorBantuId);
                                        if ($nomorBantu) {
                                            $namaBank = $nomorBantu->nm_bantu; // Hanya nama bantu
                                        }
                                    }

                                    $set('nama_bank', $namaBank);
                                    $set('rekening_id', $rekeningId);
                                    $set('nomor_bantu_id', $nomorBantuId > 0 ? $nomorBantuId : null);
                                }),
                        ]),

                        Forms\Components\Grid::make(4)->schema([
                            // Nama Bank (Auto-fill, Read-only)
                            Forms\Components\TextInput::make('nama_bank')
                                ->label('Nama Bank')
                                ->disabled()
                                ->dehydrated(false),

                            // No. Cek
                            Forms\Components\TextInput::make('no_cek')
                                ->label('No. Cek')
                                ->maxLength(255),

                            // Beban Bagian
                            Forms\Components\TextInput::make('beban_bagian')
                                ->label('Beban Bagian')
                                ->maxLength(255),

                            // Boleh dibayar kepada
                            Forms\Components\TextInput::make('dibayar_kepada')
                                ->label('Boleh dibayar kepada')
                                ->default(function (): ?string {
                                    return Company::first()?->getConfigValue('default_dibayar_kepada');
                                })
                                ->maxLength(255),
                        ]),

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
                            ->label('Lampiran PDF (Opsional)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('lampiran/jurnal-bayar-kas-bank')
                            ->disk('public')
                            ->helperText('Opsional. Upload file PDF sebagai lampiran jurnal.')
                            ->nullable(),

                        // Hidden fields for backend
                        Forms\Components\Hidden::make('no_reff')->default('4'),
                        Forms\Components\Hidden::make('rekening_id'),
                        Forms\Components\Hidden::make('nomor_bantu_id'),
                    ]),

                // SECTION 2: FORM TAMBAH ITEM PEMBAYARAN
                Forms\Components\Section::make('Tambah Item Pembayaran')
                    ->description('Isi form di bawah ini lalu klik "Tambah Item"')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
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
                                ->options([])
                                ->getSearchResultsUsing(function (string $search): array {
                                    return Rekening::query()
                                        ->with('kelompok')
                                        ->where(function ($query) use ($search) {
                                            $query->where('nama_rek', 'like', "%{$search}%")
                                                ->orWhere('no_rek', 'like', '%' . preg_replace('/\D/', '', $search) . '%');
                                        })
                                        ->orderBy('no_rek')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($rekening) => [
                                            $rekening->id => "{$rekening->no_rek} - {$rekening->nama_rek}"
                                        ])
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (!$value) {
                                        return null;
                                    }

                                    $rekening = Rekening::with('kelompok')->find($value);
                                    if (!$rekening) {
                                        return null;
                                    }

                                    return "{$rekening->no_rek} - {$rekening->nama_rek}";
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $set('temp_nomor_bantu_id', null);
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
                        ]),

                        // Jumlah dan Keterangan
                        Forms\Components\Grid::make(2)->schema([
                            // Jumlah
                            Forms\Components\TextInput::make('temp_jumlah')
                                ->label('Jumlah (Rp)')
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'style' => 'text-align: right;',
                                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");',
                                ])
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Keterangan
                            Forms\Components\Textarea::make('temp_keterangan')
                                ->label('Keterangan')
                                ->placeholder('Detail pembayaran...')
                                ->rows(2)
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
                                        'jumlah' => (float) preg_replace('/[^0-9]/', '', $get('temp_jumlah') ?? '0'),
                                        'keterangan' => $get('temp_keterangan'),
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

                                    $currentItems = $get('pembayaran_items') ?? [];
                                    $currentItems[] = array_merge($tempData, ['id' => count($currentItems) + 1]);
                                    $set('pembayaran_items', $currentItems);

                                    // Clear form
                                    $set('temp_kode_proyek_id', null);
                                    $set('temp_rekening_id', null);
                                    $set('temp_nomor_bantu_id', null);
                                    $set('temp_jumlah', '');
                                    $set('temp_keterangan', '');

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

                // SECTION 3: PREVIEW ITEMS
                Forms\Components\Section::make('Daftar Item Pembayaran')
                    ->description('Preview item yang telah ditambahkan')
                    ->schema([
                        Forms\Components\ViewField::make('pembayaran_items')
                            ->view('filament.forms.components.bayar-kas-bank-items-table'),

                        // Action untuk konfirmasi selesai menambah item
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('confirm_items_complete')
                                ->label('Konfirmasi Selesai Menambah Item')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !$get('items_completed') && !empty($get('pembayaran_items')))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $items = $get('pembayaran_items') ?? [];

                                    if (empty($items)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Tidak ada item!')
                                            ->body('Tambahkan minimal 1 item pembayaran terlebih dahulu.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $total = collect($items)->sum('jumlah');
                                    if ($total == 0) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Total tidak boleh 0!')
                                            ->body('Pastikan ada item dengan jumlah yang valid.')
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
                                ->modalHeading('Konfirmasi Item Selesai')
                                ->modalDescription('Apakah Anda yakin sudah selesai menambahkan semua item pembayaran?')
                                ->modalSubmitActionLabel('Ya, Selesai'),

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
                                    $count = count($get('pembayaran_items') ?? []);
                                    if ($count > 0) {
                                        $items = $get('pembayaran_items') ?? [];
                                        $total = collect($items)->sum('jumlah');
                                        return "📋 {$count} item ditambahkan (Total: Rp " . number_format($total, 0, ',', '.') . ") - Klik 'Konfirmasi Selesai' untuk melanjutkan";
                                    }
                                    return '📋 Belum ada item yang ditambahkan';
                                }
                            })
                            ->visible(fn(Forms\Get $get) => !empty($get('pembayaran_items')))
                            ->columnSpanFull(),

                        // Hidden field untuk status konfirmasi
                        Forms\Components\Hidden::make('items_completed')
                            ->default(false)
                            ->dehydrated(true),

                        // Hidden field untuk menyimpan array items
                        Forms\Components\Hidden::make('pembayaran_items')
                            ->dehydrated(true),
                    ])
                    ->visible(fn(Forms\Get $get) => !empty($get('pembayaran_items')))
                    ->collapsible(),

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
                Tables\Columns\TextColumn::make('no_voucher')
                    ->label('No Voucher')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_check')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bankNoCek')
                    ->label('Nama Bank/No Cek')
                    ->getStateUsing(fn($record) => $record->nama_bank_display . ' / ' . ($record->no_cek ?? '-'))
                    ->searchable(false)
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Nama Rekening')
                    ->limit(25)
                    ->searchable(),

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

                Tables\Columns\TextColumn::make('rp')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('no_reff')
                    ->label('No Reff')
                    ->searchable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->wrap(),
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
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()->visible(fn($record) => !$record->is_posted && !$record->is_confirmed && auth()->user()->can('postToLedger', $record)),

                    Tables\Actions\Action::make('confirm')
                        ->label('✓ Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(false)
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                        ->action(fn($record) => $record->confirm())
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
                        ->visible(false)
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Konfirmasi')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                        ->action(fn($record) => $record->unconfirm())
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
                        ->visible(fn($record) => auth()->user()->can('postToLedger', $record))
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
                        ->visible(fn($record) => !$record->is_posted && auth()->user()->can('postToLedger', $record)),

                    Tables\Actions\DeleteAction::make()->visible(fn($record) => !$record->is_posted && !$record->is_confirmed && auth()->user()->can('postToLedger', $record)),
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
                            $validRecords = $records->filter(fn($record) => !$record->is_posted && auth()->user()->can('postToLedger', $record));

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
                            foreach ($records as $record) {
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
                            foreach ($records as $record) {
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
            \App\Filament\Accounting\Resources\JurnalBayarKasBankResource\RelationManagers\DetailsRelationManager::class,
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
