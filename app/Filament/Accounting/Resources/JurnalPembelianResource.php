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
    protected static ?string $model = \App\Models\JurnalPembelianDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Jurnal Pembelian Barang';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 1;

    protected static ?string $pluralModelLabel = 'Jurnal Pembelian Barang';

    protected static ?string $slug = 'jurnal-pembelian-barang';

    public static function getNavigationBadge(): ?string
    {
        // Count dari header (jurnal_pembelians) yang belum dikonfirmasi
        return (string) \App\Models\JurnalPembelian::where('is_confirmed', 0)->count();
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
                'jurnalPembelian.nomorBantuKredit.rekening.kelompok',
                'nomorBantuDebit.rekening.kelompok',
                'kodeProyek',
            ]);
    }

    // Authorization helpers (Allow all authenticated users to access jurnal pembelian)

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === SECTION HUTANG (HEADER) ===
                Forms\Components\Section::make('Informasi Utama (Header)')
                    ->description('Data ini berlaku untuk semua item dalam satu transaksi')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Transaksi')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->required(),

                            Forms\Components\Select::make('rekening_kredit_id')
                                ->label('Akun Kredit (Kas/Bank/Hutang)')
                                ->placeholder('Pilih rekening...')
                                ->options(function () {
                                    return \App\Models\Rekening::with('kelompok')
                                        ->whereHas('kelompok', function ($q) {
                                            $q->whereIn('no_kel', ['10', '50']);
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
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    $set('nomor_bantu_kredit_id', null);
                                    $set('nama_nomor_bantu_kredit', '');
                                }),

                            Forms\Components\Select::make('nomor_bantu_kredit_id')
                                ->label('Nomor Bantu Kredit')
                                ->placeholder('Pilih nomor bantu...')
                                ->options(function (Forms\Get $get) {
                                    $rekeningId = $get('rekening_kredit_id');
                                    if (!$rekeningId) return [];

                                    return NomorBantu::where('rekening_id', $rekeningId)
                                        ->get()
                                        ->mapWithKeys(fn($nb) => [$nb->id => "[$nb->no_bantu] {$nb->nm_bantu}"]);
                                })
                                ->searchable()
                                ->required()
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
                                ->maxLength(255)
                                ->columnSpan(1),

                            Forms\Components\Textarea::make('keterangan_header')
                                ->label('Keterangan Umum')
                                ->placeholder('Keterangan untuk seluruh transaksi...')
                                ->rows(1)
                                ->columnSpan(2),
                        ]),
                    ]),

                // === SECTION REPEATER (DETAILS) ===
                Forms\Components\Section::make('Item Pembelian')
                    ->description(fn (string $context): string => $context === 'create'
                        ? 'Tambahkan item pembelian satu per satu'
                        : 'Masukkan satu atau lebih item pembelian')
                    ->schema(function (string $context) {
                        if ($context === 'create') {
                            return [
                                Forms\Components\Grid::make(5)->schema([
                                    Forms\Components\TextInput::make('temp_bukti')
                                        ->label('No. Bukti')
                                        ->placeholder('INV...')
                                        ->maxLength(255)
                                        ->extraAttributes(['style' => 'text-transform: uppercase;'])
                                        ->dehydrateStateUsing(fn($state) => strtoupper($state))
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('temp_keterangan')
                                        ->label('Keterangan Item')
                                        ->placeholder('Keterangan baris...')
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_kode_proyek_id')
                                        ->label('Proyek')
                                        ->options(KodeProyek::pluck('name', 'id'))
                                        ->searchable()
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_nomor_bantu_debit_id')
                                        ->label('Akun Debit')
                                        ->placeholder('Pilih akun...')
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
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('temp_jumlah')
                                        ->label('Jumlah (Rp)')
                                        ->prefix('Rp')
                                        ->numeric()
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
                                                'bukti' => $get('temp_bukti'),
                                                'keterangan' => $get('temp_keterangan'),
                                                'kode_proyek_id' => $get('temp_kode_proyek_id'),
                                                'nomor_bantu_debit_id' => $get('temp_nomor_bantu_debit_id'),
                                                'jumlah' => (float) ($get('temp_jumlah') ?? 0),
                                            ];

                                            if (empty($tempData['nomor_bantu_debit_id']) || empty($tempData['jumlah'])) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Data tidak lengkap!')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $currentItems = $get('pembelian_items') ?? [];
                                            $currentItems[] = $tempData;
                                            $set('pembelian_items', $currentItems);

                                            // Clear temp fields
                                            $set('temp_bukti', null);
                                            $set('temp_keterangan', null);
                                            $set('temp_kode_proyek_id', null);
                                            $set('temp_nomor_bantu_debit_id', null);
                                            $set('temp_jumlah', null);
                                        }),
                                ])->alignment('center'),

                                Forms\Components\ViewField::make('pembelian_items')
                                    ->view('filament.forms.components.items-table'),
                            ];
                        }

                        // EDIT OPERATION: Use Repeater
                        return [
                            Forms\Components\Repeater::make('pembelian_items')
                                ->label('Items')
                                ->schema([
                                    Forms\Components\Grid::make(5)->schema([
                                        Forms\Components\TextInput::make('bukti')
                                            ->label('No. Bukti')
                                            ->placeholder('INV...')
                                            ->maxLength(255)
                                            ->extraAttributes(['style' => 'text-transform: uppercase;'])
                                            ->dehydrateStateUsing(fn($state) => strtoupper($state)),

                                        Forms\Components\TextInput::make('keterangan')
                                            ->label('Keterangan Item')
                                            ->placeholder('Pilih akun...')
                                            ->required(),

                                        Forms\Components\Select::make('kode_proyek_id')
                                            ->label('Proyek')
                                            ->options(KodeProyek::pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\Select::make('nomor_bantu_debit_id')
                                            ->label('Akun Debit')
                                            ->placeholder('Pilih akun...')
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
                                            ->required(),

                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah (Rp)')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->required()
                                            ->extraAttributes([
                                                'style' => 'text-align: right;',
                                            ]),
                                    ]),
                                ])
                                ->columns(1)
                                ->defaultItems(1)
                                ->itemLabel(fn (array $state): ?string => $state['keterangan'] ?? null)
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->cloneable()
                                ->addActionLabel('Tambah Baris Item'),
                        ];
                    }),

                // Validasi balance tidak diperlukan di Pembelian karena single sided detail (all debit vs one header credit)
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

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn($record) => $record->jurnalPembelian->is_confirmed ?? $record->is_confirmed),

                Tables\Columns\TextColumn::make('no_reff')
                    ->label('No Reff')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->jurnalPembelian->no_reff ?? $record->no_reff),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_confirmed')
                    ->label('Status')
                    ->options([
                        1 => 'Dikonfirmasi',
                        0 => 'Pending',
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['value'])) {
                            return $query->where('jurnal_pembelians.is_confirmed', $data['value']);
                        }
                        return $query;
                    }),

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
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->visible(function($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            return !$header->is_confirmed;
                        }),

                    Tables\Actions\Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function($record) {
                            try {
                                // Get header dari relationship atau langsung dari record
                                $header = $record->jurnalPembelian ?? $record;
                                
                                // Pastikan header adalah JurnalPembelian dan belum dikonfirmasi
                                if (!($header instanceof \App\Models\JurnalPembelian)) {
                                    return false;
                                }
                                
                                if ($header->is_confirmed) {
                                    return false;
                                }
                                
                                // Check permission - gunakan permission langsung tanpa policy
                                return auth()->user()->can('confirm_jurnal::pembelian');
                            } catch (\Exception $e) {
                                \Log::error('Error checking confirm visibility: ' . $e->getMessage());
                                return false;
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                        ->action(function($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            $header->confirm();
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Jurnal berhasil dikonfirmasi')
                        ),

                    Tables\Actions\Action::make('unconfirm')
                        ->label('↶ Batal Konfirmasi')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->visible(function($record) {
                            try {
                                // Get header dari relationship atau langsung dari record
                                $header = $record->jurnalPembelian ?? $record;
                                
                                // Pastikan header adalah JurnalPembelian dan sudah dikonfirmasi
                                if (!($header instanceof \App\Models\JurnalPembelian)) {
                                    return false;
                                }
                                
                                if (!$header->is_confirmed) {
                                    return false;
                                }
                                
                                // Check permission - gunakan permission langsung tanpa policy
                                return auth()->user()->can('unconfirm_jurnal::pembelian');
                            } catch (\Exception $e) {
                                \Log::error('Error checking unconfirm visibility: ' . $e->getMessage());
                                return false;
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Konfirmasi')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                        ->action(function($record) {
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
                        ->action(function ($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            $header->load([
                                'nomorBantuKredit.rekening.kelompok', 
                                'kodeProyek', 
                                'details.nomorBantuDebit.rekening.kelompok', 
                                'details.kodeProyek',
                                'confirmedBy'
                            ]);
                            
                            // Get company data
                            $company = \App\Models\Company::first();
                            
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-pembelian-single', [
                                'company' => $company,
                                'jurnal' => $header,
                                'generatedAt' => now()->format('d M Y H:i'),
                            ])->setPaper('a4', 'portrait')
                              ->setOption('isHtml5ParserEnabled', true)
                              ->setOption('isRemoteEnabled', true);

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
                        ->visible(function($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            return $header->is_confirmed && !$header->is_posted;
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->visible(function($record) {
                            $header = $record->jurnalPembelian ?? $record;
                            return !$header->is_confirmed;
                        }),
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
                            // Get unique headers from details
                            $headers = $records->map(fn($record) => $record->jurnalPembelian ?? $record)->unique('id');
                            $validRecords = $headers->filter(fn($header) => $header->is_confirmed && !$header->is_posted);

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
                                if (!$header->is_confirmed) {
                                    $header->confirm();
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
                            // Get unique headers
                            $headers = $records->map(fn($record) => $record->jurnalPembelian ?? $record)->unique('id');
                            foreach ($headers as $header) {
                                if ($header->is_confirmed) {
                                    $header->unconfirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Konfirmasi dibatalkan'),
                        
                    Tables\Actions\BulkAction::make('delete_selected')
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->action(function ($records) {
                            // Get unique headers
                            $headers = $records->map(fn($record) => $record->jurnalPembelian ?? $record)->unique('id');
                            foreach ($headers as $header) {
                                if (!$header->is_confirmed) {
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
                fn(Model $record): string => Pages\ViewJurnalPembelian::getUrl([($record->jurnalPembelian ?? $record)->id])
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
