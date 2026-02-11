<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;
use App\Filament\Widgets\JurnalMemorialStatsWidget;
use App\Models\JurnalMemorial;
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
        return (string) \App\Models\JurnalMemorial::where('is_confirmed', 0)->count();
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

                // SECTION 2: ITEM JURNAL MEMORIAL
                Forms\Components\Section::make('Item Jurnal Memorial')
                    ->description(fn (string $context): string => $context === 'create'
                        ? 'Tambahkan item jurnal memorial satu per satu (Debit & Kredit harus seimbang)'
                        : 'Masukkan rincian akun (Debit & Kredit harus seimbang)')
                    ->schema(function (string $context) {
                        if ($context === 'create') {
                            return [
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('temp_rekening_id')
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
                                        ->afterStateUpdated(fn(callable $set) => $set('temp_nomor_bantu_id', null))
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_nomor_bantu_id')
                                        ->label('No. Bantu/Rekening')
                                        ->options(function (Forms\Get $get) {
                                            $rekeningId = $get('temp_rekening_id');
                                            if (!$rekeningId) return [];
                                            return NomorBantu::where('rekening_id', $rekeningId)
                                                ->get()
                                                ->mapWithKeys(fn($nb) => [$nb->id => "[{$nb->no_bantu}] {$nb->nm_bantu}"]);
                                        })
                                        ->searchable()
                                        ->placeholder('Pilih No. Bantu')
                                        ->dehydrated(false),

                                    Forms\Components\Select::make('temp_kode_proyek_id')
                                        ->label('Kode Proyek')
                                        ->options(KodeProyek::pluck('name', 'id'))
                                        ->searchable()
                                        ->placeholder('Pilih Proyek')
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('temp_posisi')
                                        ->label('D/K')
                                        ->options([
                                            'debit' => 'Debit',
                                            'kredit' => 'Kredit',
                                        ])
                                        ->default('debit')
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('temp_jumlah')
                                        ->label('Jumlah (Rp)')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->extraAttributes(['style' => 'text-align: right;'])
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Textarea::make('temp_keterangan')
                                    ->label('Keterangan Item')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->dehydrated(false),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('add_item')
                                        ->label('Tambah Item')
                                        ->icon('heroicon-o-plus-circle')
                                        ->color('warning')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $tempData = [
                                                'rekening' => $get('temp_rekening_id'),
                                                'nomor_bantu' => $get('temp_nomor_bantu_id'),
                                                'kode_proyek' => $get('temp_kode_proyek_id'),
                                                'position' => $get('temp_posisi'),
                                                'jumlah' => (float) ($get('temp_jumlah') ?? 0),
                                                'keterangan' => $get('temp_keterangan'),
                                            ];

                                            if (empty($tempData['rekening']) || empty($tempData['jumlah'])) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Data tidak lengkap!')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $currentItems = $get('memorial_items') ?? [];
                                            $currentItems[] = $tempData;
                                            $set('memorial_items', $currentItems);

                                            // Clear temp fields
                                            $set('temp_rekening_id', null);
                                            $set('temp_nomor_bantu_id', null);
                                            $set('temp_kode_proyek_id', null);
                                            $set('temp_posisi', 'debit');
                                            $set('temp_jumlah', null);
                                            $set('temp_keterangan', null);
                                        }),
                                ])->alignment('center'),

                                Forms\Components\ViewField::make('memorial_items')
                                    ->view('filament.forms.components.memorial-items-table'),
                            ];
                        }

                        // EDIT OPERATION: Use Repeater
                        return [
                            Forms\Components\Repeater::make('memorial_items')
                                ->label('Items')
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Select::make('rekening_id')
                                            ->label('Nama Rekening')
                                            ->options(function () {
                                                return \App\Models\Rekening::with('kelompok')
                                                    ->get()
                                                    ->mapWithKeys(fn($r) => [
                                                        $r->id => "[{$r->kelompok->no_kel}-{$r->no_rek}] {$r->nama_rek}"
                                                    ]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('nomor_bantu_id')
                                            ->label('No. Bantu/Rekening')
                                            ->options(function (Forms\Get $get) {
                                                $rekeningId = $get('rekening_id');
                                                if (!$rekeningId) return [];
                                                return NomorBantu::where('rekening_id', $rekeningId)
                                                    ->get()
                                                    ->mapWithKeys(fn($nb) => [$nb->id => "[{$nb->no_bantu}] {$nb->nm_bantu}"]);
                                            })
                                            ->searchable()
                                            ->placeholder('Pilih No. Bantu'),

                                        Forms\Components\Select::make('kode_proyek_id')
                                            ->label('Kode Proyek')
                                            ->options(KodeProyek::pluck('name', 'id'))
                                            ->searchable()
                                            ->placeholder('Pilih Proyek'),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('posisi')
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

                                    Forms\Components\Textarea::make('keterangan')
                                        ->label('Keterangan Item')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                                ->defaultItems(2)
                                ->addActionLabel('Tambah Baris Jurnal'),
                        ];
                    }),

                // SECTION 3: RINGKASAN & STATUS
                Forms\Components\Section::make('Ringkasan & Validasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('summary_preview')
                                    ->label('Total Balance')
                                    ->content(function (Forms\Get $get) {
                                        $items = $get('memorial_items') ?? [];
                                        $totalDebit = collect($items)->where('posisi', 'D')->sum('jumlah');
                                        $totalKredit = collect($items)->where('posisi', 'K')->sum('jumlah');
                                        $balance = $totalDebit - $totalKredit;

                                        $text = "Debit: Rp " . number_format($totalDebit, 0, ',', '.') . " | Kredit: Rp " . number_format($totalKredit, 0, ',', '.');
                                        if ($balance == 0 && count($items) > 0) {
                                            return new \Illuminate\Support\HtmlString("<span class='text-success font-bold'>{$text} (✅ Balanced)</span>");
                                        } elseif ($balance != 0) {
                                            $diff = number_format(abs($balance), 0, ',', '.');
                                            return new \Illuminate\Support\HtmlString("<span class='text-danger font-bold'>{$text} (⚠️ Selisih: Rp {$diff})</span>");
                                        }
                                        return $text;
                                    }),

                                Forms\Components\Placeholder::make('no_reff_info')
                                    ->label('Nomor Referensi')
                                    ->content('Status: Jurnal Memorial (Reff: 6)'),
                            ]),
                    ])
                    ->compact(),

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
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->visible(fn($record) => !($record->jurnalMemorial ?? $record)->is_confirmed),

                    Tables\Actions\Action::make('confirm')
                        ->label('✓ Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function($record) {
                            try {
                                $header = $record->jurnalMemorial ?? $record;
                                
                                if (!($header instanceof \App\Models\JurnalMemorial)) {
                                    return false;
                                }
                                
                                if ($header->is_confirmed) {
                                    return false;
                                }
                                
                                return auth()->user()->can('confirm_jurnal::memorial');
                            } catch (\Exception $e) {
                                \Log::error('Error checking confirm visibility: ' . $e->getMessage());
                                return false;
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Jurnal')
                        ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi jurnal ini? Setelah dikonfirmasi, data tidak dapat diedit lagi.')
                        ->action(function($record) {
                            $header = $record->jurnalMemorial ?? $record;
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
                                $header = $record->jurnalMemorial ?? $record;
                                
                                if (!($header instanceof \App\Models\JurnalMemorial)) {
                                    return false;
                                }
                                
                                if (!$header->is_confirmed) {
                                    return false;
                                }
                                
                                return auth()->user()->can('unconfirm_jurnal::memorial');
                            } catch (\Exception $e) {
                                \Log::error('Error checking unconfirm visibility: ' . $e->getMessage());
                                return false;
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Konfirmasi')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan konfirmasi jurnal ini?')
                        ->action(function($record) {
                            $header = $record->jurnalMemorial ?? $record;
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
                            $record->load(['rekening.kelompok', 'nomorBantu', 'kodeProyek', 'details.rekening.kelompok', 'details.nomorBantu']);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal-memorial-single', [
                                'jurnal' => $record,
                                'generatedAt' => now()->format('d M Y H:i'),
                            ]);

                            $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->bukti ?? $record->id);

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
                        ->visible(fn($record) => $record->is_confirmed && !$record->is_posted),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->visible(fn($record) => !$record->is_confirmed),
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
                            $validRecords = $records->filter(fn($record) => $record->is_confirmed && !$record->is_posted);

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
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation manager dihapus karena model resource adalah Detail
            // Detail ditampilkan langsung di infolist ViewPage
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
