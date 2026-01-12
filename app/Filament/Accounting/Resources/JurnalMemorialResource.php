<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\JurnalMemorialResource\Pages;
use App\Filament\Widgets\JurnalMemorialStatsWidget;
use App\Models\JurnalMemorial;
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
use Illuminate\Support\Facades\Auth;

class JurnalMemorialResource extends Resource
{
    protected static ?string $model = JurnalMemorial::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Jurnal Memorial';

    protected static ?string $navigationGroup = 'Jurnal Transaksi';

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralModelLabel = 'Jurnal Memorial';

    protected static ?string $slug = 'jurnal-memorial';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['kelompok', 'rekening', 'nomorBantu', 'kodeProyek']);
    }

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
        return Auth::check() && !$record->is_confirmed;
    }
    public static function canDelete($record): bool
    {
        return Auth::check() && !$record->is_confirmed;
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

                        Forms\Components\Hidden::make('no_reff')
                            ->default(fn() => 'MEM-' . date('YmdHis')),
                    ]),

                // SECTION 2: Detail Rekening (Repeater)
                Forms\Components\Section::make('Detail Rekening')
                    ->description('Tambahkan detail jurnal memorial')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(5)->schema([
                                    // Nama Rekening
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
                                        ->live()
                                        ->afterStateUpdated(fn($set) => $set('nomor_bantu_id', null)),

                                    // Kode Proyek
                                    Forms\Components\Select::make('kode_proyek_id')
                                        ->label('Kode Proyek')
                                        ->relationship('kodeProyek', 'name')
                                        ->searchable()
                                        ->placeholder('Pilih Proyek'),

                                    // Nomor Bantu / Rekening
                                    Forms\Components\Select::make('nomor_bantu_id')
                                        ->label('Rekening (No. Bantu)')
                                        ->options(function (Forms\Get $get) {
                                            if (!$get('rekening_id')) return [];
                                            return NomorBantu::where('rekening_id', $get('rekening_id'))
                                                ->get()
                                                ->mapWithKeys(fn($nb) => [$nb->id => "[{$nb->no_bantu}] {$nb->nm_bantu}"]);
                                        })
                                        ->searchable(),

                                    // Debit
                                    Forms\Components\TextInput::make('debit')
                                        ->label('Debit')
                                        ->prefix('Rp')
                                        ->numeric()
                                        ->default(0)
                                        ->live(),

                                    // Kredit
                                    Forms\Components\TextInput::make('kredit')
                                        ->label('Kredit')
                                        ->prefix('Rp')
                                        ->numeric()
                                        ->default(0)
                                        ->live(),
                                ]),

                                // Keterangan
                                Forms\Components\Textarea::make('keterangan')
                                    ->label('Keterangan')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Item')
                            ->addAction(
                                fn($action) => $action
                                    ->icon('heroicon-o-plus-circle')
                                    ->color('warning')
                            )
                            ->collapsible()
                            ->columnSpanFull(),

                        // Summary dengan Grid (Support Dark Mode)
                        Forms\Components\Section::make('Ringkasan')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Placeholder::make('total_debit')
                                        ->label('Total Debit')
                                        ->content(function (Forms\Get $get) {
                                            $details = $get('details') ?? [];
                                            $total = 0;
                                            foreach ($details as $detail) {
                                                $total += (float)($detail['debit'] ?? 0);
                                            }
                                            return 'Rp ' . number_format($total, 0, ',', '.');
                                        })
                                        ->live(),

                                    Forms\Components\Placeholder::make('total_kredit')
                                        ->label('Total Kredit')
                                        ->content(function (Forms\Get $get) {
                                            $details = $get('details') ?? [];
                                            $total = 0;
                                            foreach ($details as $detail) {
                                                $total += (float)($detail['kredit'] ?? 0);
                                            }
                                            return 'Rp ' . number_format($total, 0, ',', '.');
                                        })
                                        ->live(),

                                    Forms\Components\Placeholder::make('selisih')
                                        ->label('Selisih (Balance)')
                                        ->content(function (Forms\Get $get) {
                                            $details = $get('details') ?? [];
                                            $totalDebit = 0;
                                            $totalKredit = 0;
                                            foreach ($details as $detail) {
                                                $totalDebit += (float)($detail['debit'] ?? 0);
                                                $totalKredit += (float)($detail['kredit'] ?? 0);
                                            }
                                            $balance = $totalDebit - $totalKredit;
                                            $status = $balance == 0 ? '✓ Balance' : '✗ Selisih: Rp ' . number_format(abs($balance), 0, ',', '.');
                                            return new \Illuminate\Support\HtmlString(
                                                '<span style="color: ' . ($balance == 0 ? '#16a34a' : '#dc2626') . '; font-weight: bold;">' . $status . '</span>'
                                            );
                                        })
                                        ->live(),
                                ]),
                            ])
                            ->collapsible(),
                    ]),

                // Hidden Fields
                Forms\Components\Hidden::make('ref')->default('6'),
                Forms\Components\Hidden::make('company_id')->default(1),
                Forms\Components\Hidden::make('created_by')->default(fn() => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_reff')
                    ->label('No. Ref')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bukti')
                    ->label('Bukti')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rekening.nama_rek')
                    ->label('Rekening')
                    ->limit(30),

                Tables\Columns\TextColumn::make('rp')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight(),

                Tables\Columns\BadgeColumn::make('kode')
                    ->colors(['success' => 'D', 'danger' => 'K']),

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
                            $import = new JurnalMemorialImport();
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
                    ->action(fn() => Excel::download(new JurnalMemorialTemplateExport(), 'template-jurnal-memorial.xlsx')),
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
                    ->query(
                        fn($query, $data) => $query
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
                        ->action(function ($record) {
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
