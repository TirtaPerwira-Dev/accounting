<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalRekeningAirResource\Pages;
use App\Filament\Widgets\JurnalRekeningAirStatsWidget;
use App\Models\JurnalRekeningAir;
use App\Models\JurnalRekeningAirDetail;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Models\Company;
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
use Illuminate\Database\Eloquent\Model;

class JurnalRekeningAirResource extends Resource
{
    protected static ?string $model = JurnalRekeningAirDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Jurnal Rekening Air';

    protected static ?string $navigationGroup = 'Jurnal Transaksi';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralModelLabel = 'Jurnal Rekening Air & Non Air';

    protected static ?string $slug = 'jurnal-rekening-air';

    // Eager load relationships for better performance
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'jurnalRekeningAir',
                'rekening.kelompok',
                'nomorBantu',
                'kodeProyek'
            ]);
    }

    // Authorization helpers
    public static function canViewAny(): bool
    {
        return Auth::check();
    }

    public static function canCreate(): bool
    {
        return Auth::check();
    }

    public static function canEdit($record): bool
    {
        // Check if parent jurnal is confirmed
        if ($record && $record->jurnalRekeningAir && $record->jurnalRekeningAir->is_confirmed) {
            return false;
        }
        return Auth::check();
    }

    public static function canDelete($record): bool
    {
        // Check if parent jurnal is confirmed
        if ($record && $record->jurnalRekeningAir && $record->jurnalRekeningAir->is_confirmed) {
            return false;
        }
        return Auth::check();
    }

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

                            // Rekening
                            Forms\Components\Select::make('temp_rekening')
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
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $rekening = Rekening::find($state);
                                        if ($rekening) {
                                            $set('temp_position', $rekening->kode === 'K' ? 'kredit' : 'debit');
                                            $set('temp_nomor_bantu', null);
                                        }
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
                                        'kode_proyek' => $get('temp_kode_proyek'),
                                        'rekening' => $get('temp_rekening'),
                                        'nomor_bantu' => $get('temp_nomor_bantu'),
                                        'position' => $get('temp_position'),
                                        'jumlah' => (float) preg_replace('/[^0-9]/', '', $get('temp_jumlah') ?? '0'),
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

                                    $currentItems = $get('rekening_air_items') ?? [];
                                    $currentItems[] = array_merge($tempData, ['id' => count($currentItems) + 1]);
                                    $set('rekening_air_items', $currentItems);

                                    // Clear form
                                    $set('temp_kode_proyek', null);
                                    $set('temp_rekening', null);
                                    $set('temp_nomor_bantu', null);
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
                                ->label('Konfirmasi Selesai Menambah Item')
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
                                    $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
                                    $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');

                                    if ($totalDebit !== $totalKredit || $totalDebit == 0) {
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
                                ->modalHeading('Konfirmasi Item Selesai')
                                ->modalDescription('Apakah Anda yakin sudah selesai menambahkan semua item? Sistem akan memvalidasi balance sebelum melanjutkan.')
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
                                    $count = count($get('rekening_air_items') ?? []);
                                    if ($count > 0) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
                                        $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');
                                        $balance = $totalDebit === $totalKredit && $totalDebit > 0 ? '✅' : '⚠️';
                                        return "{$balance} {$count} item ditambahkan - Klik 'Konfirmasi Selesai' untuk melanjutkan";
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
                                        $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
                                        return 'Rp ' . number_format($totalDebit, 0, ',', '.');
                                    }),

                                Forms\Components\Placeholder::make('total_kredit')
                                    ->label('Total Kredit')
                                    ->content(function (callable $get) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');
                                        return 'Rp ' . number_format($totalKredit, 0, ',', '.');
                                    }),

                                Forms\Components\Placeholder::make('status_balance')
                                    ->label('⚖️ Status Balance')
                                    ->content(function (callable $get) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalDebit = collect($items)->where('position', 'debit')->sum('jumlah');
                                        $totalKredit = collect($items)->where('position', 'kredit')->sum('jumlah');

                                        $isBalance = $totalDebit === $totalKredit && $totalDebit > 0;
                                        return $isBalance ? '✅ Balance' : '⚠️ Tidak Balance';
                                    }),
                            ]),

                        Forms\Components\Hidden::make('rp'),
                    ])
                    ->visible(fn(Forms\Get $get) => !empty($get('rekening_air_items'))),

                // === NOMOR REFERENSI (Auto-generate) ===
                Forms\Components\Section::make('Nomor Referensi')
                    ->schema([
                        Forms\Components\Placeholder::make('no_reff_preview')
                            ->label('Nomor Referensi')
                            ->content('Auto-generate: 2-X/2024')
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(),

                // === HIDDEN FIELDS ===
                Forms\Components\Hidden::make('no_reff'),
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
                Tables\Columns\TextColumn::make('jurnalRekeningAir.no_reff')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('jurnalRekeningAir.tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jurnalRekeningAir.bukti')
                    ->label('Bukti')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Rekening')
                    ->searchable()
                    ->formatStateUsing(fn($record) => $record->rekening ? 
                        $record->rekening->kelompok->no_kel . '-' . 
                        $record->rekening->no_rek . ' ' . 
                        $record->rekening->nama_rek : '-')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->rekening ? 
                        $record->rekening->kelompok->no_kel . '-' . 
                        $record->rekening->no_rek . ' ' . 
                        $record->rekening->nama_rek : '-'),

                Tables\Columns\TextColumn::make('nomorBantu.nm_bantu')
                    ->label('Nomor Bantu')
                    ->searchable()
                    ->formatStateUsing(fn($record) => $record->nomorBantu ? 
                        $record->nomorBantu->no_bantu . ' - ' . 
                        $record->nomorBantu->nm_bantu : '-')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('kodeProyek.name')
                    ->label('Proyek')
                    ->searchable()
                    ->default('-')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('position')
                    ->label('D/K')
                    ->badge()
                    ->color(fn($state) => $state === 'debit' ? 'danger' : 'success')
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn($record) => $record->position === 'debit' ? 'danger' : 'success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('jurnalRekeningAir.keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->jurnalRekeningAir?->keterangan)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('jurnalRekeningAir.is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                            ->when($data['dari_tanggal'], fn($q) => 
                                $q->whereHas('jurnalRekeningAir', fn($query) => 
                                    $query->whereDate('tanggal', '>=', $data['dari_tanggal'])
                                )
                            )
                            ->when($data['sampai_tanggal'], fn($q) => 
                                $q->whereHas('jurnalRekeningAir', fn($query) => 
                                    $query->whereDate('tanggal', '<=', $data['sampai_tanggal'])
                                )
                            );
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

                Tables\Filters\SelectFilter::make('position')
                    ->label('Posisi')
                    ->options([
                        'debit' => 'Debit',
                        'kredit' => 'Kredit',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('confirm')
                        ->label('Konfirmasi Jurnal')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            // Confirm parent jurnal
                            $record->jurnalRekeningAir->confirm();
                            Notification::make()
                                ->title('Jurnal berhasil dikonfirmasi')
                                ->body("No. Reff: {$record->jurnalRekeningAir->no_reff}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription(fn($record) => "Apakah Anda yakin ingin mengkonfirmasi jurnal {$record->jurnalRekeningAir->no_reff}?")
                        ->visible(fn($record) => !$record->jurnalRekeningAir->is_confirmed),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($record) {
                            // Unconfirm parent jurnal
                            $record->jurnalRekeningAir->unconfirm();
                            Notification::make()
                                ->title('Konfirmasi jurnal dibatalkan')
                                ->body("No. Reff: {$record->jurnalRekeningAir->no_reff}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Batal Konfirmasi Jurnal')
                        ->modalDescription(fn($record) => "Apakah Anda yakin ingin membatalkan konfirmasi jurnal {$record->jurnalRekeningAir->no_reff}?")
                        ->visible(fn($record) => $record->jurnalRekeningAir->is_confirmed),

                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus Item')
                        ->modalHeading('Hapus Item Transaksi')
                        ->modalDescription(fn($record) => "Item ini akan dihapus dari jurnal {$record->jurnalRekeningAir->no_reff}")
                        ->visible(fn($record) => !$record->jurnalRekeningAir->is_confirmed)
                        ->after(function ($record) {
                            // Check if parent jurnal still has details
                            $parent = $record->jurnalRekeningAir;
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
                    ->color('primary'),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Check if any of the records are confirmed
                            $confirmedCount = $records->filter(fn($record) => 
                                $record->jurnalRekeningAir->is_confirmed
                            )->count();
                            
                            if ($confirmedCount > 0) {
                                Notification::make()
                                    ->title('Tidak dapat menghapus')
                                    ->body("Terdapat {$confirmedCount} item dari jurnal yang sudah dikonfirmasi")
                                    ->danger()
                                    ->send();
                                    
                                return false;
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            //
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
