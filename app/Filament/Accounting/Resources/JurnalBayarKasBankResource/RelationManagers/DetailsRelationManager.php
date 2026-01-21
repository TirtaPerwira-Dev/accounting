<?php

namespace App\Filament\Accounting\Resources\JurnalBayarKasBankResource\RelationManagers;

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
    protected static ?string $title = 'Detail Transaksi';
    protected static ?string $recordTitleAttribute = 'keterangan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('no_voucher')
                    ->label('No. Voucher')
                    ->maxLength(50),

                Forms\Components\TextInput::make('dibayar_kepada')
                    ->label('Dibayar Kepada')
                    ->maxLength(255),

                Forms\Components\Select::make('kelompok_id')
                    ->label('Kelompok')
                    ->options(Kelompok::pluck('nama_kel', 'id'))
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(fn(callable $set) => $set('rekening_id', null)),

                Forms\Components\Select::make('rekening_id')
                    ->label('Rekening')
                    ->options(function (callable $get) {
                        $kelompokId = $get('kelompok_id');
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
                    ->afterStateUpdated(fn(callable $set) => $set('nomor_bantu_id', null)),

                Forms\Components\Select::make('nomor_bantu_id')
                    ->label('Nomor Bantu')
                    ->options(function (callable $get) {
                        $rekeningId = $get('rekening_id');
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

                Forms\Components\Select::make('kode_proyek_id')
                    ->label('Kode Proyek')
                    ->options(KodeProyek::pluck('nama_proyek', 'id'))
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('jumlah')
                    ->label('Jumlah')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('keterangan')
            ->columns([
                Tables\Columns\TextColumn::make('no_voucher')
                    ->label('No. Voucher')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('dibayar_kepada')
                    ->label('Dibayar Kepada')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('kelompok.nama_kel')
                    ->label('Kelompok')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Rekening')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nomorBantu.nm_bantu')
                    ->label('Nomor Bantu')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('kodeProyek.nama_proyek')
                    ->label('Proyek')
                    ->searchable()
                    ->toggleable(),

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
