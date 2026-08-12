<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\LhkReportResource\Pages;
use App\Models\Kelompok;
use App\Models\KodeProyek;
use App\Models\LhkReport;
use App\Models\NomorBantu;
use App\Models\Rekening;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LhkReportResource extends Resource
{
    protected static ?string $model = LhkReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'LHK';

    protected static ?string $navigationGroup = 'Transaksi Kas';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'LHK';

    protected static ?string $pluralModelLabel = 'Laporan Harian Keuangan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'kasBank.rekening.kelompok',
                'kodeProyek',
                'createdBy',
            ])
            ->withSum('details', 'jumlah');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi LHK')
                    ->description('Input pemasukan saldo Kas/Bank dan pengeluaran harian')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('jenis')
                                    ->label('Jenis')
                                    ->options([
                                        'pemasukan' => 'Pemasukan',
                                        'pengeluaran' => 'Pengeluaran',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('no_reff')
                                    ->label('No Reff')
                                    ->default('LHK')
                                    ->required()
                                    ->maxLength(50),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nomor_bukti')
                                    ->label('Nomor Bukti')
                                    ->maxLength(50),

                                Forms\Components\Select::make('kode_proyek_id')
                                    ->label('Kode Proyek')
                                    ->options(function () {
                                        return KodeProyek::query()
                                            ->orderBy('kode')
                                            ->get()
                                            ->mapWithKeys(fn($proyek) => [
                                                $proyek->id => $proyek->kode . ' - ' . $proyek->name,
                                            ]);
                                    })
                                    ->searchable()
                                    ->placeholder('Opsional'),
                            ]),

                        Forms\Components\Select::make('kas_bank_id')
                            ->label('Kas/Bank')
                            ->options(function () {
                                return NomorBantu::query()
                                    ->whereHas('rekening', function ($query) {
                                        $query->where(function ($rekeningQuery) {
                                            $rekeningQuery
                                                ->where('no_rek', 'like', '1101%')
                                                ->orWhere('no_rek', 'like', '1102%');
                                        });
                                    })
                                    ->with(['rekening.kelompok'])
                                    ->orderBy('nm_bantu')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $kel = $item->rekening?->kelompok?->no_kel ?? '-';
                                        $rek = $item->rekening?->no_rek ?? '-';

                                        return [
                                            $item->id => "{$kel}-{$rek}-{$item->no_bantu} | {$item->nm_bantu}",
                                        ];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('kelompok_id', null);
                                    $set('rekening_id', null);
                                    return;
                                }

                                $nomorBantu = NomorBantu::query()->with('rekening')->find($state);

                                $set('rekening_id', $nomorBantu?->rekening_id);
                                $set('kelompok_id', $nomorBantu?->rekening?->kelompok_id);
                            }),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('kelompok_id')
                            ->default(function () {
                                return Kelompok::query()->where('no_kel', '10')->value('id');
                            }),

                        Forms\Components\Hidden::make('rekening_id'),
                        Forms\Components\Hidden::make('company_id')->default(1),
                    ]),

                Forms\Components\Section::make('Rincian Item LHK')
                    ->description('Setiap item mewakili detail pemasukan atau pengeluaran')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->schema([
                                Forms\Components\TextInput::make('nomor_bukti')
                                    ->label('Nomor Bukti Item')
                                    ->maxLength(50),

                                Forms\Components\Select::make('rekening_id')
                                    ->label('Rekening Item')
                                    ->options(function () {
                                        return Rekening::query()
                                            ->with('kelompok')
                                            ->orderBy('no_rek')
                                            ->get()
                                            ->mapWithKeys(function ($rekening) {
                                                $kel = $rekening->kelompok?->no_kel ?? '-';
                                                return [
                                                    $rekening->id => "{$kel}-{$rekening->no_rek} | {$rekening->nama_rek}",
                                                ];
                                            });
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (!$state) {
                                            $set('kelompok_id', null);
                                            $set('nomor_bantu_id', null);
                                            return;
                                        }

                                        $rekening = Rekening::query()->find($state);
                                        $set('kelompok_id', $rekening?->kelompok_id);
                                        $set('nomor_bantu_id', null);
                                    }),

                                Forms\Components\Select::make('nomor_bantu_id')
                                    ->label('Nomor Bantu Item')
                                    ->options(function (Forms\Get $get) {
                                        $rekeningId = $get('rekening_id');
                                        if (!$rekeningId) {
                                            return [];
                                        }

                                        return NomorBantu::query()
                                            ->where('rekening_id', $rekeningId)
                                            ->orderBy('no_bantu')
                                            ->get()
                                            ->mapWithKeys(fn($nomorBantu) => [
                                                $nomorBantu->id => $nomorBantu->no_bantu . ' - ' . $nomorBantu->nm_bantu,
                                            ]);
                                    })
                                    ->searchable()
                                    ->placeholder('Opsional'),

                                Forms\Components\Select::make('kode_proyek_id')
                                    ->label('Kode Proyek Item')
                                    ->options(function () {
                                        return KodeProyek::query()
                                            ->orderBy('kode')
                                            ->get()
                                            ->mapWithKeys(fn($proyek) => [
                                                $proyek->id => $proyek->kode . ' - ' . $proyek->name,
                                            ]);
                                    })
                                    ->searchable()
                                    ->placeholder('Opsional'),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),

                                Forms\Components\Textarea::make('keterangan_item')
                                    ->label('Keterangan Item')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Hidden::make('kelompok_id'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
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

                Tables\Columns\TextColumn::make('no_reff')
                    ->label('No Reff')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nomor_bukti')
                    ->label('Nomor Bukti')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('jenis')
                    ->label('Jenis')
                    ->formatStateUsing(fn(string $state) => ucfirst($state))
                    ->colors([
                        'success' => 'pemasukan',
                        'danger' => 'pengeluaran',
                    ]),

                Tables\Columns\TextColumn::make('kasBank.nm_bantu')
                    ->label('Kas/Bank')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('details_sum_jumlah')
                    ->label('Total')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Konfirmasi')
                    ->boolean(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('jenis')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')->label('Dari Tanggal')->native(false),
                        Forms\Components\DatePicker::make('sampai_tanggal')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari_tanggal'] ?? null, fn(Builder $q, $date) => $q->whereDate('tanggal', '>=', $date))
                            ->when($data['sampai_tanggal'] ?? null, fn(Builder $q, $date) => $q->whereDate('tanggal', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLhkReports::route('/'),
            'create' => Pages\CreateLhkReport::route('/create'),
            'edit' => Pages\EditLhkReport::route('/{record}/edit'),
        ];
    }
}