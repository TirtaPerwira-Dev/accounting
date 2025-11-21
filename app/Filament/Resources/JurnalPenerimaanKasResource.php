<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalPenerimaanKasResource\Pages;
use App\Filament\Widgets\JurnalPenerimaanKasStatsWidget;
use App\Models\JurnalPenerimaanKas;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Blade;

class JurnalPenerimaanKasResource extends Resource
{
    protected static ?string $model = JurnalPenerimaanKas::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Jurnal Penerimaan Kas';

    protected static ?string $navigationGroup = 'Jurnal Transaksi';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralModelLabel = 'Jurnal Penerimaan Kas/Bank';

    protected static ?string $slug = 'jurnal-penerimaan-kas';

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
        return Auth::check();
    }

    public static function canDelete($record): bool
    {
        return Auth::check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === SECTION 1: KAS/BANK TUJUAN (DEBIT) ===
                Forms\Components\Section::make('Kas/Bank Tujuan (DEBIT)')
                    ->description('Pilih rekening kas atau bank tempat uang masuk')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                // Kelompok
                                Forms\Components\Select::make('kelompok_id')
                                    ->label('Kelompok')
                                    ->options(function () {
                                        return Kelompok::where('no_kel', '10') // Aktiva Lancar only
                                            ->pluck('nama_kel', 'id')
                                            ->mapWithKeys(fn($nama, $id) => [
                                                $id => Kelompok::find($id)->no_kel . ' - ' . $nama
                                            ]);
                                    })
                                    ->default(function () {
                                        return Kelompok::where('no_kel', '10')->first()?->id;
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('rekening_id', null);
                                        $set('kas_bank_id', null);
                                    }),

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
                                    }),

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
                                    ->placeholder('Pilih Nomor Bantu'),

                                // Tanggal
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->helperText('Tanggal penerimaan kas/bank'),
                            ]),
                    ])
                    ->collapsible(),

                // === SECTION 2: SUMBER PENERIMAAN (KREDIT) ===
                Forms\Components\Section::make('Sumber Penerimaan (KREDIT)')
                    ->description('Detail sumber-sumber uang yang masuk ke kas/bank')
                    ->schema([
                        Forms\Components\Repeater::make('detail_penerimaan')
                            ->label('Detail Sumber Penerimaan')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        // Nomor Bukti
                                        Forms\Components\TextInput::make('nomor_bukti')
                                            ->label('Nomor Bukti')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Contoh: BKM-001, KAS-001'),

                                        // Kode Proyek
                                        Forms\Components\Select::make('kode_proyek')
                                            ->label('Kode Proyek')
                                            ->options(function () {
                                                return KodeProyek::all()
                                                    ->pluck('name', 'id')
                                                    ->mapWithKeys(fn($nama, $id) => [
                                                        $id => KodeProyek::find($id)->kode . ' - ' . $nama
                                                    ]);
                                            })
                                            ->searchable()
                                            ->placeholder('Pilih Proyek'),

                                        // Rekening (Sumber Kredit)
                                        Forms\Components\Select::make('rekening')
                                            ->label('Rekening (Sumber)')
                                            ->options(function () {
                                                return Rekening::with('kelompok')
                                                    ->get()
                                                    ->mapWithKeys(fn($rekening) => [
                                                        $rekening->id => "{$rekening->kelompok->no_kel}-{$rekening->no_rek} - {$rekening->nama_rek}"
                                                    ]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $set('nomor_bantu', null);
                                                }
                                            }),

                                        // Nomor Bantu
                                        Forms\Components\Select::make('nomor_bantu')
                                            ->label('Nomor Bantu')
                                            ->options(function (callable $get) {
                                                $rekeningId = $get('rekening');
                                                if (!$rekeningId) return [];

                                                return NomorBantu::where('rekening_id', $rekeningId)
                                                    ->get()
                                                    ->mapWithKeys(fn($item) => [
                                                        $item->id => $item->no_bantu . ' - ' . $item->nm_bantu
                                                    ]);
                                            })
                                            ->searchable()
                                            ->placeholder('Pilih No. Bantu'),

                                        // Jumlah
                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->required()
                                            ->prefix('Rp')
                                            ->suffixIcon('heroicon-o-plus-circle')
                                            ->suffixIconColor('success')
                                            ->placeholder('0')
                                            ->live(),

                                        // Keterangan Item
                                        Forms\Components\Textarea::make('keterangan_item')
                                            ->label('Keterangan')
                                            ->placeholder('Detail sumber penerimaan ini')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),


                            ])
                            ->defaultItems(1)
                            ->addActionLabel('➕ Tambah Sumber Penerimaan')
                            ->columnSpanFull()
                            ->live(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan Umum')
                            ->placeholder('Contoh: Penerimaan dari penjualan, Penerimaan bunga bank, dll')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // === SECTION 3: BALANCE & RINGKASAN ===
                Forms\Components\Section::make('Ringkasan Transaksi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('total_amount')
                                    ->label('Total Penerimaan')
                                    ->content(function (callable $get) {
                                        $details = $get('detail_penerimaan') ?? [];
                                        $total = collect($details)->sum(fn($item) => (float) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    }),

                                Forms\Components\Placeholder::make('status_balance')
                                    ->label('⚖️ Status Balance')
                                    ->content(function (callable $get) {
                                        $details = $get('detail_penerimaan') ?? [];
                                        $total = collect($details)->sum(fn($item) => (float) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));
                                        $isBalance = $total > 0;
                                        return $isBalance ? '✅ Balance' : '⚠️ Belum Balance';
                                    }),
                            ]),
                    ]),

                // === SECTION 4: NOMOR REFERENSI ===
                Forms\Components\Section::make('Nomor Referensi')
                    ->description('Kode referensi otomatis untuk sistem jurnal penerimaan kas')
                    ->schema([
                        Forms\Components\TextInput::make('reff')
                            ->label('Reff (Auto-Generated)')
                            ->default(function () {
                                $lastRecord = JurnalPenerimaanKas::whereYear('created_at', now()->year)
                                    ->whereMonth('created_at', now()->month)
                                    ->count();
                                $nextNumber = $lastRecord + 1;
                                return '3-' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT) . '/' . now()->format('m/Y');
                            })
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(20)
                            ->helperText('Format: 3-XX/MM/YYYY (Auto-generated)'),

                        Forms\Components\Placeholder::make('reff_info')
                            ->label('ℹ️ Informasi Referensi')
                            ->content('Nomor referensi dibuat otomatis berdasarkan urutan transaksi bulanan')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reff')
                    ->label('Referensi')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('kelompok.nama_kel')
                    ->label('Kelompok')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(function ($record) {
                        return $record->kelompok?->no_kel . ' - ' . $record->kelompok?->nama_kel;
                    }),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Rekening')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(function ($record) {
                        return $record->rekening?->no_rek . ' - ' . $record->rekening?->nama_rek;
                    }),

                Tables\Columns\TextColumn::make('kasBank.nm_bantu')
                    ->label('Kas/Bank')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->getStateUsing(function ($record) {
                        if ($record->detail_penerimaan) {
                            $total = collect($record->detail_penerimaan)->sum('jumlah');
                            return 'Rp ' . number_format($total, 0, ',', '.');
                        }
                        return 'Rp 0';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->keterangan),

                Tables\Columns\TextColumn::make('reff')
                    ->label('Reff')
                    ->badge()
                    ->color('primary'),

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
                            ->when($data['dari_tanggal'], fn($q) => $q->whereDate('tanggal', '>=', $data['dari_tanggal']))
                            ->when($data['sampai_tanggal'], fn($q) => $q->whereDate('tanggal', '<=', $data['sampai_tanggal']));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print_pdf')
                    ->label('📄 PDF')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn($record) => route('jurnal-penerimaan-kas.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_pdf')
                        ->label('📄 Export PDF Terpilih')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->action(function ($records) {
                            return response()->streamDownload(function () use ($records) {
                                echo Pdf::loadView('pdf.jurnal-penerimaan-kas-bulk', [
                                    'records' => $records,
                                    'title' => 'JPK Terpilih'
                                ])->stream();
                            }, 'JPK-Selected-' . now()->format('Y-m-d-H-i') . '.pdf');
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
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
            'edit' => Pages\EditJurnalPenerimaanKas::route('/{record}/edit'),
        ];
    }
}
