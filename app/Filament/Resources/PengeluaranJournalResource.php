<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengeluaranJournalResource\Pages;
use App\Filament\Resources\PengeluaranJournalResource\Widgets;
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

class PengeluaranJournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-minus-circle';

    protected static ?string $navigationLabel = 'Jurnal Pengeluaran';

    protected static ?string $modelLabel = 'Jurnal Pengeluaran';

    protected static ?string $pluralModelLabel = 'Jurnal Pengeluaran';

    protected static ?string $navigationGroup = 'Transaksi Kas';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?string $slug = 'jurnal-pengeluaran';

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = Journal::where('status', 'draft')
                ->where('transaction_type', Journal::TYPE_PENGELUARAN)
                ->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('transaction_type', Journal::TYPE_PENGELUARAN);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Jurnal Pengeluaran Kas')
                    ->description('Input transaksi pengeluaran kas/uang keluar')
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
                                ->placeholder('Auto-generate: KK-YYYYMM-XXX')
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\DatePicker::make('transaction_date')
                                ->label('Tanggal Pengeluaran')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->helperText('Tanggal uang dikeluarkan'),

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
                            ->default(Journal::TYPE_PENGELUARAN),

                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan Pengeluaran')
                            ->placeholder('Contoh: Pembayaran listrik PLN bulan November 2025')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // === FORM JURNAL PENGELUARAN ===
                Section::make('Entry Jurnal Pengeluaran')
                    ->description('💸 Masukkan detail pengeluaran kas. Akun yang tersedia: Beban/Biaya (📋) dan Kas/Bank (💸)')
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
                                        'debit' => '📋 Debit (Beban/Biaya Bertambah)',
                                        'credit' => '💸 Kredit (Kas/Bank Berkurang)',
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
                                    ->placeholder('Detail pengeluaran...')
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Tambah Baris Entry')
                            ->addAction(fn($action) => $action->color('danger'))
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
                                $icon = $type === 'debit' ? '📋' : '💸';

                                if ($amount > 0) {
                                    return $icon . ' Rp ' . number_format($amount, 0, ',', '.');
                                }
                                return 'Entry Pengeluaran Baru';
                            })
                            ->columnSpanFull(),
                    ]),

                // === BALANCE CHECKER ===
                Section::make('Ringkasan Balance')
                    ->description('Monitor keseimbangan jurnal secara real-time')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Placeholder::make('total_debit')
                                ->label('📋 Total Debit')
                                ->content(function (Get $get) {
                                    $total = static::calculateTotal($get, 'debit');
                                    return 'Rp ' . number_format($total, 0, ',', '.');
                                }),

                            Forms\Components\Placeholder::make('total_credit')
                                ->label('💸 Total Kredit')
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
                    ->color('danger')
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
                            if (!$record || !$record->details) {
                                return '-';
                            }

                            $details = collect($record->details);
                            if ($details->isEmpty()) {
                                return '-';
                            }

                            $lines = [];
                            foreach ($details as $detail) {
                                if ($detail && $detail->nomor_bantu_id && $detail->nomorBantu) {
                                    $nb = $detail->nomorBantu;
                                    if ($nb && $nb->rekening && $nb->rekening->kelompok) {
                                        $code = $nb->rekening->kelompok->no_kel .
                                            $nb->rekening->no_rek .
                                            str_pad($nb->no_bantu, 2, '0', STR_PAD_LEFT);

                                        $lines[] = "[{$code}] {$nb->nm_bantu}";
                                    }
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

                // Debet Nominal
                Tables\Columns\TextColumn::make('debet_nominal')
                    ->label('Debet')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record || !$record->details) {
                                return '-';
                            }

                            $details = collect($record->details);
                            $debits = $details->where('debit', '>', 0);

                            if ($debits->isEmpty()) {
                                return '-';
                            }

                            $lines = [];
                            foreach ($debits as $detail) {
                                if ($detail && isset($detail->debit)) {
                                    $amount = 'Rp ' . number_format($detail->debit, 0, ',', '.');
                                    $lines[] = $amount;
                                }
                            }

                            return empty($lines) ? '-' : implode('<br>', $lines);
                        } catch (\Exception $e) {
                            return '-';
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
                            if (!$record || !$record->details) {
                                return '-';
                            }

                            $details = collect($record->details);
                            $credits = $details->where('credit', '>', 0);

                            if ($credits->isEmpty()) {
                                return '-';
                            }

                            $lines = [];
                            foreach ($credits as $detail) {
                                if ($detail && isset($detail->credit)) {
                                    $amount = 'Rp ' . number_format($detail->credit, 0, ',', '.');
                                    $lines[] = $amount;
                                }
                            }

                            return empty($lines) ? '-' : implode('<br>', $lines);
                        } catch (\Exception $e) {
                            return '-';
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
                        ->label('Edit Pengeluaran')
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
                    ->label('Laporan Pengeluaran')
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
                Infolists\Components\Section::make('Informasi Jurnal Pengeluaran')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('reference')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('danger'),

                                Infolists\Components\TextEntry::make('transaction_date')
                                    ->label('Tanggal Pengeluaran')
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
                                    ->label('Total Pengeluaran')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('createdBy.name')
                                    ->label('Dibuat Oleh'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Detail Transaksi Pengeluaran')
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
            'index' => Pages\ListPengeluaranJournals::route('/'),
            'create' => Pages\CreatePengeluaranJournal::route('/create'),
            'view' => Pages\ViewPengeluaranJournal::route('/{record}'),
            'edit' => Pages\EditPengeluaranJournal::route('/{record}/edit'),
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

                // Filter akun yang relevan untuk PENGELUARAN:
                // 10 = Aktiva Lancar (Kas, Bank)
                // 91 = Biaya Sumber Air
                // 92 = Biaya Pengolahan Air
                // 93 = Biaya Transmisi dan Distribusi
                // 94 = Biaya Air Limbah
                // 96 = Biaya Administrasi dan Umum
                // 98 = Biaya Diluar Usaha
                return in_array($kelompokNo, ['10', '91', '92', '93', '94', '96', '98']);
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
                        $hint = ' 💸'; // Kas/Bank - biasanya kredit (keluar)
                    } elseif (in_array($kelompokNo, ['91', '92', '93', '94', '96', '98'])) {
                        $hint = ' 📋'; // Biaya - biasanya debit
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
        $year = now()->format('Y');
        $month = now()->format('m');
        $last = Journal::whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->where('transaction_type', Journal::TYPE_PENGELUARAN)
            ->count();
        $data['reference'] = "KK-$year$month-" . str_pad($last + 1, 3, '0', STR_PAD_LEFT);

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
        $data['transaction_type'] = Journal::TYPE_PENGELUARAN;

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
            Widgets\PengeluaranStatsWidget::class,
        ];
    }

    public static function generateJournalReport(array $data): StreamedResponse
    {
        $from = $data['from'];
        $until = $data['until'];
        $status = $data['status'];

        $query = Journal::where('transaction_type', Journal::TYPE_PENGELUARAN)
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
            'title' => 'Laporan Jurnal Pengeluaran',
            'type' => 'pengeluaran'
        ]);

        $filename = 'laporan-jurnal-pengeluaran-' . $from . '-to-' . $until . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public static function printJournal(Journal $record): StreamedResponse
    {
        $record->load(['details.nomorBantu.rekening.kelompok', 'createdBy', 'company']);

        $pdf = Pdf::loadView('reports.journal-print', [
            'journal' => $record,
            'title' => 'Jurnal Pengeluaran'
        ]);

        $filename = 'jurnal-pengeluaran-' . $record->reference . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
