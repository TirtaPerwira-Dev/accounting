<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalBayarKasBankResource\Pages;
use App\Filament\Widgets\JurnalBayarKasBankStatsWidget;
use App\Models\JurnalBayarKasBank;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use App\Imports\JurnalBayarKasBankImport;
use App\Exports\JurnalBayarKasBankTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class JurnalBayarKasBankResource extends Resource
{
    protected static ?string $model = JurnalBayarKasBank::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Jurnal Bayar Kas/Bank';

    protected static ?string $navigationGroup = 'Jurnal Transaksi';

    protected static ?int $navigationSort = 4;

    protected static ?string $pluralModelLabel = 'Jurnal Bayar Kas/Bank';

    protected static ?string $slug = 'jurnal-bayar-kas-bank';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['kelompok', 'rekening', 'nomorBantu', 'kodeProyek']);
    }

    public static function canViewAny(): bool { return Auth::check(); }
    public static function canCreate(): bool { return Auth::check(); }
    public static function canEdit($record): bool { return Auth::check() && !$record->is_confirmed; }
    public static function canDelete($record): bool { return Auth::check() && !$record->is_confirmed; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: Header Transaksi
                Forms\Components\Section::make('Informasi Pembayaran')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            // No Voucher
                            Forms\Components\TextInput::make('no_voucher')
                                ->label('No. Voucher')
                                ->required()
                                ->maxLength(255)
                                ->default(fn() => 'BKB-' . date('Ymd') . '-' . rand(100, 999)),

                            // Tanggal Check
                            Forms\Components\DatePicker::make('tanggal_check')
                                ->label('Tanggal Check')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required(),

                            // Kode Rekening Bank (Rekening + Nomor Bantu)
                            Forms\Components\Select::make('rekening_bank_id')
                                ->label('Kode Rekening Bank')
                                ->options(function () {
                                    return Rekening::with(['kelompok', 'nomorBantus'])
                                        ->whereHas('kelompok', fn($q) => $q->where('no_kel', '10'))
                                        ->where('no_rek', 'like', '1102%') // Bank only
                                        ->get()
                                        ->flatMap(function ($rekening) {
                                            // Jika ada nomor bantu, tampilkan dengan nomor bantu
                                            if ($rekening->nomorBantus->count() > 0) {
                                                return $rekening->nomorBantus->mapWithKeys(fn($nb) => [
                                                    $rekening->id . '|' . $nb->id => "{$rekening->no_rek} {$nb->no_bantu} - {$nb->nm_bantu}"
                                                ]);
                                            }
                                            // Jika tidak ada nomor bantu
                                            return [$rekening->id . '|0' => "{$rekening->no_rek} - {$rekening->nama_rek}"];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (!$state) return;
                                    [$rekeningId, $nomorBantuId] = explode('|', $state);
                                    $rekening = Rekening::find($rekeningId);
                                    $set('nama_bank', $rekening?->nama_rek ?? '');
                                    $set('rekening_id', $rekeningId);
                                    $set('nomor_bantu_id', $nomorBantuId > 0 ? $nomorBantuId : null);
                                }),
                        ]),

                        Forms\Components\Grid::make(4)->schema([
                            // Nama Bank (Auto-fill, Read-only)
                            Forms\Components\TextInput::make('nama_bank')
                                ->label('Nama Bank')
                                ->disabled()
                                ->dehydrated(false),

                            // No. Cek
                            Forms\Components\TextInput::make('no_cek')
                                ->label('No. Cek')
                                ->maxLength(255),

                            // Beban Bagian
                            Forms\Components\TextInput::make('beban_bagian')
                                ->label('Beban Bagian')
                                ->maxLength(255),

                            // Boleh dibayar kepada
                            Forms\Components\TextInput::make('dibayar_kepada')
                                ->label('Boleh dibayar kepada')
                                ->maxLength(255),
                        ]),

                        // Hidden fields for backend
                        Forms\Components\Hidden::make('no_reff')
                            ->default(fn() => 'BKB-' . date('YmdHis')),
                        Forms\Components\Hidden::make('rekening_id'),
                        Forms\Components\Hidden::make('nomor_bantu_id'),
                    ]),

                // SECTION 2: Detail Pembayaran (Repeater)
                Forms\Components\Section::make('Detail Pembayaran')
                    ->description('Tambahkan detail rekening pembayaran')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    // Nama Rekening
                                    Forms\Components\Select::make('rekening_detail_id')
                                        ->label('Nama Rekening')
                                        ->options(function () {
                                            return Rekening::with('kelompok')
                                                ->get()
                                                ->mapWithKeys(fn($r) => [
                                                    $r->id => "[{$r->kelompok->no_kel}-{$r->no_rek}] {$r->nama_rek}"
                                                ]);
                                        })
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn($set) => $set('nomor_bantu_detail_id', null)),

                                    // Rekening (Nomor Bantu)
                                    Forms\Components\Select::make('nomor_bantu_detail_id')
                                        ->label('Rekening (No. Bantu)')
                                        ->options(function (Forms\Get $get) {
                                            if (!$get('rekening_detail_id')) return [];
                                            return NomorBantu::where('rekening_id', $get('rekening_detail_id'))
                                                ->get()
                                                ->mapWithKeys(fn($nb) => [$nb->id => "[{$nb->no_bantu}] {$nb->nm_bantu}"]);
                                        })
                                        ->searchable(),

                                    // Jumlah
                                    Forms\Components\TextInput::make('jumlah')
                                        ->label('Jumlah')
                                        ->prefix('Rp')
                                        ->numeric()
                                        ->required()
                                        ->default(0)
                                        ->live(),
                                ]),

                                // Keterangan dengan Dropdown Template
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('keterangan_template')
                                        ->label('Template Keterangan')
                                        ->options([
                                            'Bayar Lembur' => 'Bayar Lembur',
                                            'Perbaikan Kebocoran' => 'Perbaikan Kebocoran',
                                            'Pembelian Bahan' => 'Pembelian Bahan',
                                            'Biaya Operasional' => 'Biaya Operasional',
                                            'Gaji Karyawan' => 'Gaji Karyawan',
                                        ])
                                        ->placeholder('Pilih template keterangan')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $tanggal = $get('../../tanggal_check') ?? now()->format('d/m/Y');
                                            $keterangan = $state ? "{$state} - {$tanggal}" : '';
                                            $set('keterangan', $keterangan);
                                        }),

                                    Forms\Components\Textarea::make('keterangan')
                                        ->label('Keterangan')
                                        ->rows(2),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Item')
                            ->addAction(
                                fn ($action) => $action
                                    ->icon('heroicon-o-plus-circle')
                                    ->color('warning')
                            )
                            ->collapsible()
                            ->columnSpanFull(),

                        // Summary Total
                        Forms\Components\Section::make('Ringkasan')
                            ->schema([
                                Forms\Components\Placeholder::make('total_pembayaran')
                                    ->label('Total Pembayaran')
                                    ->content(function (Forms\Get $get) {
                                        $details = $get('details') ?? [];
                                        $total = 0;
                                        foreach ($details as $detail) {
                                            $total += (float)($detail['jumlah'] ?? 0);
                                        }
                                        return new \Illuminate\Support\HtmlString(
                                            '<span style="font-size: 1.2em; font-weight: bold; color: #059669;">Rp ' . 
                                            number_format($total, 0, ',', '.') . '</span>'
                                        );
                                    })
                                    ->live(),
                            ])
                            ->collapsible(),
                    ]),

                // Hidden Fields
                Forms\Components\Hidden::make('ref')->default('3'),
                Forms\Components\Hidden::make('company_id')->default(1),
                Forms\Components\Hidden::make('created_by')->default(fn() => Auth::id()),
                Forms\Components\Hidden::make('kelompok_id')
                    ->dehydrateStateUsing(fn(Forms\Get $get) => 
                        $get('rekening_id') ? Rekening::find($get('rekening_id'))?->kelompok_id : null
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_voucher')
                    ->label('No. Voucher')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_check')
                    ->label('Tanggal Check')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_bank')
                    ->label('Nama Bank')
                    ->limit(25)
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_cek')
                    ->label('No. Cek')
                    ->searchable(),

                Tables\Columns\TextColumn::make('dibayar_kepada')
                    ->label('Dibayar Kepada')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('rp')
                    ->label('Total')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->headerActions([
                // Import Excel
                Tables\Actions\Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File Excel')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $import = new JurnalBayarKasBankImport();
                            Excel::import($import, $data['file']);

                            if (!empty($import->getErrors())) {
                                Notification::make()
                                    ->title('Import selesai dengan error')
                                    ->body('Berhasil: ' . $import->getImportedCount() . ' | Error: ' . count($import->getErrors()))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Import berhasil!')
                                    ->body('Berhasil import ' . $import->getImportedCount() . ' data')
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import gagal')
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
                    ->action(fn() => Excel::download(new JurnalBayarKasBankTemplateExport(), 'template-jurnal-bayar-kas-bank.xlsx')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_confirmed')
                    ->label('Status')
                    ->options([1 => 'Dikonfirmasi', 0 => 'Pending']),
                
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn($query, $data) => $query
                        ->when($data['from'], fn($q, $d) => $q->whereDate('tanggal', '>=', $d))
                        ->when($data['until'], fn($q, $d) => $q->whereDate('tanggal', '<=', $d))
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()->visible(fn($record) => !$record->is_confirmed),
                    
                    Tables\Actions\Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn($record) => !$record->is_confirmed)
                        ->requiresConfirmation()
                        ->action(function($record) {
                            $record->update([
                                'is_confirmed' => true,
                                'confirmed_by' => Auth::id(),
                                'confirmed_at' => now(),
                            ]);
                            Notification::make()->title('Jurnal dikonfirmasi')->success()->send();
                        }),

                    Tables\Actions\DeleteAction::make()->visible(fn($record) => !$record->is_confirmed),
                ])
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

    public static function getWidgets(): array
    {
        return [
            JurnalBayarKasBankStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalBayarKasBanks::route('/'),
            'create' => Pages\CreateJurnalBayarKasBank::route('/create'),
            'view' => Pages\ViewJurnalBayarKasBank::route('/{record}'),
            'edit' => Pages\EditJurnalBayarKasBank::route('/{record}/edit'),
        ];
    }
}
