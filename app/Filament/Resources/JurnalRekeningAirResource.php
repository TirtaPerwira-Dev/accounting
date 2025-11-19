<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalRekeningAirResource\Pages;
use App\Filament\Widgets\JurnalRekeningAirStatsWidget;
use App\Models\JurnalRekeningAir;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Models\Company;
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
    protected static ?string $model = JurnalRekeningAir::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Jurnal Rekening Air';

    protected static ?string $navigationGroup = 'Jurnal Transaksi';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralModelLabel = 'Jurnal Rekening Air & Non Air';

    protected static ?string $slug = 'jurnal-rekening-air';

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
        if ($record && $record->is_confirmed) {
            return false;
        }
        return Auth::check();
    }

    public static function canDelete($record): bool
    {
        if ($record && $record->is_confirmed) {
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
                                    ->helperText('Input kode sesuai dengan ketentuan perusahaan.'),

                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->helperText('Tanggal memilih hari ini secara default.'),
                            ]),
                    ])
                    ->collapsible(),

                // === SECTION INPUTAN ===
                Forms\Components\Section::make('Form Inputan Rekening Air')
                    ->description('Tambahkan item-item pembelian')
                    ->schema([
                        Forms\Components\Repeater::make('rekening_air_items')
                            ->label('Detail Transaksi')
                            ->schema([
                                Forms\Components\Grid::make(5)
                                    ->schema([
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

                                        // Rekening
                                        Forms\Components\Select::make('rekening')
                                            ->label('Rekening')
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
                                                    $rekening = Rekening::find($state);
                                                    if ($rekening) {
                                                        $set('position', $rekening->kode === 'K' ? 'kredit' : 'debit');
                                                        $set('nomor_bantu', null);
                                                        $set('nama_nomor_bantu', '');
                                                    }
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
                                            ->placeholder('Pilih No. Bantu')
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $nomorBantu = NomorBantu::find($state);
                                                    if ($nomorBantu) {
                                                        $set('nama_nomor_bantu', $nomorBantu->nm_bantu);
                                                    }
                                                } else {
                                                    $set('nama_nomor_bantu', '');
                                                }
                                            }),

                                        // Nama Nomor Bantu (Auto Input)
                                        Forms\Components\TextInput::make('nama_nomor_bantu')
                                            ->label('Nama Nomor Bantu')
                                            ->disabled()
                                            ->placeholder('Otomatis terisi')
                                            ->dehydrated(false),

                                        // D/K (Debit/Kredit)
                                        Forms\Components\Select::make('position')
                                            ->label('D/K')
                                            ->options([
                                                'debit' => 'Debit',
                                                'kredit' => 'Kredit',
                                            ])
                                            ->required()
                                            ->default('debit')
                                            ->live(),
                                    ]),

                                // Jumlah
                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required()
                                    ->prefix(function (callable $get) {
                                        $position = $get('position');
                                        if ($position === 'kredit') {
                                            return 'Rp';  // Icon hijau untuk kredit
                                        } elseif ($position === 'debit') {
                                            return 'Rp';  // Icon merah untuk debit
                                        }
                                        return 'Rp';
                                    })
                                    ->suffixIcon(function (callable $get) {
                                        $position = $get('position');
                                        if ($position === 'kredit') {
                                            return 'heroicon-o-plus-circle';  // Icon plus untuk kredit
                                        } elseif ($position === 'debit') {
                                            return 'heroicon-o-minus-circle'; // Icon minus untuk debit
                                        }
                                        return null;
                                    })
                                    ->suffixIconColor(function (callable $get) {
                                        $position = $get('position');
                                        if ($position === 'kredit') {
                                            return 'success';  // Warna hijau untuk kredit
                                        } elseif ($position === 'debit') {
                                            return 'danger';   // Warna merah untuk debit
                                        }
                                        return 'gray';
                                    })
                                    ->placeholder('0')
                                    ->live(),
                            ])

                            ->defaultItems(1)
                            ->addActionLabel('➕ Tambah Baris')
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Update total otomatis dan validasi balance
                                $totalDebit = collect($state)->where('position', 'debit')->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));
                                $totalKredit = collect($state)->where('position', 'kredit')->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));
                                $total = max($totalDebit, $totalKredit);
                                $set('rp', $total);
                            }),

                        // Informasi Balance
                        Forms\Components\Placeholder::make('balance_info')
                            ->label('ℹ️ Informasi Balance')
                            ->content(function (callable $get) {
                                $items = $get('rekening_air_items') ?? [];
                                $totalDebit = collect($items)->where('position', 'debit')->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));
                                $totalKredit = collect($items)->where('position', 'kredit')->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));

                                $isBalance = $totalDebit === $totalKredit && $totalDebit > 0;
                                $status = $isBalance ? '✅ Balance' : '⚠️ Tidak Balance';

                                return "Total Debit: Rp " . number_format($totalDebit, 0, ',', '.') . "<br>" .
                                    "Total Kredit: Rp " . number_format($totalKredit, 0, ',', '.') . "<br>" .
                                    "<strong>{$status}</strong>";
                            })
                            ->columnSpanFull()
                            ->visible(fn(callable $get) => !empty($get('rekening_air_items'))),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('📝 Keterangan')
                            ->placeholder('Contoh: Rekening air bulan November 2024, Pembayaran supplier, dll')
                            ->rows(2)
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->collapsible(),

                // === RINGKASAN ===
                Forms\Components\Section::make('📊 Ringkasan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('rp')
                                    ->label('💰 Total Transaksi')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Otomatis dari items di atas'),

                                Forms\Components\Placeholder::make('balance_check')
                                    ->label('⚖️ Status Balance')
                                    ->content(function (callable $get) {
                                        $items = $get('rekening_air_items') ?? [];
                                        $totalDebit = collect($items)->where('position', 'debit')->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));
                                        $totalKredit = collect($items)->where('position', 'kredit')->sum(fn($item) => (int) str_replace(['.', ',', 'Rp', ' '], '', $item['jumlah'] ?? 0));

                                        $isBalance = $totalDebit === $totalKredit && $totalDebit > 0;
                                        return $isBalance ?
                                            '<span style="color: green; font-weight: bold;">✅ Jurnal Balance</span>' :
                                            '<span style="color: red; font-weight: bold;">⚠️ Jurnal Tidak Balance</span>';
                                    }),
                            ]),
                    ]),

                // === HIDDEN FIELDS ===
                Forms\Components\Hidden::make('no_reff'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_reff')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bukti')
                    ->label('Bukti')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('items_summary')
                    ->label('Items')
                    ->formatStateUsing(function ($record) {
                        if (!$record->rekening_air_items) {
                            return '-';
                        }
                        $count = count($record->rekening_air_items);
                        return "{$count} baris transaksi";
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('rp')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->keterangan),

                Tables\Columns\IconColumn::make('is_confirmed')
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
                            ->when($data['dari_tanggal'], fn($q) => $q->whereDate('tanggal', '>=', $data['dari_tanggal']))
                            ->when($data['sampai_tanggal'], fn($q) => $q->whereDate('tanggal', '<=', $data['sampai_tanggal']));
                    }),

                Tables\Filters\TernaryFilter::make('is_confirmed')
                    ->label('Status Konfirmasi')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Dikonfirmasi')
                    ->falseLabel('Belum Dikonfirmasi'),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        $record->confirm();
                        Notification::make()
                            ->title('Berhasil dikonfirmasi')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn($record) => !$record->is_confirmed),

                Tables\Actions\Action::make('unconfirm')
                    ->label('Batal Konfirmasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->unconfirm();
                        Notification::make()
                            ->title('Konfirmasi dibatalkan')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->is_confirmed),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->canBeEdited()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $record->canBeEdited()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }
}
