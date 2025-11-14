<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NomorBantuResource\Pages;
use App\Filament\Resources\NomorBantuResource\RelationManagers;
use App\Models\NomorBantu;
use App\Models\Rekening;
use App\Models\Kelompok;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select as FormSelect;

class NomorBantuResource extends Resource
{
    protected static ?string $model = NomorBantu::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Master Penomoran';

    protected static ?int $navigationGroupSort = 1;

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Nomor Bantu';

    protected static ?string $navigationLabel = 'Nomor Bantu';

    protected static ?string $pluralModelLabel = 'Nomor Bantu';

    protected static ?string $slug = 'nomor-bantu';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Rekening')
                    ->description('Pilih rekening dan atur nomor bantu')
                    ->schema([
                        Select::make('rekening_id')
                            ->label('Rekening Induk')
                            ->relationship('rekening', 'nama_rek')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(
                                fn(Rekening $record): string =>
                                $record->kelompok->nama_kel . ' - ' . $record->no_rek . ' - ' . $record->nama_rek
                            )
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                if ($state) {
                                    $rekening = Rekening::find($state);
                                    if ($rekening) {
                                        $set('kel', $rekening->kelompok->kel);
                                        $set('kode', $rekening->kode); // Ambil dari rekening

                                        // Update preview jika no_bantu sudah diisi
                                        $noBantu = $get('no_bantu');
                                        if ($noBantu) {
                                            $noKel = str_pad($rekening->kelompok->no_kel, 2, '0', STR_PAD_LEFT);
                                            $noRekNum = str_pad($rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                            $noBantuPad = str_pad($noBantu, 2, '0', STR_PAD_LEFT);
                                            $set('preview_code', $noKel . '.' . $noRekNum . '.' . $noBantuPad);
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('no_bantu')
                                    ->label('Nomor Bantu')
                                    ->required()
                                    ->numeric()
                                    ->maxLength(2)
                                    ->placeholder('01, 02, 03...')
                                    ->hint('2 digit angka')
                                    ->unique(ignoreRecord: true)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                        if ($state && $get('rekening_id')) {
                                            $rekening = Rekening::find($get('rekening_id'));
                                            if ($rekening) {
                                                $noKel = str_pad($rekening->kelompok->no_kel, 2, '0', STR_PAD_LEFT);
                                                $noRek = str_pad($rekening->no_rek, 4, '0', STR_PAD_LEFT);
                                                $noBantu = str_pad($state, 2, '0', STR_PAD_LEFT);
                                                $set('preview_code', $noKel . '.' . $noRek . '.' . $noBantu);
                                            }
                                        }
                                    }),

                                Select::make('kel')
                                    ->label('Kategori (Auto)')
                                    ->options([
                                        1 => '1 - Aktiva',
                                        2 => '2 - Kewajiban',
                                        3 => '3 - Pendapatan',
                                        4 => '4 - Biaya Operasional',
                                        5 => '5 - Biaya Administrasi',
                                        6 => '6 - Biaya Luar Usaha'
                                    ])
                                    ->disabled()
                                    ->native(false),

                                Select::make('kode')
                                    ->label('Saldo Normal (Auto)')
                                    ->options([
                                        'D' => 'D - Debet',
                                        'K' => 'K - Kredit',
                                    ])
                                    ->disabled()
                                    ->native(false),
                            ]),

                        TextInput::make('nm_bantu')
                            ->label('Nama Bantu')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nama untuk nomor bantu ini')
                            ->columnSpanFull(),

                        TextInput::make('preview_code')
                            ->label('Preview Nomor Akuntansi')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Akan terisi otomatis saat rekening dan nomor bantu dipilih')
                            ->hint('Format: Kelompok.Rekening.Bantu')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ])
            ->columns(1);
    }

    public static function createForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pilih Rekening')
                    ->description('Pilih rekening untuk menambahkan nomor bantu')
                    ->schema([
                        Select::make('base_rekening_id')
                            ->label('Rekening Induk')
                            ->options(function () {
                                return Rekening::with('kelompok')
                                    ->get()
                                    ->mapWithKeys(function ($rekening) {
                                        return [
                                            $rekening->id =>
                                            $rekening->kelompok->nama_kel . ' - ' .
                                                $rekening->no_rek . ' - ' .
                                                $rekening->nama_rek
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $rekening = Rekening::find($state);
                                    if ($rekening) {
                                        $set('base_kel', $rekening->kelompok->kel);
                                        $set('base_kode', $rekening->kode); // Ambil dari rekening
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Select::make('base_kel')
                                    ->label('Kategori (Auto)')
                                    ->options([
                                        1 => '1 - Aktiva',
                                        2 => '2 - Kewajiban',
                                        3 => '3 - Pendapatan',
                                        4 => '4 - Biaya Operasional',
                                        5 => '5 - Biaya Administrasi',
                                        6 => '6 - Biaya Luar Usaha'
                                    ])
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->native(false),

                                Select::make('base_kode')
                                    ->label('Saldo Normal (Auto)')
                                    ->options([
                                        'D' => 'D - Debet',
                                        'K' => 'K - Kredit',
                                    ])
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->native(false),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Nomor Bantu')
                    ->description('Tambahkan satu atau lebih nomor bantu untuk rekening yang dipilih')
                    ->schema([
                        Repeater::make('nomor_bantus')
                            ->label('Daftar Nomor Bantu')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('no_bantu')
                                            ->label('Nomor Bantu')
                                            ->required()
                                            ->numeric()
                                            ->maxLength(2)
                                            ->placeholder('01, 02, 03...')
                                            ->hint('2 digit angka'),

                                        TextInput::make('nm_bantu')
                                            ->label('Nama Bantu')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Nama untuk nomor bantu ini'),
                                    ]),
                            ])
                            ->addActionLabel('+ Tambah Nomor Bantu')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (empty($state['no_bantu']) || empty($state['nm_bantu'])) {
                                    return 'Nomor Bantu Baru';
                                }
                                return sprintf('%02d - %s', $state['no_bantu'], $state['nm_bantu']);
                            })
                            ->minItems(1)
                            ->maxItems(99)
                            ->defaultItems(1)
                            ->grid(1),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rekening.kelompok.nama_kel')
                    ->label('Kelompok')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('nomor_akuntansi')
                    ->label('Nomor Akuntansi')
                    ->state(function (NomorBantu $record): string {
                        $noKel = str_pad($record->rekening->kelompok->no_kel, 2, '0', STR_PAD_LEFT);
                        $noRek = str_pad($record->rekening->no_rek, 4, '0', STR_PAD_LEFT);
                        $noBantu = str_pad($record->no_bantu, 2, '0', STR_PAD_LEFT);

                        return $noKel . '.' . $noRek . '.' . $noBantu;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('rekenings', 'nomor_bantus.rekening_id', '=', 'rekenings.id')
                            ->join('kelompoks', 'rekenings.kelompok_id', '=', 'kelompoks.id')
                            ->orderByRaw("LPAD(kelompoks.no_kel::text, 2, '0') || '.' || LPAD(rekenings.no_rek::text, 4, '0') || '.' || LPAD(nomor_bantus.no_bantu::text, 2, '0') {$direction}")
                            ->select('nomor_bantus.*');
                    })
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Nomor akuntansi disalin!')
                    ->copyMessageDuration(1500)
                    ->description(function (NomorBantu $record): string {
                        return 'Kelompok: ' . $record->rekening->kelompok->no_kel .
                            ' | Rekening: ' . $record->rekening->no_rek .
                            ' | Bantu: ' . $record->no_bantu;
                    })
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('nm_bantu')
                    ->label('Nama Akun')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('saldo_kategori')
                    ->label('Saldo | Kategori')
                    ->state(function (NomorBantu $record): string {
                        $saldo = match ($record->kode) {
                            'D' => 'Debet',
                            'K' => 'Kredit',
                            default => $record->kode,
                        };

                        $kategori = match ($record->kel) {
                            '1' => 'Aktiva',
                            '2' => 'Kewajiban',
                            '3' => 'Pendapatan',
                            '4' => 'Biaya Operasional',
                            '5' => 'Biaya Administrasi',
                            '6' => 'Biaya Luar Usaha',
                            default => $record->kel,
                        };

                        return $saldo . ' | ' . $kategori;
                    })
                    ->badge()
                    ->color(fn(NomorBantu $record): string => match ($record->kode) {
                        'D' => 'success',
                        'K' => 'danger',
                        default => 'secondary',
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('kelompok_rekening')
                    ->form([
                        FormSelect::make('kelompok_id')
                            ->label('Kelompok')
                            ->options(
                                Kelompok::query()
                                    ->orderBy('no_kel')
                                    ->get()
                                    ->mapWithKeys(function ($kelompok) {
                                        return [$kelompok->id => $kelompok->no_kel . ' - ' . $kelompok->nama_kel];
                                    })
                                    ->toArray()
                            )
                            ->searchable()
                            ->placeholder('Pilih Kelompok')
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('rekening_id', null);
                            }),

                        FormSelect::make('rekening_id')
                            ->label('Rekening')
                            ->options(function (callable $get) {
                                $kelompokId = $get('kelompok_id');

                                if (!$kelompokId) {
                                    return [];
                                }

                                return Rekening::query()
                                    ->where('kelompok_id', $kelompokId)
                                    ->orderBy('no_rek')
                                    ->get()
                                    ->mapWithKeys(function ($rekening) {
                                        return [$rekening->id => $rekening->no_rek . ' - ' . $rekening->nama_rek];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('Pilih Rekening')
                            ->disabled(fn(callable $get) => !$get('kelompok_id')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['kelompok_id'],
                                fn(Builder $query, $kelompokId): Builder => $query->whereHas(
                                    'rekening.kelompok',
                                    fn(Builder $query): Builder => $query->where('id', $kelompokId)
                                )
                            )
                            ->when(
                                $data['rekening_id'],
                                fn(Builder $query, $rekeningId): Builder => $query->where('rekening_id', $rekeningId)
                            );
                    }),

                Tables\Filters\SelectFilter::make('kode')
                    ->label('Saldo Normal')
                    ->options([
                        'D' => 'D - Debet',
                        'K' => 'K - Kredit',
                    ]),

                Tables\Filters\SelectFilter::make('kel')
                    ->label('Kategori')
                    ->options([
                        1 => '1 - Aktiva',
                        2 => '2 - Kewajiban',
                        3 => '3 - Pendapatan',
                        4 => '4 - Biaya Operasional',
                        5 => '5 - Biaya Administrasi',
                        6 => '6 - Biaya Luar Usaha'
                    ]),
            ])
            ->headerActions([
                Action::make('export_all_pdf')
                    ->label('Unduh No.Bantu')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        FormSelect::make('kelompok_id')
                            ->label('Filter Kelompok')
                            ->options(
                                Kelompok::query()
                                    ->orderBy('no_kel')
                                    ->get()
                                    ->mapWithKeys(function ($kelompok) {
                                        return [$kelompok->id => $kelompok->no_kel . ' - ' . $kelompok->nama_kel];
                                    })
                                    ->prepend('Semua Kelompok', '')
                                    ->toArray()
                            )
                            ->searchable()
                            ->placeholder('Pilih Kelompok')
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('rekening_id', null);
                            }),

                        FormSelect::make('rekening_id')
                            ->label('Filter Rekening')
                            ->options(function (callable $get) {
                                $kelompokId = $get('kelompok_id');

                                if (!$kelompokId) {
                                    return Rekening::query()
                                        ->with('kelompok')
                                        ->orderBy('no_rek')
                                        ->get()
                                        ->mapWithKeys(function ($rekening) {
                                            return [$rekening->id => $rekening->kelompok->nama_kel . ' - ' . $rekening->no_rek . ' - ' . $rekening->nama_rek];
                                        })
                                        ->prepend('Semua Rekening', '')
                                        ->toArray();
                                }

                                return Rekening::query()
                                    ->where('kelompok_id', $kelompokId)
                                    ->orderBy('no_rek')
                                    ->get()
                                    ->mapWithKeys(function ($rekening) {
                                        return [$rekening->id => $rekening->no_rek . ' - ' . $rekening->nama_rek];
                                    })
                                    ->prepend('Semua Rekening', '')
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('Pilih Rekening'),

                        FormSelect::make('kode')
                            ->label('Filter Saldo Normal')
                            ->options([
                                '' => 'Semua',
                                'D' => 'D - Debet',
                                'K' => 'K - Kredit',
                            ])
                            ->placeholder('Pilih Saldo Normal'),

                        FormSelect::make('kel')
                            ->label('Filter Kategori')
                            ->options([
                                '' => 'Semua',
                                1 => '1 - Aktiva',
                                2 => '2 - Kewajiban',
                                3 => '3 - Pendapatan',
                                4 => '4 - Biaya Operasional',
                                5 => '5 - Biaya Administrasi',
                                6 => '6 - Biaya Luar Usaha'
                            ])
                            ->placeholder('Pilih Kategori'),
                    ])
                    ->action(function (array $data) {
                        $queryParams = array_filter($data);
                        return redirect()->to(route('nomor-bantu.export-pdf', $queryParams));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->color('warning') // Ini yang membuat warnanya kuning (warning)
                    ->icon('heroicon-o-ellipsis-vertical') // Opsional: ganti icon
                    ->size('sm')
                    ->button()
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_selected_pdf')
                        ->label('Export Terpilih ke PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($records) {
                            $ids = collect($records)->pluck('id')->implode(',');
                            return redirect()->to(route('nomor-bantu.export-pdf', ['ids' => $ids]));
                        }),
                ]),
            ])
            ->defaultSort('no_bantu');
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
            'index' => Pages\ListNomorBantus::route('/'),
            'create' => Pages\CreateNomorBantu::route('/create'),
            'edit' => Pages\EditNomorBantu::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total Nomor Bantu Terdaftar';
    }
}
