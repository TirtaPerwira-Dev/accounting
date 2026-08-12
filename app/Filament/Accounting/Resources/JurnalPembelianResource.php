<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalPembelianResource\Pages;
use App\Filament\Accounting\Resources\JurnalPembelianResource\Widgets;
use App\Models\JurnalPembelian;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Imports\JurnalPembelianImport;
use App\Exports\JurnalPembelianTemplateExport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

class JurnalPembelianResource extends Resource
{
    protected static ?string $model = JurnalPembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Jurnal Pembelian Barang';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 1;

    protected static ?string $pluralModelLabel = 'Jurnal Pembelian Barang';

    protected static ?string $slug = 'jurnal-pembelian-barang';

    public static function getNavigationBadge(): ?string
    {
        // Count dari header (jurnal_pembelians) yang belum diposting
        return (string) JurnalPembelian::where('is_posted', 0)->count();
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
                'nomorBantuKredit.rekening.kelompok',
                'nomorBantuDebit.rekening.kelompok',
                'kodeProyek',
                'confirmedBy',
                'details.nomorBantuDebit.rekening.kelompok',
                'details.kodeProyek'
            ]);
    }

    // Authorization helpers (Allow all authenticated users to access jurnal pembelian)

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // === SECTION HUTANG ===
                Forms\Components\Section::make('Akun Hutang/Pembayaran')
                    ->description('Pilih akun untuk pembayaran atau hutang')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('rekening_kredit_id')
                                ->label('Cari Rekening')
                                ->placeholder('Pilih rekening...')
                                ->options(function () {
                                    return \App\Models\Rekening::with('kelompok')
                                        ->whereHas('kelompok', function ($q) {
                                            $q->whereIn('no_kel', ['10', '50']); // Kas/Bank atau Hutang
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($rekening) {
                                            $code = str_pad((string) $rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                            return [$rekening->id => "[{$code}] {$rekening->nama_rek}"];
                                        });
                                })
                                ->getSearchResultsUsing(function (string $search): array {
                                    return \App\Models\Rekening::with('kelompok')
                                        ->whereHas('kelompok', function ($q) {
                                            $q->whereIn('no_kel', ['10', '50']);
                                        })
                                        ->where('no_rek', 'like', '%' . preg_replace('/\D/', '', $search) . '%')
                                        ->orderBy('no_rek')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($rekening) {
                                            $code = str_pad((string) $rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                            return [$rekening->id => "[{$code}] {$rekening->nama_rek}"];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (!$value) {
                                        return null;
                                    }

                                    $rekening = \App\Models\Rekening::find($value);
                                    if (!$rekening) {
                                        return null;
                                    }

                                    $code = str_pad((string) $rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                    return "[{$code}] {$rekening->nama_rek}";
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $set('nomor_bantu_kredit_id', null);
                                    $set('nama_nomor_bantu_kredit', '');
                                }),

                            Forms\Components\Select::make('nomor_bantu_kredit_id')
                                ->label('Cari Nomor Bantu')
                                ->placeholder('Pilih nomor bantu...')
                                ->options(function (Forms\Get $get) {
                                    $rekeningId = $get('rekening_kredit_id');
                                    if (!$rekeningId) return [];

                                    return NomorBantu::where('rekening_id', $rekeningId)
                                        ->get()
                                        ->mapWithKeys(function ($nb) {
                                            $noBantu = str_pad((string) $nb->no_bantu, 3, '0', STR_PAD_LEFT);
                                            return [$nb->id => "[{$noBantu}] {$nb->nm_bantu}"];
                                        });
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if ($state) {
                                        $nomorBantu = NomorBantu::find($state);
                                        $set('nama_nomor_bantu_kredit', $nomorBantu?->nm_bantu ?? '');
                                    }
                                }),

                            Forms\Components\TextInput::make('nama_nomor_bantu_kredit')
                                ->label('Nama Nomor Bantu')
                                ->placeholder('Isi otomatis atau manual...')
                                ->maxLength(255),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Transaksi')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->required()
                                ->columnSpanFull(),
                        ]),

                        // Hidden fields
                        Forms\Components\Hidden::make('data_k')
                            ->dehydrateStateUsing(function (Forms\Get $get) {
                                $nomorBantuId = $get('nomor_bantu_kredit_id');
                                if ($nomorBantuId) {
                                    $nomorBantu = NomorBantu::with('rekening')->find($nomorBantuId);
                                    return $nomorBantu?->rekening?->data;
                                }
                                return null;
                            }),
                        Forms\Components\Hidden::make('data_d')
                            ->dehydrateStateUsing(function (Forms\Get $get) {
                                $items = $get('pembelian_items') ?? [];
                                foreach ($items as $item) {
                                    if (!empty($item['nomor_bantu_debit_id'])) {
                                        $nomorBantu = NomorBantu::with('rekening')->find($item['nomor_bantu_debit_id']);
                                        if ($nomorBantu && $nomorBantu->rekening && $nomorBantu->rekening->data === 'AT') {
                                            return 'AT'; // Isi jika ada rekening dengan data AT
                                        }
                                    }
                                }
                                return null; // Kosong jika tidak ada rekening AT
                            }),
                        Forms\Components\Hidden::make('no_reff')->default('1'),
                        Forms\Components\Hidden::make('company_id')->default(1),
                    ]),

                // === SECTION INPUT PEMBELIAN ===
                Forms\Components\Section::make('Input Item Pembelian')
                    ->description(function (Forms\Get $get) {
                        $itemsCompleted = $get('items_completed') ?? false;
                        if ($itemsCompleted) {
                            return '✅ Item sudah dikonfirmasi selesai - Form dinonaktifkan';
                        }
                        return 'Tambahkan item pembelian satu per satu';
                    })
                    ->schema([
                        Forms\Components\Grid::make(6)->schema([
                            Forms\Components\TextInput::make('temp_bukti')
                                ->label('Bukti')
                                ->placeholder('INV-001, PO-123...')
                                ->maxLength(255)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $set('temp_bukti', strtoupper($state ?? ''));
                                })
                                ->extraAttributes(['style' => 'text-transform: uppercase;'])
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            Forms\Components\Textarea::make('temp_keterangan')
                                ->label('Keterangan')
                                ->placeholder('Deskripsi item...')
                                ->rows(1)
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            Forms\Components\Select::make('temp_kode_proyek_id')
                                ->label('Kode Proyek')
                                ->placeholder('Pilih...')
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
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            Forms\Components\Select::make('temp_rekening_debit_id')
                                ->label('Kode Rekening')
                                ->placeholder('Pilih rekening...')
                                ->options(function () {
                                    return \App\Models\Rekening::query()
                                        ->orderBy('no_rek')
                                        ->limit(300)
                                        ->get()
                                        ->mapWithKeys(function ($rekening) {
                                            $noRek = str_pad((string) $rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                            return [$rekening->id => "{$noRek} - {$rekening->nama_rek}"];
                                        });
                                })
                                ->getSearchResultsUsing(function (string $search): array {
                                    $digits = preg_replace('/\D/', '', $search);

                                    return \App\Models\Rekening::query()
                                        ->where(function ($query) use ($search, $digits) {
                                            $query->where('nama_rek', 'like', "%{$search}%")
                                                ->orWhere('no_rek', 'like', "%{$digits}%");
                                        })
                                        ->orderBy('no_rek')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($rekening) {
                                            $noRek = str_pad((string) $rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                            return [$rekening->id => "{$noRek} - {$rekening->nama_rek}"];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (!$value) {
                                        return null;
                                    }

                                    $rekening = \App\Models\Rekening::find($value);
                                    if (!$rekening) {
                                        return null;
                                    }

                                    $noRek = str_pad((string) $rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                    return "{$noRek} - {$rekening->nama_rek}";
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    $set('temp_nomor_bantu_debit_id', null);
                                })
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            Forms\Components\Select::make('temp_nomor_bantu_debit_id')
                                ->label('Nomor Bantu')
                                ->placeholder('Pilih nomor bantu...')
                                ->options(function (Forms\Get $get) {
                                    $rekeningId = $get('temp_rekening_debit_id');
                                    if (!$rekeningId) {
                                        return [];
                                    }

                                    return NomorBantu::query()
                                        ->where('rekening_id', $rekeningId)
                                        ->orderBy('no_bantu')
                                        ->limit(200)
                                        ->get()
                                        ->mapWithKeys(function ($n) {
                                            $noBantu = str_pad((string) $n->no_bantu, 3, '0', STR_PAD_LEFT);
                                            return [$n->id => "{$noBantu} - {$n->nm_bantu}"];
                                        });
                                })
                                ->getSearchResultsUsing(function (string $search): array {
                                    $digits = preg_replace('/\D/', '', $search);

                                    $keyword = preg_replace('/\D/', '', $search);

                                    return NomorBantu::with(['rekening'])
                                        ->where(function ($query) use ($search, $digits, $keyword) {
                                            $query->where('nm_bantu', 'like', "%{$search}%")
                                                ->orWhere('no_bantu', 'like', "%{$digits}%")
                                                ->orWhereHas('rekening', function ($q) use ($keyword) {
                                                    $q->where('no_rek', 'like', '%' . $keyword . '%');
                                                });
                                        })
                                        ->orderBy('no_bantu')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($n) {
                                            $noBantu = str_pad((string) $n->no_bantu, 3, '0', STR_PAD_LEFT);
                                            return [$n->id => "{$noBantu} - {$n->nm_bantu}"];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (!$value) {
                                        return null;
                                    }

                                    $nomorBantu = NomorBantu::with('rekening')->find($value);
                                    if (!$nomorBantu || !$nomorBantu->rekening) {
                                        return null;
                                    }

                                    $noBantu = str_pad((string) $nomorBantu->no_bantu, 3, '0', STR_PAD_LEFT);

                                    return "{$noBantu} - {$nomorBantu->nm_bantu}";
                                })
                                ->searchable()
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

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
                                        'bukti' => $get('temp_bukti'),
                                        'keterangan' => $get('temp_keterangan'),
                                        'kode_proyek_id' => $get('temp_kode_proyek_id'),
                                        'rekening_debit_id' => $get('temp_rekening_debit_id'),
                                        'nomor_bantu_debit_id' => $get('temp_nomor_bantu_debit_id'),
                                        'jumlah' => (float) preg_replace('/[^0-9]/', '', $get('temp_jumlah') ?? '0'),
                                    ];

                                    // Validate required fields
                                    if (empty($tempData['keterangan']) || empty($tempData['rekening_debit_id']) || empty($tempData['nomor_bantu_debit_id']) || empty($tempData['jumlah'])) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Data tidak lengkap!')
                                            ->body('Keterangan, Kode Rekening, Nomor Bantu, dan Jumlah harus diisi.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $currentItems = $get('pembelian_items') ?? [];
                                    $currentItems[] = array_merge($tempData, ['id' => count($currentItems) + 1]);
                                    $set('pembelian_items', $currentItems);

                                    // Clear form
                                    $set('temp_bukti', '');
                                    $set('temp_keterangan', '');
                                    $set('temp_kode_proyek_id', null);
                                    $set('temp_rekening_debit_id', null);
                                    $set('temp_nomor_bantu_debit_id', null);
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
                Forms\Components\Section::make('Daftar Item Pembelian')
                    ->description('Preview item yang telah ditambahkan')
                    ->schema([
                        Forms\Components\ViewField::make('pembelian_items')
                            ->view('filament.forms.components.items-table'),

                        // Action untuk konfirmasi selesai menambah item
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('confirm_items_complete')
                                ->label('Konfirmasi')
                                ->icon('heroicon-o-check-circle')
                                ->color('warning')
                                ->size('lg')
                                ->visible(fn(Forms\Get $get) => !$get('items_completed') && !empty($get('pembelian_items')))
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $items = $get('pembelian_items') ?? [];

                                    if (empty($items)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Tidak ada item!')
                                            ->body('Tambahkan minimal 1 item pembelian terlebih dahulu.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $set('items_completed', true);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Item dikonfirmasi!')
                                        ->body('Silakan klik tombol "Buat" untuk menyimpan jurnal pembelian.')
                                        ->success()
                                        ->send();
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Konfirmasi Item Pembelian')
                                ->modalDescription('Apakah Anda yakin data item pembelian sudah benar? Setelah dikonfirmasi, item tidak bisa diedit atau dihapus.')
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
                                    $count = count($get('pembelian_items') ?? []);
                                    if ($count > 0) {
                                        return "⚠️ {$count} item ditambahkan - Klik 'Konfirmasi' untuk melanjutkan";
                                    }
                                    return '📋 Belum ada item yang ditambahkan';
                                }
                            })
                            ->visible(fn(Forms\Get $get) => !empty($get('pembelian_items')))
                            ->columnSpanFull(),

                        // Hidden field untuk status konfirmasi
                        Forms\Components\Hidden::make('items_completed')
                            ->default(false)
                            ->dehydrated(true), // Diubah ke true agar bisa divalidasi
                    ])
                    ->visible(fn(Forms\Get $get) => !empty($get('pembelian_items')))
                    ->collapsible(),

                // === NOMOR REFERENSI (Auto-generate) ===
                Forms\Components\Section::make('Nomor Referensi')
                    ->schema([
                        Forms\Components\Placeholder::make('no_reff_preview')
                            ->label('Nomor Referensi')
                            ->content('Nomor Reff Jurnal Pembelian Barang adalah = 1')
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(),
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

                            $import = new JurnalPembelianImport();
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
                                    ->body("Berhasil mengimport {$import->getImportedCount()} data jurnal pembelian")
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
                            new JurnalPembelianTemplateExport(),
                            'template-jurnal-pembelian.xlsx'
                        );
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('bukti')
                    ->label('Bukti')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->jurnalPembelian->tanggal ?? $record->tanggal),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(40)
                    ->wrap()
                    ->tooltip(fn($record) => $record->keterangan),

                Tables\Columns\TextColumn::make('kodeProyekRekening')
                    ->label('Kode Proyek/Rekening')
                    ->html()
                    ->getStateUsing(function ($record) {
                        // Detail item - ambil dari record detail
                        $kodeProyek = $record->kodeProyek?->kode ?? '';
                        $namaProyek = $record->kodeProyek?->name ?? '';

                        $nomorBantu = $record->nomorBantuDebit;
                        $rekening = $nomorBantu?->rekening?->no_rek ?? '';
                        $namaRekening = $nomorBantu?->rekening?->nama_rek ?? '';

                        $kode = ($kodeProyek && $rekening)
                            ? sprintf('%02d %04d', intval($kodeProyek), intval($rekening))
                            : ($rekening ? sprintf('-- %04d', intval($rekening)) : ($kodeProyek ?: '-'));

                        $nama = trim(($namaProyek ? $namaProyek : '') . ($namaProyek && $namaRekening ? ' - ' : '') . ($namaRekening ? $namaRekening : ''));

                        return "<div class='font-medium'>{$kode}</div><div class='text-xs text-gray-500'>{$nama}</div>";
                    })
                    ->searchable(false)
                    ->wrap(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_posted')
                    ->label('Posted')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->jurnalPembelian->is_posted ?? $record->is_posted),

                Tables\Columns\TextColumn::make('no_reff')
                    ->label('No Reff')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->jurnalPembelian->no_reff ?? $record->no_reff),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kode_proyek_id')
                    ->label('Proyek')
                    ->options(KodeProyek::pluck('name', 'id'))
                    ->query(function ($query, array $data) {
                        if (isset($data['value'])) {
                            return $query->where('jurnal_pembelian_details.kode_proyek_id', $data['value']);
                        }
                        return $query;
                    }),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('jurnal_pembelians.tanggal', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('jurnal_pembelians.tanggal', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('is_posted')
                    ->label('Status Posting')
                    ->placeholder('Semua Status')
                    ->trueLabel('Sudah Diposting')
                    ->falseLabel('Belum Diposting')
                    ->queries(
                        true: fn($query) => $query->where('jurnal_pembelians.is_posted', true),
                        false: fn($query) => $query->where('jurnal_pembelians.is_posted', false),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_header')
                        ->label('Lihat Jurnal')
                        ->icon('heroicon-o-eye')
                        ->url(fn($record) => Pages\ViewJurnalPembelian::getUrl(['record' => ($record->jurnalPembelian ?? $record)->id]))
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('edit_header')
                        ->label('Edit Jurnal')
                        ->icon('heroicon-o-pencil')
                        ->url(fn($record) => Pages\EditJurnalPembelian::getUrl(['record' => ($record->jurnalPembelian ?? $record)->id]))
                        ->visible(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            return !$header->is_posted && !$header->is_confirmed && auth()->user()->can('postToLedger', $header);
                        }),

                    Tables\Actions\Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(false)
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                        ->action(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            $header->confirm();
                        })
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
                        ->action(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            $header->unconfirm();
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Konfirmasi berhasil dibatalkan')
                        ),

                    Tables\Actions\Action::make('exportPdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->visible(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            return auth()->user()->can('postToLedger', $header);
                        })
                        ->action(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            $header->load(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek', 'details.rekeningDebit.kelompok', 'details.nomorBantuDebit']);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-pembelian-single', [
                                'jurnal' => $header,
                                'generatedAt' => now()->format('d M Y H:i'),
                            ])->setPaper('a4', 'portrait');

                            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $header->no_reff ?? $header->id);

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                'jurnal-pembelian-' . $safeFilename . '.pdf'
                            );
                        }),

                    Tables\Actions\Action::make('post_to_ledger')
                        ->label('Post ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record, \App\Services\JournalPostingService $service) {
                            try {
                                $header = $record->jurnalPembelian ?? $record;
                                $service->post($header);
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
                        ->visible(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            return !$header->is_posted && auth()->user()->can('postToLedger', $header);
                        }),

                    Tables\Actions\Action::make('delete_header')
                        ->label('Hapus Jurnal')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            $header->delete();
                        })
                        ->visible(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            return !$header->is_posted && !$header->is_confirmed && auth()->user()->can('postToLedger', $header);
                        }),
                ])
                    ->label('Actions')
                    ->color('warning')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_post_to_ledger')
                        ->label('Post Terpilih ke Buku Besar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, \App\Services\JournalPostingService $service) {
                            // Get unique headers from details
                            $headers = $records->map(fn($record) => $record->jurnalPembelian ?? $record)->unique('id');
                            $validRecords = $headers->filter(fn($header) => !$header->is_posted && auth()->user()->can('postToLedger', $header));

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

                    Tables\Actions\BulkAction::make('confirm_selected')
                        ->label('✓ Konfirmasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            // Get unique headers
                            $headers = $records->map(fn($record) => $record->jurnalPembelian ?? $record)->unique('id');
                            foreach ($headers as $header) {
                                if (!$header->is_confirmed && auth()->user()->can('confirm', $header)) {
                                    $header->confirm();
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
                            // Get unique headers
                            $headers = $records->map(fn($record) => $record->jurnalPembelian ?? $record)->unique('id');
                            foreach ($headers as $header) {
                                if ($header->is_confirmed && auth()->user()->can('unconfirm', $header)) {
                                    $header->unconfirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Konfirmasi dibatalkan')
                        ->visible(false),

                    Tables\Actions\BulkAction::make('delete_selected')
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->action(function ($records) {
                            // Get unique headers
                            $headers = $records->map(fn($record) => $record->jurnalPembelian ?? $record)->unique('id');
                            foreach ($headers as $header) {
                                if (!$header->is_posted && auth()->user()->can('postToLedger', $header)) {
                                    $header->delete();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Jurnal terpilih berhasil dihapus'),
                ]),
            ])
            ->defaultSort('jurnal_pembelians.tanggal', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->recordUrl(
                fn(Model $record): string => Pages\ViewJurnalPembelian::getUrl(['record' => ($record->jurnalPembelian ?? $record)->id])
            );
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Accounting\Resources\JurnalPembelianResource\RelationManagers\DetailsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\JurnalPembelianStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalPembelians::route('/'),
            'create' => Pages\CreateJurnalPembelian::route('/create'),
            'view' => Pages\ViewJurnalPembelian::route('/{record}'),
            'edit' => Pages\EditJurnalPembelian::route('/{record}/edit'),
        ];
    }
}
