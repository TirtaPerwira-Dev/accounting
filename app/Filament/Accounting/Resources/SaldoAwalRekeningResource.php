<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\SaldoAwalRekeningResource\Pages;
use App\Models\SaldoAwalRekening;
use App\Models\Rekening;
use App\Models\NomorBantu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SaldoAwalRekeningResource extends Resource
{
    protected static ?string $model = SaldoAwalRekening::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Saldo Awal Rekening';

    protected static ?string $navigationGroup = 'Setup Saldo';

    protected static ?int $navigationSort = 1;

    protected static ?string $pluralModelLabel = 'Saldo Awal Rekening';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Saldo Awal')
                    ->description('Input saldo awal untuk setiap rekening di awal periode/tahun')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
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
                                ->native(false)
                                ->searchable(),

                            Forms\Components\Select::make('rekening_id')
                                ->label('Rekening')
                                ->options(function () {
                                    return Rekening::with('kelompok')
                                        ->get()
                                        ->mapWithKeys(fn($r) => [
                                            $r->id => "[{$r->kelompok->no_kel}-{$r->no_rek}] {$r->nama_rek}"
                                        ]);
                                })
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('nomor_bantu_id', null);
                                }),
                        ]),

                        Forms\Components\Select::make('nomor_bantu_id')
                            ->label('Nomor Bantu / Sub Rekening (Opsional)')
                            ->options(function (Forms\Get $get) {
                                if (!$get('rekening_id')) return [];
                                
                                return NomorBantu::where('rekening_id', $get('rekening_id'))
                                    ->get()
                                    ->mapWithKeys(fn($nb) => [
                                        $nb->id => "[{$nb->no_bantu}] {$nb->nm_bantu}"
                                    ]);
                            })
                            ->searchable()
                            ->placeholder('Pilih nomor bantu jika ada')
                            ->helperText('Kosongkan jika rekening tidak memiliki nomor bantu'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('saldo_awal')
                                ->label('Saldo Awal')
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

                            Forms\Components\Select::make('posisi')
                                ->label('Posisi Saldo')
                                ->options([
                                    'D' => 'Debit',
                                    'K' => 'Kredit',
                                ])
                                ->required()
                                ->default('D')
                                ->native(false)
                                ->helperText('Pilih Debit untuk aset, Kredit untuk kewajiban/modal')
                                ->live(),
                        ]),

                        Forms\Components\Placeholder::make('info_saldo')
                            ->label('Informasi Saldo')
                            ->content(function (Forms\Get $get) {
                                $saldo = (float) preg_replace('/[^0-9]/', '', $get('saldo_awal') ?? '0');
                                $posisi = $get('posisi') ?? 'D';
                                
                                if ($saldo == 0) {
                                    return '⚪ Saldo Rp 0';
                                }

                                $icon = $posisi === 'D' ? '🔵' : '🔴';
                                $label = $posisi === 'D' ? 'Debit' : 'Kredit';
                                $color = $posisi === 'D' ? 'primary' : 'danger';

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-lg font-bold text-' . $color . '-600">' .
                                        $icon . ' Rp ' . number_format($saldo, 0, ',', '.') . ' (' . $label . ')' .
                                        '</div>'
                                );
                            })
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Catatan saldo awal rekening ini...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('rekening.kelompok.no_kel')
                    ->label('Kelompok')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rekening.no_rek')
                    ->label('No. Rek')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Nama Rekening')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('nomorBantu.nm_bantu')
                    ->label('No. Bantu')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-')
                    ->wrap(),

                Tables\Columns\TextColumn::make('saldo_awal')
                    ->label('Saldo Awal')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\BadgeColumn::make('posisi')
                    ->label('D/K')
                    ->formatStateUsing(fn($state) => $state === 'D' ? 'Debit' : 'Kredit')
                    ->colors([
                        'primary' => 'D',
                        'danger' => 'K',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tahun', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Filter Tahun')
                    ->options(function () {
                        $years = SaldoAwalRekening::selectRaw('DISTINCT tahun')
                            ->orderBy('tahun', 'desc')
                            ->pluck('tahun', 'tahun')
                            ->toArray();
                        
                        if (empty($years)) {
                            $currentYear = now()->year;
                            return [$currentYear => $currentYear];
                        }
                        
                        return $years;
                    })
                    ->default(now()->year),

                Tables\Filters\SelectFilter::make('posisi')
                    ->label('Filter Posisi')
                    ->options([
                        'D' => 'Debit',
                        'K' => 'Kredit',
                    ]),

                Tables\Filters\SelectFilter::make('kelompok')
                    ->label('Filter Kelompok')
                    ->relationship('rekening.kelompok', 'nama_kel')
                    ->searchable()
                    ->preload(),
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
            ->emptyStateHeading('Belum ada saldo awal')
            ->emptyStateDescription('Mulai input saldo awal untuk setiap rekening')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaldoAwalRekening::route('/'),
            'create' => Pages\CreateSaldoAwalRekening::route('/create'),
            'edit' => Pages\EditSaldoAwalRekening::route('/{record}/edit'),
        ];
    }
}
