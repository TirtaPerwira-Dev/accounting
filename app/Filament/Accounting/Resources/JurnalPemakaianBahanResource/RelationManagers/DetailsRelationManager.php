<?php

namespace App\Filament\Accounting\Resources\JurnalPemakaianBahanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';
    protected static ?string $title = 'Detail Pemakaian Bahan';
    protected static ?string $recordTitleAttribute = 'keterangan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Bukti')
                    ->schema([
                        Forms\Components\TextInput::make('bukti')
                            ->label('No. Bukti')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('beban_bagian')
                            ->label('Beban Bagian')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Akun Debit')
                    ->schema([
                        Forms\Components\Select::make('kelompok_debit_id')
                            ->label('Kelompok Debit')
                            ->options(Kelompok::pluck('nama_kel', 'id'))
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('rekening_debit_id', null)),

                        Forms\Components\Select::make('rekening_debit_id')
                            ->label('Rekening Debit')
                            ->options(function (callable $get) {
                                $kelompokId = $get('kelompok_debit_id');
                                if (!$kelompokId) {
                                    return [];
                                }
                                return Rekening::where('no_kel', $kelompokId)
                                    ->get()
                                    ->mapWithKeys(fn($r) => [$r->id => "{$r->no_rek} - {$r->nama_rek}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('nomor_bantu_debit_id', null)),

                        Forms\Components\Select::make('nomor_bantu_debit_id')
                            ->label('Nomor Bantu Debit')
                            ->options(function (callable $get) {
                                $rekeningId = $get('rekening_debit_id');
                                if (!$rekeningId) {
                                    return [];
                                }
                                $rekening = Rekening::find($rekeningId);
                                if (!$rekening) {
                                    return [];
                                }
                                return NomorBantu::where('no_kel', $rekening->no_kel)
                                    ->where('no_rek', $rekening->no_rek)
                                    ->get()
                                    ->mapWithKeys(fn($nb) => [$nb->id => "{$nb->no_bantu} - {$nb->nm_bantu}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Akun Kredit')
                    ->schema([
                        Forms\Components\Select::make('kelompok_kredit_id')
                            ->label('Kelompok Kredit')
                            ->options(Kelompok::pluck('nama_kel', 'id'))
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('rekening_kredit_id', null)),

                        Forms\Components\Select::make('rekening_kredit_id')
                            ->label('Rekening Kredit')
                            ->options(function (callable $get) {
                                $kelompokId = $get('kelompok_kredit_id');
                                if (!$kelompokId) {
                                    return [];
                                }
                                return Rekening::where('no_kel', $kelompokId)
                                    ->get()
                                    ->mapWithKeys(fn($r) => [$r->id => "{$r->no_rek} - {$r->nama_rek}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('nomor_bantu_kredit_id', null)),

                        Forms\Components\Select::make('nomor_bantu_kredit_id')
                            ->label('Nomor Bantu Kredit')
                            ->options(function (callable $get) {
                                $rekeningId = $get('rekening_kredit_id');
                                if (!$rekeningId) {
                                    return [];
                                }
                                $rekening = Rekening::find($rekeningId);
                                if (!$rekening) {
                                    return [];
                                }
                                return NomorBantu::where('no_kel', $rekening->no_kel)
                                    ->where('no_rek', $rekening->no_rek)
                                    ->get()
                                    ->mapWithKeys(fn($nb) => [$nb->id => "{$nb->no_bantu} - {$nb->nm_bantu}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Detail Transaksi')
                    ->schema([
                        Forms\Components\Select::make('kode_proyek_id')
                            ->label('Kode Proyek')
                            ->options(KodeProyek::pluck('nama_proyek', 'id'))
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('keterangan')
            ->columns([
                Tables\Columns\TextColumn::make('bukti')
                    ->label('No. Bukti')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('beban_bagian')
                    ->label('Beban Bagian')
                    ->searchable(),

                Tables\Columns\TextColumn::make('kelompokDebit.nama_kel')
                    ->label('Kel. Debit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rekeningDebit.nama_rek')
                    ->label('Rek. Debit')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('kelompokKredit.nama_kel')
                    ->label('Kel. Kredit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rekeningKredit.nama_rek')
                    ->label('Rek. Kredit')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
