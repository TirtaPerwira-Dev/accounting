<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenerimaanJournalResource\Pages;
use App\Filament\Resources\PenerimaanJournalResource\Widgets;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\NomorBantu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\Section;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenerimaanJournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Jurnal Penerimaan';

    protected static ?string $modelLabel = 'Jurnal Penerimaan';

    protected static ?string $pluralModelLabel = 'Jurnal Penerimaan';

    protected static ?string $navigationGroup = 'Transaksi Kas';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?string $slug = 'jurnal-penerimaan';

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = Journal::where('status', 'draft')
                ->where('transaction_type', Journal::TYPE_PENERIMAAN)
                ->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('transaction_type', Journal::TYPE_PENERIMAAN);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Jurnal Penerimaan Kas')
                    ->description('Input transaksi penerimaan kas/uang masuk')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label('Perusahaan')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->hidden()
                                ->default(1)
                                ->disabled(),

                            Forms\Components\TextInput::make('reference')
                                ->label('No. Referensi')
                                ->placeholder('Auto-generate: KM-YYYYMM-XXX')
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\DatePicker::make('transaction_date')
                                ->label('Tanggal Penerimaan')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->helperText('Tanggal uang diterima'),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => 'Draft',
                                    'posted' => 'Posted',
                                ])
                                ->default('draft')
                                ->required()
                                ->disabled()
                                ->hidden(),
                        ]),

                        Forms\Components\Hidden::make('transaction_type')
                            ->default(Journal::TYPE_PENERIMAAN),

                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan Penerimaan')
                            ->placeholder('Contoh: Penerimaan dari penjualan air bulan November 2025')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // === FORM JURNAL PENERIMAAN ===
                Section::make('Entry Jurnal Penerimaan')
                    ->description('💰 Masukkan detail penerimaan kas. Akun yang tersedia: Kas/Bank (💰), Pendapatan (📈), dan Piutang')
                    ->schema([
                        Forms\Components\Repeater::make('simple_entries')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('nomor_bantu_id')
                                    ->label('Akun')
                                    ->options(function () {
                                        return static::getSelectableNomorBantuOptionsGrouped();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->allowHtml()
                                    ->columnSpan(2),

                                Forms\Components\Select::make('account_type')
                                    ->label('Posisi')
                                    ->options([
                                        'debit' => '💰 Debit (Kas/Bank Bertambah)',
                                        'credit' => '📈 Kredit (Pendapatan/Piutang Berkurang)',
                                    ])
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Nominal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->required()
                                    ->live()
                                    ->extraAttributes([
                                        'inputmode' => 'numeric',
                                        'autocomplete' => 'off',
                                        'style' => 'text-align: right;',
                                        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, \'\').replace(/\B(?=(\d{3})+(?!\d))/g, \'.\');',
                                        'onpaste' => 'setTimeout(() => { this.value = this.value.replace(/[^0-9]/g, \'\').replace(/\B(?=(\d{3})+(?!\d))/g, \'.\'); }, 10);'
                                    ])
                                    ->dehydrateStateUsing(function ($state) {
                                        if (!$state) return 0;
                                        return (float) preg_replace('/[^0-9]/', '', $state);
                                    })
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '';
                                        return number_format((float)$state, 0, '', '.');
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('description')
                                    ->label('Keterangan')
                                    ->placeholder('Detail penerimaan...')
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Tambah Baris Entry')
                            ->addAction(fn($action) => $action->color('success'))
                            ->deleteAction(fn($action) => $action->requiresConfirmation())
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $amount = $state['amount'] ?? 0;

                                if (is_string($amount)) {
                                    $amount = str_replace(['.', ',', ' ', 'Rp'], '', $amount);
                                }

                                $amount = is_numeric($amount) ? (float)$amount : 0;
                                $type = $state['account_type'] ?? '';
                                $icon = $type === 'debit' ? '💰' : '📈';

                                if ($amount > 0) {
                                    return $icon . ' Rp ' . number_format($amount, 0, ',', '.');
                                }
                                return 'Entry Penerimaan Baru';
                            })
                            ->columnSpanFull(),
                    ]),

                // === BALANCE CHECKER ===
                Section::make('Ringkasan Balance')
                    ->description('Monitor keseimbangan jurnal secara real-time')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Placeholder::make('total_debit')
                                ->label('💰 Total Debit')
                                ->content(function (Get $get) {
                                    $total = static::calculateTotal($get, 'debit');
                                    return 'Rp ' . number_format($total, 0, ',', '.');
                                }),

                            Forms\Components\Placeholder::make('total_credit')
                                ->label('📈 Total Kredit')
                                ->content(function (Get $get) {
                                    $total = static::calculateTotal($get, 'credit');
                                    return 'Rp ' . number_format($total, 0, ',', '.');
                                }),

                            Forms\Components\Placeholder::make('balance_status')
                                ->label('⚖️ Status Balance')
                                ->content(function (Get $get) {
                                    $debit = static::calculateTotal($get, 'debit');
                                    $credit = static::calculateTotal($get, 'credit');
                                    $diff = abs($debit - $credit);

                                    if ($diff < 1) {
                                        return '✅ SEIMBANG';
                                    }

                                    $higher = $debit > $credit ? 'Debit' : 'Kredit';
                                    return '❌ TIDAK SEIMBANG! ' . $higher . ' lebih Rp ' . number_format($diff, 0, ',', '.');
                                }),
                        ]),
                    ])
                    ->compact(),
            ])
            ->statePath('data')
            ->model(Journal::class);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'success',
                        'reversed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state): string {
                        return match ($state) {
                            'draft' => 'Draft',
                            'posted' => 'Posted',
                            'reversed' => 'Dibatalkan',
                            default => $state ?? 'Draft',
                        };
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function ($record): ?string {
                        try {
                            return $record && $record->description ? $record->description : null;
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->wrap(),

                // Kode/Nama Akun
                Tables\Columns\TextColumn::make('kode_nama_akun')
                    ->label('Kode/Nama Akun')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record) {
                                return '<span class="text-gray-400">No Record</span>';
                            }

                            // Load details if not loaded
                            if (!$record->relationLoaded('details')) {
                                $record->load([
                                    'details.nomorBantu.rekening.kelompok'
                                ]);
                            }

                            $details = $record->details;
                            if (!$details || $details->isEmpty()) {
                                return '<span class="text-gray-400">No Details</span>';
                            }

                            $lines = [];
                            foreach ($details as $detail) {
                                try {
                                    if ($detail && $detail->nomor_bantu_id && $detail->nomorBantu) {
                                        $nb = $detail->nomorBantu;
                                        if ($nb && $nb->rekening && $nb->rekening->kelompok) {
                                            $code = $nb->rekening->kelompok->no_kel .
                                                $nb->rekening->no_rek .
                                                str_pad($nb->no_bantu, 2, '0', STR_PAD_LEFT);

                                            $lines[] = '<span class="font-mono text-xs bg-green-50 text-green-700 px-1 py-0.5 rounded">[' .
                                                $code . ']</span> <span class="text-gray-800">' .
                                                e($nb->nm_bantu) . '</span>';
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $lines[] = '<span class="text-red-400">Error loading detail</span>';
                                }
                            }

                            return empty($lines) ?
                                '<span class="text-gray-400">No Valid Accounts</span>' :
                                implode('<br>', $lines);
                        } catch (\Exception $e) {
                            return '<span class="text-red-400">Error: ' . e($e->getMessage()) . '</span>';
                        }
                    })
                    ->html()
                    ->wrap()
                    ->searchable()
                    ->toggleable(),

                // Debet Nominal
                Tables\Columns\TextColumn::make('debet_nominal')
                    ->label('Debet')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record) {
                                return '<span class="text-gray-400">-</span>';
                            }

                            // Load details if not loaded
                            if (!$record->relationLoaded('details')) {
                                $record->load('details');
                            }

                            $details = $record->details;
                            if (!$details || $details->isEmpty()) {
                                return '<span class="text-gray-400">-</span>';
                            }

                            $debits = $details->where('debit', '>', 0);
                            if ($debits->isEmpty()) {
                                return '<span class="text-gray-400">-</span>';
                            }

                            $lines = [];
                            foreach ($debits as $detail) {
                                try {
                                    if ($detail && isset($detail->debit) && $detail->debit > 0) {
                                        $amount = '<span class="font-semibold text-green-600">Rp ' .
                                            number_format((float)$detail->debit, 0, ',', '.') . '</span>';
                                        $lines[] = $amount;
                                    }
                                } catch (\Exception $e) {
                                    $lines[] = '<span class="text-red-400">Error</span>';
                                }
                            }

                            return empty($lines) ?
                                '<span class="text-gray-400">-</span>' :
                                implode('<br>', $lines);
                        } catch (\Exception $e) {
                            return '<span class="text-red-400">Error</span>';
                        }
                    })
                    ->html()
                    ->alignment('right')
                    ->toggleable(),

                // Kredit Nominal
                Tables\Columns\TextColumn::make('kredit_nominal')
                    ->label('Kredit')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record) {
                                return '<span class="text-gray-400">-</span>';
                            }

                            // Load details if not loaded
                            if (!$record->relationLoaded('details')) {
                                $record->load('details');
                            }

                            $details = $record->details;
                            if (!$details || $details->isEmpty()) {
                                return '<span class="text-gray-400">-</span>';
                            }

                            $credits = $details->where('credit', '>', 0);
                            if ($credits->isEmpty()) {
                                return '<span class="text-gray-400">-</span>';
                            }

                            $lines = [];
                            foreach ($credits as $detail) {
                                try {
                                    if ($detail && isset($detail->credit) && $detail->credit > 0) {
                                        $amount = '<span class="font-semibold text-red-600">Rp ' .
                                            number_format((float)$detail->credit, 0, ',', '.') . '</span>';
                                        $lines[] = $amount;
                                    }
                                } catch (\Exception $e) {
                                    $lines[] = '<span class="text-red-400">Error</span>';
                                }
                            }

                            return empty($lines) ?
                                '<span class="text-gray-400">-</span>' :
                                implode('<br>', $lines);
                        } catch (\Exception $e) {
                            return '<span class="text-red-400">Error</span>';
                        }
                    })
                    ->html()
                    ->alignment('right')
                    ->toggleable(),

                // Detail Description
                Tables\Columns\TextColumn::make('detail_descriptions')
                    ->label('Detail Keterangan')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record || !$record->details) {
                                return '-';
                            }

                            $details = collect($record->details);
                            if ($details->isEmpty()) {
                                return '-';
                            }

                            $lines = [];
                            foreach ($details as $detail) {
                                if ($detail) {
                                    $desc = $detail->description ?? '-';
                                    $lines[] = $desc;
                                }
                            }

                            return empty($lines) ? '-' : implode('<br>', $lines);
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
                    ->html()
                    ->wrap()
                    ->searchable()
                    ->toggleable(),

                // Balance Status
                Tables\Columns\TextColumn::make('balance_status')
                    ->label('Balance Status')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record || !$record->details) {
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">No Data</span>';
                            }

                            $details = collect($record->details);
                            if ($details->isEmpty()) {
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">No Data</span>';
                            }

                            $totalDebit = $details->sum('debit') ?? 0;
                            $totalCredit = $details->sum('credit') ?? 0;
                            $isBalanced = abs($totalDebit - $totalCredit) < 0.01;

                            if ($isBalanced) {
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Seimbang</span>';
                            } else {
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Tidak Seimbang</span>';
                            }
                        } catch (\Exception $e) {
                            return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Error</span>';
                        }
                    })
                    ->html()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Buat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with([
                    'details' => function ($query) {
                        $query->orderBy('line_number');
                    },
                    'details.nomorBantu',
                    'details.nomorBantu.rekening',
                    'details.nomorBantu.rekening.kelompok',
                    'createdBy',
                    'company'
                ]);
            })
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                    ])
                    ->default(['draft', 'posted'])
                    ->multiple(),

                Tables\Filters\Filter::make('transaction_date')
                    ->label('Periode Transaksi')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth())
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(now()->endOfMonth())
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn($query, $date) => $query->whereDate('transaction_date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn($query, $date) => $query->whereDate('transaction_date', '<=', $date)
                            );
                    }),

                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Perusahaan')
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Edit Penerimaan')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn(Journal $record) => $record->status === 'draft'),

                    Tables\Actions\Action::make('post')
                        ->label('Post Jurnal')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(Journal $record) => $record->status === 'draft' && $record->is_balanced)
                        ->requiresConfirmation()
                        ->action(fn(Journal $record) => $record->post()),

                    Tables\Actions\Action::make('print')
                        ->label('Print Jurnal')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (Journal $record): StreamedResponse {
                            return static::printJournal($record);
                        })
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->visible(fn(Journal $record) => $record->status === 'draft'),
                ])
                    ->label('Aksi')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('report')
                    ->label('Laporan Penerimaan')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth())
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(now()->endOfMonth())
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'all' => 'Semua Status',
                                'draft' => 'Draft',
                                'posted' => 'Posted',
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->action(function (array $data): StreamedResponse {
                        return static::generateJournalReport($data);
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Jurnal Penerimaan')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('reference')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('transaction_date')
                                    ->label('Tanggal Penerimaan')
                                    ->date('d M Y'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'draft' => 'Draft',
                                        'posted' => 'Posted',
                                        'reversed' => 'Dibatalkan',
                                        default => 'Draft',
                                    })
                                    ->color(fn($state) => match ($state) {
                                        'draft' => 'gray',
                                        'posted' => 'success',
                                        'reversed' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull(),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('company.name')
                                    ->label('Perusahaan'),

                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Total Penerimaan')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('createdBy.name')
                                    ->label('Dibuat Oleh'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Detail Transaksi Penerimaan')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('nomorBantu.rekening.kelompok.no_kel')
                                            ->label('Kode')
                                            ->formatStateUsing(function ($record) {
                                                if ($record->nomor_bantu_id && $record->nomorBantu) {
                                                    $nomor_bantu = $record->nomorBantu;
                                                    return $nomor_bantu->rekening->kelompok->no_kel .
                                                        $nomor_bantu->rekening->no_rek .
                                                        str_pad($nomor_bantu->no_bantu, 2, '0', STR_PAD_LEFT);
                                                }
                                                return '-';
                                            })
                                            ->badge()
                                            ->color('info'),

                                        Infolists\Components\TextEntry::make('nomorBantu.nm_bantu')
                                            ->label('Nama Akun')
                                            ->default('-'),

                                        Infolists\Components\TextEntry::make('debit')
                                            ->label('Debit')
                                            ->money('IDR')
                                            ->default(0)
                                            ->color('success'),

                                        Infolists\Components\TextEntry::make('credit')
                                            ->label('Kredit')
                                            ->money('IDR')
                                            ->default(0)
                                            ->color('danger'),
                                    ]),

                                Infolists\Components\TextEntry::make('description')
                                    ->label('Keterangan')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
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
            'index' => Pages\ListPenerimaanJournals::route('/'),
            'create' => Pages\CreatePenerimaanJournal::route('/create'),
            'view' => Pages\ViewPenerimaanJournal::route('/{record}'),
            'edit' => Pages\EditPenerimaanJournal::route('/{record}/edit'),
        ];
    }

    // Helper methods
    private static function getSelectableNomorBantuOptionsGrouped()
    {
        return NomorBantu::active()
            ->with(['rekening.kelompok'])
            ->get()
            ->filter(function ($nomorBantu) {
                $kelompokNo = $nomorBantu->rekening->kelompok->no_kel;

                // Filter akun yang relevan untuk PENERIMAAN:
                // 10 = Aktiva Lancar (Kas, Bank, Piutang)
                // 80 = Pendapatan
                // 88 = Pendapatan Diluar Usaha
                return in_array($kelompokNo, ['10', '80', '88']);
            })
            ->sortBy(function ($item) {
                return $item->rekening->kelompok->no_kel .
                    $item->rekening->no_rek .
                    str_pad($item->no_bantu, 2, '0', STR_PAD_LEFT);
            })
            ->groupBy(function ($item) {
                return $item->rekening->kelompok->nama_kel;
            })
            ->map(function ($group) {
                return $group->mapWithKeys(function ($n) {
                    $code = $n->rekening->kelompok->no_kel .
                        $n->rekening->no_rek .
                        str_pad($n->no_bantu, 2, '0', STR_PAD_LEFT);

                    $kelompokNo = $n->rekening->kelompok->no_kel;
                    $hint = '';
                    if ($kelompokNo == '10') {
                        $hint = ' 💰'; // Kas/Bank - biasanya debit
                    } elseif (in_array($kelompokNo, ['80', '88'])) {
                        $hint = ' 📈'; // Pendapatan - biasanya kredit
                    }

                    return [$n->id => "[$code] {$n->nm_bantu}$hint"];
                });
            });
    }

    private static function calculateTotal(Get $get, string $type): float
    {
        return collect($get('simple_entries') ?? [])
            ->where('account_type', $type)
            ->sum(function ($item) {
                $amount = $item['amount'] ?? 0;
                if (is_string($amount)) {
                    $amount = str_replace(['.', ',', ' ', 'Rp'], '', $amount);
                }
                return is_numeric($amount) ? (float)$amount : 0;
            });
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Process amounts
        $debit = 0;
        $credit = 0;

        foreach ($data['simple_entries'] ?? [] as &$entry) {
            $amount = $entry['amount'] ?? 0;
            if (is_string($amount)) {
                $amount = str_replace(['.', ',', ' ', 'Rp'], '', $amount);
            }
            $entry['amount'] = is_numeric($amount) ? (float)$amount : 0;

            if ($entry['account_type'] === 'debit') {
                $debit += $entry['amount'];
            } else {
                $credit += $entry['amount'];
            }
        }

        if (abs($debit - $credit) >= 1) {
            throw ValidationException::withMessages([
                'data.simple_entries' => 'Total Debit dan Kredit harus seimbang!',
            ]);
        }

        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';
        $data['total_amount'] = $debit;
        $data['transaction_type'] = Journal::TYPE_PENERIMAAN;

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Process amounts
        $debit = 0;
        $credit = 0;

        foreach ($data['simple_entries'] ?? [] as &$entry) {
            $amount = $entry['amount'] ?? 0;
            if (is_string($amount)) {
                $amount = str_replace(['.', ',', ' ', 'Rp'], '', $amount);
            }
            $entry['amount'] = is_numeric($amount) ? (float)$amount : 0;

            if ($entry['account_type'] === 'debit') {
                $debit += $entry['amount'];
            } else {
                $credit += $entry['amount'];
            }
        }

        if (abs($debit - $credit) >= 1) {
            throw ValidationException::withMessages([
                'data.simple_entries' => 'Total Debit dan Kredit harus seimbang!',
            ]);
        }

        $data['total_amount'] = $debit;
        return $data;
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\PenerimaanStatsWidget::class,
        ];
    }

    public static function generateJournalReport(array $data): StreamedResponse
    {
        $from = $data['from'];
        $until = $data['until'];
        $status = $data['status'];

        $query = Journal::where('transaction_type', Journal::TYPE_PENERIMAAN)
            ->with(['details.nomorBantu.rekening.kelompok', 'createdBy', 'company'])
            ->whereBetween('transaction_date', [$from, $until]);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $journals = $query->orderBy('transaction_date', 'desc')->get();

        $pdf = Pdf::loadView('reports.journal-report', [
            'journals' => $journals,
            'from' => $from,
            'until' => $until,
            'status' => $status,
            'title' => 'Laporan Jurnal Penerimaan',
            'type' => 'penerimaan'
        ]);

        $filename = 'laporan-jurnal-penerimaan-' . $from . '-to-' . $until . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public static function printJournal(Journal $record): StreamedResponse
    {
        $record->load(['details.nomorBantu.rekening.kelompok', 'createdBy', 'company']);

        $pdf = Pdf::loadView('reports.journal-print', [
            'journal' => $record,
            'title' => 'Jurnal Penerimaan'
        ]);

        $filename = 'jurnal-penerimaan-' . $record->reference . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
