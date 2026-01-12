<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\SaldoAwalJurnalResource\Pages;
use App\Models\SaldoAwalJurnal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaldoAwalJurnalResource extends Resource
{
    protected static ?string $model = SaldoAwalJurnal::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Saldo Awal Jurnal';

    protected static ?string $navigationGroup = 'Setup Akuntansi';

    protected static ?int $navigationSort = 1;

    protected static ?string $pluralModelLabel = 'Saldo Awal Jurnal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Saldo Awal')
                    ->description('Input saldo awal untuk jenis jurnal tertentu di awal periode/tahun')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('jenis_jurnal')
                                ->label('Jenis Jurnal')
                                ->options([
                                    'rekening_air' => 'Jurnal Rekening Air',
                                    'pemakaian_bahan' => 'Jurnal Pemakaian Bahan (JPBIK)',
                                    'memorial' => 'Jurnal Memorial',
                                    'pembelian' => 'Jurnal Pembelian',
                                    'bayar_kas_bank' => 'Jurnal Bayar Kas Bank',
                                    'penerimaan_kas' => 'Jurnal Penerimaan Kas',
                                ])
                                ->required()
                                ->searchable()
                                ->native(false),

                            Forms\Components\Select::make('tahun')
                                ->label('Tahun')
                                ->options(function () {
                                    $years = [];
                                    $currentYear = now()->year;
                                    for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                                        $years[$i] = $i;
                                    }
                                    return $years;
                                })
                                ->required()
                                ->default(now()->year)
                                ->native(false),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('saldo_debit')
                                ->label('Saldo Debit Awal')
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->default(0)
                                ->required()
                                ->live()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'autocomplete' => 'off',
                                    'style' => 'text-align: right;',
                                ])
                                ->dehydrateStateUsing(function ($state) {
                                    if (!$state) return 0;
                                    return (float) preg_replace('/[^0-9]/', '', $state);
                                })
                                ->formatStateUsing(function ($state) {
                                    if (!$state) return '0';
                                    return number_format((float)$state, 0, '', '.');
                                }),

                            Forms\Components\TextInput::make('saldo_kredit')
                                ->label('Saldo Kredit Awal')
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->default(0)
                                ->required()
                                ->live()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'autocomplete' => 'off',
                                    'style' => 'text-align: right;',
                                ])
                                ->dehydrateStateUsing(function ($state) {
                                    if (!$state) return 0;
                                    return (float) preg_replace('/[^0-9]/', '', $state);
                                })
                                ->formatStateUsing(function ($state) {
                                    if (!$state) return '0';
                                    return number_format((float)$state, 0, '', '.');
                                }),
                        ]),

                        Forms\Components\Placeholder::make('selisih')
                            ->label('Selisih (Debit - Kredit)')
                            ->content(function (Forms\Get $get) {
                                $debit = (float) preg_replace('/[^0-9]/', '', $get('saldo_debit') ?? '0');
                                $kredit = (float) preg_replace('/[^0-9]/', '', $get('saldo_kredit') ?? '0');
                                $selisih = $debit - $kredit;
                                
                                $color = $selisih == 0 ? 'success' : 'warning';
                                $icon = $selisih == 0 ? '✅' : '⚠️';
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-lg font-bold text-' . $color . '-600">' .
                                    $icon . ' Rp ' . number_format(abs($selisih), 0, ',', '.') .
                                    ($selisih < 0 ? ' (Kredit lebih besar)' : ($selisih > 0 ? ' (Debit lebih besar)' : ' (Seimbang)')) .
                                    '</div>'
                                );
                            })
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Catatan saldo awal...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenis_jurnal')
                    ->label('Jenis Jurnal')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'rekening_air' => 'Rekening Air',
                        'pemakaian_bahan' => 'Pemakaian Bahan (JPBIK)',
                        'memorial' => 'Memorial',
                        'pembelian' => 'Pembelian',
                        'bayar_kas_bank' => 'Bayar Kas Bank',
                        'penerimaan_kas' => 'Penerimaan Kas',
                        default => $state,
                    })
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_debit')
                    ->label('Saldo Debit')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_kredit')
                    ->label('Saldo Kredit')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('selisih')
                    ->label('Selisih')
                    ->getStateUsing(fn ($record) => $record->saldo_debit - $record->saldo_kredit)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format(abs($state), 0, ',', '.'))
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'success' : 'warning')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tahun', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $years = SaldoAwalJurnal::selectRaw('DISTINCT tahun')
                            ->orderBy('tahun', 'desc')
                            ->pluck('tahun', 'tahun')
                            ->toArray();
                        
                        return $years;
                    })
                    ->default(now()->year),

                Tables\Filters\SelectFilter::make('jenis_jurnal')
                    ->label('Jenis Jurnal')
                    ->options([
                        'rekening_air' => 'Rekening Air',
                        'pemakaian_bahan' => 'Pemakaian Bahan',
                        'memorial' => 'Memorial',
                        'pembelian' => 'Pembelian',
                        'bayar_kas_bank' => 'Bayar Kas Bank',
                        'penerimaan_kas' => 'Penerimaan Kas',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaldoAwalJurnal::route('/'),
            'create' => Pages\CreateSaldoAwalJurnal::route('/create'),
            'edit' => Pages\EditSaldoAwalJurnal::route('/{record}/edit'),
        ];
    }
}
