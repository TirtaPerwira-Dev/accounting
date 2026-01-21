<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalPembelianResource\Pages;
use App\Filament\Accounting\Resources\JurnalPembelianResource\Widgets;
use App\Models\JurnalPembelian;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Models\Company;
use App\Imports\JurnalPembelianImport;
use App\Exports\JurnalPembelianTemplateExport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
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

    // Eager load relationships for better performance
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'kelompokKredit',
                'rekeningKredit.kelompok',
                'nomorBantuKredit',
                'kelompokDebit',
                'rekeningDebit.kelompok',
                'nomorBantuDebit',
                'details.kelompokDebit',
                'details.rekeningDebit.kelompok',
                'details.nomorBantuDebit',
                'kodeProyek',
                'confirmedBy'
            ]);
    }

    // Authorization helpers (Allow all authenticated users to access jurnal pembelian)
    public static function canViewAny(): bool
    {
        return Auth::check(); // Semua user yang sudah login bisa lihat
    }

    public static function canCreate(): bool
    {
        return Auth::check(); // Semua user yang sudah login bisa buat
    }

    public static function canEdit($record): bool
    {
        // Bisa edit jika belum dikonfirmasi dan user sudah login
        if ($record && $record->is_confirmed) {
            return false; // Tidak bisa edit jika sudah dikonfirmasi
        }
        return Auth::check();
    }

    public static function canDelete($record): bool
    {
        // Bisa hapus jika belum dikonfirmasi dan user sudah login
        if ($record && $record->is_confirmed) {
            return false; // Tidak bisa hapus jika sudah dikonfirmasi
        }
        return Auth::check();
    }

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
                                            $code = $rekening->kelompok->no_kel . $rekening->no_rek;
                                            return [$rekening->id => "[$code] {$rekening->nama_rek}"];
                                        });
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
                                            return [$nb->id => "[$nb->no_bantu] {$nb->nm_bantu}"];
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
                        Forms\Components\Hidden::make('kelompok_kredit_id')
                            ->dehydrateStateUsing(function (Forms\Get $get) {
                                $rekeningId = $get('rekening_kredit_id');
                                if ($rekeningId) {
                                    return \App\Models\Rekening::find($rekeningId)?->kelompok_id;
                                }
                                return null;
                            }),
                        Forms\Components\Hidden::make('data_k')
                            ->dehydrateStateUsing(function (Forms\Get $get) {
                                $rekeningId = $get('rekening_kredit_id');
                                if ($rekeningId) {
                                    $rekening = \App\Models\Rekening::find($rekeningId);
                                    return $rekening?->data; // Bisa kosong, bisa isi
                                }
                                return null; // Boleh null
                            }),
                        Forms\Components\Hidden::make('data_d')
                            ->dehydrateStateUsing(function (Forms\Get $get) {
                                $items = $get('pembelian_items') ?? [];
                                foreach ($items as $item) {
                                    if (!empty($item['rekening_debit_id'])) {
                                        $rekening = \App\Models\Rekening::find($item['rekening_debit_id']);
                                        if ($rekening && $rekening->data === 'AT') {
                                            return 'AT'; // Isi jika ada rekening dengan data AT
                                        }
                                    }
                                }
                                return null; // Kosong jika tidak ada rekening AT
                            }),
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
                        Forms\Components\Grid::make(5)->schema([
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
                                ->options(KodeProyek::pluck('name', 'id'))
                                ->searchable()
                                ->disabled(fn(Forms\Get $get) => $get('items_completed') ?? false)
                                ->dehydrated(false),

                            Forms\Components\Select::make('temp_nomor_bantu_debit_id')
                                ->label('Kode Rekening')
                                ->placeholder('Pilih akun pembelian...')
                                ->options(function () {
                                    return NomorBantu::with(['rekening.kelompok'])
                                        ->get()
                                        ->mapWithKeys(function ($n) {
                                            $code = $n->rekening->kelompok->no_kel .
                                                $n->rekening->no_rek .
                                                str_pad($n->no_bantu, 2, '0', STR_PAD_LEFT);
                                            return [$n->id => "[$code] {$n->nm_bantu}"];
                                        });
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
                                        'nomor_bantu_debit_id' => $get('temp_nomor_bantu_debit_id'),
                                        'jumlah' => (float) preg_replace('/[^0-9]/', '', $get('temp_jumlah') ?? '0'),
                                    ];

                                    // Validate required fields
                                    if (empty($tempData['keterangan']) || empty($tempData['nomor_bantu_debit_id']) || empty($tempData['jumlah'])) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Data tidak lengkap!')
                                            ->body('Keterangan, Kode Rekening, dan Jumlah harus diisi.')
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
                                ->label('Selesai')
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
                                ->modalHeading('Konfirmasi Item Selesai')
                                ->modalDescription('Apakah Anda yakin sudah selesai menambahkan semua item pembelian? Setelah dikonfirmasi, Anda dapat menyimpan jurnal ini.')
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
                                    $count = count($get('pembelian_items') ?? []);
                                    if ($count > 0) {
                                        return "⚠️ {$count} item ditambahkan - Klik 'Konfirmasi Selesai' untuk melanjutkan";
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
                Forms\Components\Section::make('Total')
                    ->schema([
                        Forms\Components\Placeholder::make('total_pembelian')
                            ->label('Total Pembelian')
                            ->content(function (Forms\Get $get) {
                                $items = $get('pembelian_items') ?? [];
                                $total = array_sum(array_column($items, 'jumlah'));
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            })
                            ->live(),

                        Forms\Components\Hidden::make('rp')
                            ->dehydrateStateUsing(function (Forms\Get $get) {
                                $items = $get('pembelian_items') ?? [];
                                return array_sum(array_column($items, 'jumlah'));
                            }),
                    ])
                    ->compact()
                    ->visible(fn(Forms\Get $get) => !empty($get('pembelian_items'))),

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

                // === STATUS SUBMIT ===
                Forms\Components\Section::make('Status Validasi')
                    ->schema([
                        Forms\Components\Placeholder::make('submit_status')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $items = $get('pembelian_items') ?? [];
                                $itemsCompleted = $get('items_completed') ?? false;
                                $rekeningKredit = $get('rekening_kredit_id');
                                $nomorBantuKredit = $get('nomor_bantu_kredit_id');
                                $tanggal = $get('tanggal');

                                $checks = [];
                                $checks[] = $tanggal ? '✅ Tanggal diisi' : '❌ Tanggal belum diisi';
                                $checks[] = $rekeningKredit ? '✅ Rekening kredit dipilih' : '❌ Rekening kredit belum dipilih';
                                $checks[] = $nomorBantuKredit ? '✅ Nomor bantu kredit dipilih' : '❌ Nomor bantu kredit belum dipilih';
                                $checks[] = !empty($items) ? '✅ Item ditambahkan (' . count($items) . ')' : '❌ Belum ada item';
                                $checks[] = $itemsCompleted ? '✅ Item dikonfirmasi selesai' : '❌ Item belum dikonfirmasi selesai';

                                $allValid = $tanggal && $rekeningKredit && $nomorBantuKredit && !empty($items) && $itemsCompleted;

                                $status = $allValid ?
                                    '🎉 **SIAP UNTUK DISIMPAN** - Semua validasi terpenuhi!' :
                                    '⚠️ **BELUM SIAP** - Lengkapi langkah berikut:';

                                return $status . "\n\n" . implode("\n", $checks);
                            })
                            ->live()
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->visible(fn(Forms\Get $get) => !empty($get('pembelian_items')) || $get('rekening_kredit_id')),
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
                Tables\Columns\TextColumn::make('bukti_item')
                    ->label('Bukti')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan_item')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('kodeProyekRekening')
                    ->label('Kode Proyek/Rekening')
                    ->getStateUsing(function ($record) {
                        if ($record->kode_proyek_id) {
                            return $record->kodeProyek?->name ?? '-';
                        }
                        // Format: kelompok-rekening-nomor_bantu
                        $kelompok = $record->kelompokDebit?->no_kel ?? '';
                        $rekening = $record->rekeningDebit?->no_rek ?? '';
                        $bantu = $record->nomorBantuDebit?->no_bantu ?? '';
                        return $kelompok ? "{$kelompok}-{$rekening}-{$bantu}" : '-';
                    })
                    ->searchable(false)
                    ->limit(20),

                Tables\Columns\TextColumn::make('jumlah_item')
                    ->label('Jumlah')
                    ->formatStateUsing(function ($state, $record) {
                        $amount = $state ?: $record->rp ?: 0;
                        return 'Rp ' . number_format($amount, 0, ',', '.');
                    })
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('no_reff')
                    ->label('No Reff')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_confirmed')
                    ->label('Status')
                    ->options([
                        1 => 'Dikonfirmasi',
                        0 => 'Pending',
                    ]),

                Tables\Filters\SelectFilter::make('kode_proyek_id')
                    ->label('Proyek')
                    ->options(KodeProyek::pluck('name', 'id')),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('tanggal', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('tanggal', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\EditAction::make()
                        ->visible(fn($record) => !$record->is_confirmed),

                    Tables\Actions\Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn($record) => !$record->is_confirmed)
                        ->hidden(fn() => auth()->user()->hasRole('staff'))
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                        ->action(fn($record) => $record->confirm())
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Jurnal berhasil dikonfirmasi')
                        ),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->visible(fn($record) => $record->is_confirmed)
                        ->hidden(fn() => auth()->user()->hasRole('staff'))
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Konfirmasi')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                        ->action(fn($record) => $record->unconfirm())
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Konfirmasi berhasil dibatalkan')
                        ),

                    Tables\Actions\Action::make('exportPdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($record) {
                            $record->load(['rekeningKredit.kelompok', 'nomorBantuKredit', 'kodeProyek']);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-pembelian-single', [
                                'jurnal' => $record,
                                'generatedAt' => now()->format('d M Y H:i'),
                            ]);

                            // Sanitize filename - remove invalid characters
                            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->no_reff);

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                'jurnal-pembelian-' . $safeFilename . '.pdf'
                            );
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => !$record->is_confirmed),
                ])
                    ->label('Actions')
                    ->color('warning')          // warna kuning/orange
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->button()                  // optional: jadi tombol dropdown bukan icon saja
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('confirm_selected')
                        ->label('✓ Konfirmasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->is_confirmed) {
                                    $record->confirm();
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
                                if ($record->is_confirmed) {
                                    $record->unconfirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Konfirmasi dibatalkan'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->recordUrl(
                fn(Model $record): string => Pages\ViewJurnalPembelian::getUrl([$record->id])
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
