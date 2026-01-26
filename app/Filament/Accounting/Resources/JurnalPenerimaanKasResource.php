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

    // Eager load relationships for better performance
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'jurnalPenerimaanKas.kasBank',
                'jurnalPenerimaanKas.confirmedBy',
                'jurnalPenerimaanKas.createdBy',
                'kelompok',
                'rekening.kelompok',
                'nomorBantu',
                'kodeProyek'
                // Note: Detail tables don't have created_by/confirmed_by columns
            ]);
    }

    // Authorization helpers
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->check();
    }

    public static function canEdit($record): bool
    {
        // Check if parent jurnal is confirmed (if exists)
        if ($record && $record->jurnalPenerimaanKas) {
            // Add your confirmation check here if needed
            // For now, allow edit
        }
        return auth()->check();
    }

    public static function canDelete($record): bool
    {
        // Check if parent jurnal is confirmed (if exists)
        if ($record && $record->jurnalPenerimaanKas) {
            // Add your confirmation check here if needed
            // For now, allow delete
        }
        return auth()->check();
    }

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
                                // Kelompok (Hidden) - Auto-set to Aktiva Lancar
                                Forms\Components\Hidden::make('kelompok_id')
                                    ->default(function () {
                                        return Kelompok::where('no_kel', '10')->first()?->id;
                                    })
                                    ->dehydrated(),

                                // Rekening
                                Forms\Components\Select::make('rekening_id')
                                    ->label('Rekening')
                                    ->options(function (callable $get) {
                                        $kelompokId = $get('kelompok_id');
                                        if (!$kelompokId) return [];

                                        return Rekening::where('kelompok_id', $kelompokId)
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
                                    ->live()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('kas_bank_id', null);
                                    })
                                    ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                    ->dehydrated(),

                                // Nomor Bantu
                                Forms\Components\Select::make('kas_bank_id')
                                    ->label('Nomor Bantu')
                                    ->options(function (callable $get) {
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
                                    ->placeholder('Pilih Nomor Bantu')
                                    ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                    ->dehydrated(),

                                // Tanggal
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->helperText('Tanggal penerimaan kas/bank')
                                    ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                    ->dehydrated(),
                            ]),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan Umum')
                            ->placeholder('Contoh: Penerimaan dari penjualan, Penerimaan bunga bank, dll')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                            ->dehydrated(),
                    ])
                    ->collapsible(),

                // === SECTION 2: FORM TAMBAH ITEM ===
                Forms\Components\Section::make('Tambah Sumber Penerimaan')
                    ->description('Isi form di bawah ini lalu klik "Tambah Item"')
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            // Nomor Bukti
                            Forms\Components\TextInput::make('temp_nomor_bukti')
                                ->label('Nomor Bukti')
                                ->maxLength(50)
                                ->placeholder('Contoh: BKM-001, KAS-001')
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Kode Proyek
                            Forms\Components\Select::make('temp_kode_proyek')
                                ->label('Kode Proyek')
                                ->options(function () {
                                    return KodeProyek::all()
                                        ->pluck('name', 'id')
                                        ->mapWithKeys(fn($nama, $id) => [
                                            $id => KodeProyek::find($id)->kode . ' - ' . $nama
                                        ]);
                                })
                                ->searchable()
                                ->placeholder('Pilih Proyek')
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Rekening (Sumber Kredit)
                            Forms\Components\Select::make('temp_rekening')
                                ->label('Rekening (Sumber)')
                                ->options(function () {
                                    return Rekening::with('kelompok')
                                        ->get()
                                        ->mapWithKeys(fn($rekening) => [
                                            $rekening->id => "{$rekening->kelompok->no_kel}-{$rekening->no_rek} - {$rekening->nama_rek}"
                                        ]);
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $set('temp_nomor_bantu', null);
                                    }
                                })
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            // Nomor Bantu
                            Forms\Components\Select::make('temp_nomor_bantu')
                                ->label('Nomor Bantu')
                                ->options(function (callable $get) {
                                    $rekeningId = $get('temp_rekening');
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

                        // Keterangan Item (Full Width)
                        Forms\Components\Textarea::make('temp_keterangan_item')
                            ->label('Keterangan Item')
                            ->placeholder('Detail sumber penerimaan ini...')
                            ->rows(2)
                            ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('add_item')
                                ->label('Tambah Item')
                                ->icon('heroicon-o-plus-circle')
                                ->color('warning')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !($get('items_completed') ?? false))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $tempData = [
                                        'nomor_bukti' => $get('temp_nomor_bukti'),
                                        'kode_proyek' => $get('temp_kode_proyek'),
                                        'rekening' => $get('temp_rekening'),
                                        'nomor_bantu' => $get('temp_nomor_bantu'),
                                        'jumlah' => (float) preg_replace('/[^0-9]/', '', $get('temp_jumlah') ?? '0'),
                                        'keterangan_item' => $get('temp_keterangan_item'),
                                    ];

                                    // Validate required fields
                                    if (empty($tempData['rekening']) || empty($tempData['jumlah'])) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Data tidak lengkap!')
                                            ->body('Rekening dan Jumlah harus diisi.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $currentItems = $get('detail_penerimaan') ?? [];
                                    $currentItems[] = array_merge($tempData, ['id' => count($currentItems) + 1]);
                                    $set('detail_penerimaan', $currentItems);

                                    // Clear form
                                    $set('temp_nomor_bukti', '');
                                    $set('temp_kode_proyek', null);
                                    $set('temp_rekening', null);
                                    $set('temp_nomor_bantu', null);
                                    $set('temp_jumlah', '');
                                    $set('temp_keterangan_item', '');

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
                Forms\Components\Section::make('Daftar Item Sumber Penerimaan')
                    ->description('Preview item yang telah ditambahkan')
                    ->schema([
                        Forms\Components\ViewField::make('detail_penerimaan')
                            ->view('filament.forms.components.penerimaan-kas-items-table'),

                        // Action untuk konfirmasi selesai menambah item
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('confirm_items_complete')
                                ->label('Konfirmasi Selesai Menambah Item')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !$get('items_completed') && !empty($get('detail_penerimaan')))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $items = $get('detail_penerimaan') ?? [];

                                    if (empty($items)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Tidak ada item!')
                                            ->body('Tambahkan minimal 1 item sumber penerimaan terlebih dahulu.')
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
                                ->modalDescription('Apakah Anda yakin sudah selesai menambahkan semua item sumber penerimaan?')
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
                                    $count = count($get('detail_penerimaan') ?? []);
                                    if ($count > 0) {
                                        $items = $get('detail_penerimaan') ?? [];
                                        $total = collect($items)->sum('jumlah');
                                        return "📋 {$count} item ditambahkan (Total: Rp " . number_format($total, 0, ',', '.') . ") - Klik 'Konfirmasi Selesai' untuk melanjutkan";
                                    }
                                    return '📋 Belum ada item yang ditambahkan';
                                }
                            })
                            ->visible(fn(Forms\Get $get) => !empty($get('detail_penerimaan')))
                            ->columnSpanFull(),

                        // Hidden field untuk status konfirmasi
                        Forms\Components\Hidden::make('items_completed')
                            ->default(false)
                            ->dehydrated(true),

                        // Hidden field untuk menyimpan array items
                        Forms\Components\Hidden::make('detail_penerimaan')
                            ->dehydrated(true),
                    ])
                    ->visible(fn(Forms\Get $get) => !empty($get('detail_penerimaan')))
                    ->collapsible(),

                // === RINGKASAN ===
                Forms\Components\Section::make('Ringkasan Transaksi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('total_amount')
                                    ->label('Total Penerimaan')
                                    ->content(function (callable $get) {
                                        $details = $get('detail_penerimaan') ?? [];
                                        $total = collect($details)->sum('jumlah');
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    }),

                                Forms\Components\Placeholder::make('status_balance')
                                    ->label('⚖️ Status')
                                    ->content(function (callable $get) {
                                        $details = $get('detail_penerimaan') ?? [];
                                        $total = collect($details)->sum('jumlah');
                                        $isBalance = $total > 0;
                                        return $isBalance ? '✅ Valid' : '⚠️ Belum ada item';
                                    }),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(),

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

                // === HIDDEN FIELDS ===
                Forms\Components\Hidden::make('reff')->default('3'),
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

                Tables\Columns\IconColumn::make('jurnalPenerimaanKas.is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('jurnalPenerimaanKas.reff')
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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('confirm')
                        ->label('✓ Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->jurnalPenerimaanKas->confirm();
                            Notification::make()
                                ->title('Jurnal berhasil dikonfirmasi')
                                ->body("No. Reff: {$record->jurnalPenerimaanKas->reff}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription(fn($record) => "Apakah Anda yakin ingin mengkonfirmasi jurnal {$record->jurnalPenerimaanKas->reff}?")
                        ->visible(fn($record) => !$record->jurnalPenerimaanKas->is_confirmed)
                        ->hidden(fn() => auth()->user()->hasRole('staff')),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('↶ Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->jurnalPenerimaanKas->unconfirm();
                            Notification::make()
                                ->title('Konfirmasi jurnal dibatalkan')
                                ->body("No. Reff: {$record->jurnalPenerimaanKas->reff}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Batal Konfirmasi Jurnal')
                        ->modalDescription(fn($record) => "Apakah Anda yakin ingin membatalkan konfirmasi jurnal {$record->jurnalPenerimaanKas->reff}?")
                        ->visible(fn($record) => $record->jurnalPenerimaanKas->is_confirmed)
                        ->hidden(fn() => auth()->user()->hasRole('staff')),

                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus Item')
                        ->modalHeading('Hapus Item Transaksi')
                        ->modalDescription(fn($record) => "Item ini akan dihapus dari jurnal {$record->jurnalPenerimaanKas->reff}")
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
            \App\Filament\Accounting\Resources\JurnalPenerimaanKasResource\RelationManagers\DetailsRelationManager::class,
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
