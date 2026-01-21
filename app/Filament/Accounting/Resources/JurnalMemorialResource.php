<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;
use App\Filament\Accounting\Resources\JurnalMemorialResource\RelationManagers;
use App\Filament\Widgets\JurnalMemorialStatsWidget;
use App\Models\JurnalMemorial;
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
use Illuminate\Support\Facades\Auth;

class JurnalMemorialResource extends Resource
{
    protected static ?string $model = JurnalMemorial::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Jurnal Memorial';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralModelLabel = 'Jurnal Memorial';

    protected static ?string $slug = 'jurnal-memorial';

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
        return Auth::check() && !$record->is_confirmed;
    }
    public static function canDelete($record): bool
    {
        return Auth::check() && !$record->is_confirmed;
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

                        Forms\Components\Hidden::make('no_reff')
                            ->default(fn() => 'MEM-' . date('YmdHis')),
                    ]),

                // SECTION 2: FORM TAMBAH ITEM MEMORIAL
                Forms\Components\Section::make('Tambah Item Jurnal Memorial')
                    ->description('Isi form di bawah ini lalu klik "Tambah Item"')
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
                                    return KodeProyek::pluck('name', 'id');
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
                                ->numeric()
                                ->default(0)
                                ->live()
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
                                    $tempJumlah = $get('temp_jumlah') ?? 0;
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
                Forms\Components\Hidden::make('ref')->default('6'),
                Forms\Components\Hidden::make('company_id')->default(1),
                Forms\Components\Hidden::make('created_by')->default(fn() => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bukti')
                    ->label('No Bukti')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Nama Rekening')
                    ->limit(30),

                Tables\Columns\TextColumn::make('kodeProyekRekening')
                    ->label('Kode Proyek/Rekening')
                    ->getStateUsing(function ($record) {
                        if ($record->kode_proyek_id) {
                            return $record->kodeProyek?->name ?? '-';
                        }
                        $kelompok = $record->kelompok?->no_kel ?? '';
                        $rek = $record->rekening?->no_rek ?? '';
                        $bantu = $record->nomorBantu?->no_bantu ?? '';
                        return $kelompok ? "{$kelompok}-{$rek}-{$bantu}" : '-';
                    })
                    ->searchable(false)
                    ->limit(20),

                Tables\Columns\TextColumn::make('kode')
                    ->label('D/K')
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->badge()
                    ->color(fn($state) => $state === 'D' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('rp')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight(),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('no_reff')
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
                    Tables\Actions\EditAction::make()->visible(fn($record) => !$record->is_confirmed),

                    Tables\Actions\Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn($record) => !$record->is_confirmed)
                        ->hidden(fn() => auth()->user()->hasRole('staff'))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'is_confirmed' => true,
                                'confirmed_by' => Auth::id(),
                                'confirmed_at' => now(),
                            ]);
                            Notification::make()->title('Jurnal dikonfirmasi')->success()->send();
                        }),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn($record) => $record->is_confirmed)
                        ->hidden(fn() => auth()->user()->hasRole('staff'))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'is_confirmed' => false,
                                'confirmed_by' => null,
                                'confirmed_at' => null,
                            ]);
                            Notification::make()->title('Konfirmasi dibatalkan')->success()->send();
                        }),

                    Tables\Actions\DeleteAction::make()->visible(fn($record) => !$record->is_confirmed),
                ])
                    ->label('Action')
                    ->button()
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
