<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Filament\Resources\JournalResource\Widgets;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\NomorBantu;
use App\Models\OpeningBalance;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\Section;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Jurnal Umum';

    protected static ?string $modelLabel = 'Jurnal Umum';

    protected static ?string $pluralModelLabel = 'Jurnal Umum';

    protected static ?string $navigationGroup = 'Transaksi Kas';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?string $slug = 'jurnal-umum';


    public static function getNavigationBadge(): ?string
    {
        try {
            $count = Journal::where('status', 'draft')
                ->where(function ($query) {
                    $query->whereNull('transaction_type')
                        ->orWhere('transaction_type', '')
                        ->orWhereNotIn('transaction_type', [Journal::TYPE_PENERIMAAN, Journal::TYPE_PENGELUARAN]);
                })
                ->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where(function ($query) {
            $query->whereNull('transaction_type')
                ->orWhere('transaction_type', '')
                ->orWhereNotIn('transaction_type', [Journal::TYPE_PENERIMAAN, Journal::TYPE_PENGELUARAN]);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Dasar Jurnal Umum')
                    ->description('Khusus untuk jurnal penyesuaian, reklasifikasi, koreksi, dan transaksi non-kas lainnya')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label('Perusahaan')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->hidden()
                                ->default(1) // Default company
                                ->disabled(), // Tidak dapat diubah


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

                            Forms\Components\TextInput::make('reference')
                                ->label('No. Referensi')
                                ->placeholder('Auto-generate : JU-YYYYMM-XX')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Format: JU = Jurnal Umum'),

                            Forms\Components\DatePicker::make('transaction_date')
                                ->label('Tanggal Transaksi')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->helperText('Pilih tanggal transaksi (default: today)'),
                        ]),



                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan Jurnal Umum')
                            ->placeholder('Jelaskan transaksi jurnal penyesuaian/reklasifikasi ini...')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Contoh: Penyesuaian depresiasi, reklasifikasi aset, koreksi saldo awal'),
                    ]),

                // === FORM JURNAL SEDERHANA ===
                Section::make('Entry Jurnal Umum')
                    ->description('Masukkan detail transaksi debit dan kredit untuk jurnal penyesuaian/reklasifikasi. Semua akun tersedia untuk fleksibilitas maksimal.')
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
                                    ->columnSpan(2)
                                    ->rules([
                                        function () {
                                            return function (string $attribute, $value, \Closure $fail) {
                                                if ($value && !static::isValidAccountForJournal($value)) {
                                                    $fail('Akun yang dipilih belum memiliki saldo awal atau belum pernah digunakan dalam transaksi.');
                                                }
                                            };
                                        }
                                    ]),

                                Forms\Components\Select::make('account_type')
                                    ->label('Tipe')
                                    ->options([
                                        'debit' => '➕ Debit',
                                        'credit' => '➖ Kredit',
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
                                    ->placeholder('Detail transaksi...')
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Tambah Baris Jurnal')
                            ->addAction(fn($action) => $action->color('info'))
                            ->deleteAction(
                                fn($action) => $action->requiresConfirmation()
                            )
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $amount = $state['amount'] ?? 0;

                                // Clean amount formatting if it's a string
                                if (is_string($amount)) {
                                    $amount = str_replace(['.', ',', ' ', 'Rp'], '', $amount);
                                }

                                $amount = is_numeric($amount) ? (float)$amount : 0;
                                $type = $state['account_type'] ?? '';
                                $icon = $type === 'debit' ? '➕' : '➖';

                                if ($amount > 0) {
                                    return $icon . ' Rp ' . number_format($amount, 0, ',', '.');
                                }
                                return 'Entry Baru';
                            })
                            ->columnSpanFull(),
                    ]),

                // === RINGKASAN REAL-TIME ===
                Section::make('Ringkasan Balance')
                    ->description('Monitor keseimbangan jurnal secara real-time')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Placeholder::make('total_debit')
                                ->label('➕ Total Debit')
                                ->content(function (Get $get) {
                                    $total = static::calculateTotal($get, 'debit');
                                    return 'Rp ' . number_format($total, 0, ',', '.');
                                }),

                            Forms\Components\Placeholder::make('total_credit')
                                ->label('➖ Total Kredit')
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
                                    return '❌ TIDAK SEIMBANG!!
                                           ' . $higher . ' lebih Rp ' . number_format($diff, 0, ',', '.');
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
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

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

                // === KOLOM BARU YANG DITAMBAHKAN ===

                // 1. Kode/Nama Akun (gabung 1 kolom)
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

                                            $lines[] = '<span class="font-mono text-xs bg-blue-50 text-blue-700 px-1 py-0.5 rounded">[' .
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

                // 2. Debet/nominal
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

                // 3. Kredit/nominal
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

                // 4. Keterangan detail
                Tables\Columns\TextColumn::make('keterangan_detail')
                    ->label('Keterangan Detail')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record) {
                                return '<span class="text-gray-400">No Record</span>';
                            }

                            // Load details if not loaded
                            if (!$record->relationLoaded('details')) {
                                $record->load('details');
                            }

                            $details = $record->details;
                            if (!$details || $details->isEmpty()) {
                                return '<span class="text-gray-400">No Details</span>';
                            }

                            $lines = [];
                            foreach ($details as $detail) {
                                try {
                                    if ($detail) {
                                        $desc = $detail->description ?? 'No Description';
                                        $lines[] = '<span class="text-gray-700">' . e($desc) . '</span>';
                                    }
                                } catch (\Exception $e) {
                                    $lines[] = '<span class="text-red-400">Error loading description</span>';
                                }
                            }

                            return empty($lines) ?
                                '<span class="text-gray-400">No Descriptions</span>' :
                                implode('<br>', $lines);
                        } catch (\Exception $e) {
                            return '<span class="text-red-400">Error: ' . e($e->getMessage()) . '</span>';
                        }
                    })
                    ->html()
                    ->wrap()
                    ->searchable()
                    ->toggleable(),

                // 5. Balance Status
                Tables\Columns\TextColumn::make('balance_status')
                    ->label('Balance Status')
                    ->formatStateUsing(function ($state, $record): string {
                        try {
                            if (!$record) {
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">No Record</span>';
                            }

                            // Load details if not loaded
                            if (!$record->relationLoaded('details')) {
                                $record->load('details');
                            }

                            $details = $record->details;
                            if (!$details || $details->isEmpty()) {
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">No Data</span>';
                            }

                            $totalDebit = 0;
                            $totalCredit = 0;

                            foreach ($details as $detail) {
                                try {
                                    if ($detail) {
                                        $totalDebit += (float)($detail->debit ?? 0);
                                        $totalCredit += (float)($detail->credit ?? 0);
                                    }
                                } catch (\Exception $e) {
                                    // Skip this detail if error
                                    continue;
                                }
                            }

                            $isBalanced = abs($totalDebit - $totalCredit) < 0.01;

                            if ($isBalanced) {
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">✅ Seimbang</span>';
                            } else {
                                $diff = abs($totalDebit - $totalCredit);
                                $diffText = 'Rp ' . number_format($diff, 0, ',', '.');
                                return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">❌ Selisih: ' . $diffText . '</span>';
                            }
                        } catch (\Exception $e) {
                            return '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Error</span>';
                        }
                    })
                    ->html()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Dibuat')
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
                        'reversed' => 'Dibatalkan',
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
                        ->label('Edit Jurnal')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn(Journal $record) => $record->status === 'draft'),

                    Tables\Actions\Action::make('print_journal')
                        ->label('🖨️ Print Jurnal')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->action(function (Journal $record) {
                            return static::printJournal($record);
                        }),

                    Tables\Actions\Action::make('post')
                        ->label('Post Jurnal')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(Journal $record) => $record->status === 'draft' && $record->is_balanced)
                        ->requiresConfirmation()
                        ->action(fn(Journal $record) => $record->post()),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(Journal $record) => $record->status === 'draft'),
                ])
                    ->label('Aksi')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_report')
                    ->label('Laporan Jurnal Umum')
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('date_from')
                                ->label('Dari Tanggal')
                                ->required()
                                ->default(now()->startOfMonth())
                                ->native(false),
                            Forms\Components\DatePicker::make('date_to')
                                ->label('Sampai Tanggal')
                                ->required()
                                ->default(now()->endOfMonth())
                                ->native(false),
                        ]),
                        Forms\Components\Select::make('status_filter')
                            ->label('Filter Status')
                            ->options([
                                'all' => 'Semua Status',
                                'draft' => 'Draft Saja',
                                'posted' => 'Posted Saja',
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return static::generateJournalReport($data);
                    }),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Jurnal')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('reference')
                                    ->label('No. Referensi')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('transaction_date')
                                    ->label('Tanggal')
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
                                    ->label('Total')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('createdBy.name')
                                    ->label('Dibuat Oleh'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Detail Transaksi')
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

                Infolists\Components\Section::make('Ringkasan')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_debit')
                                    ->label('Total Debit')
                                    ->money('IDR')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('total_credit')
                                    ->label('Total Kredit')
                                    ->money('IDR')
                                    ->color('danger'),

                                Infolists\Components\TextEntry::make('is_balanced')
                                    ->label('Balance Status')
                                    ->formatStateUsing(fn($state) => $state ? 'SEIMBANG' : 'TIDAK SEIMBANG')
                                    ->color(fn($state) => $state ? 'success' : 'danger'),
                            ]),
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
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'view' => Pages\ViewJournal::route('/{record}'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }

    // Helper methods
    private static function getNomorBantuOptionsGrouped()
    {
        return cache()->remember('nomor_bantu_grouped_options', 1800, function () {
            return NomorBantu::active()
                ->with(['rekening.kelompok'])
                ->get()
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
                        return [$n->id => "[$code] {$n->nm_bantu}"];
                    });
                });
        });
    }

    private static function calculateAccountBalance(int $nomorBantuId): float
    {
        // Get opening balance (debit - credit)
        $openingDebit = OpeningBalance::where('nomor_bantu_id', $nomorBantuId)
            ->where('is_confirmed', true)
            ->sum('debit_balance') ?? 0;

        $openingCredit = OpeningBalance::where('nomor_bantu_id', $nomorBantuId)
            ->where('is_confirmed', true)
            ->sum('credit_balance') ?? 0;

        $openingBalance = $openingDebit - $openingCredit;

        // Get total debits from posted journals
        $totalDebits = JournalDetail::where('nomor_bantu_id', $nomorBantuId)
            ->whereHas('journal', function ($query) {
                $query->where('status', 'posted');
            })
            ->sum('debit') ?? 0;

        // Get total credits from posted journals
        $totalCredits = JournalDetail::where('nomor_bantu_id', $nomorBantuId)
            ->whereHas('journal', function ($query) {
                $query->where('status', 'posted');
            })
            ->sum('credit') ?? 0;

        // Calculate current balance
        return $openingBalance + $totalDebits - $totalCredits;
    }

    private static function getSelectableNomorBantuOptionsGrouped()
    {
        return cache()->remember('selectable_nomor_bantu_options_with_balance', 1800, function () {
            return NomorBantu::active()
                ->with(['rekening.kelompok', 'openingBalances', 'journalDetails'])
                ->get()
                ->filter(function ($nomorBantu) {
                    return static::isValidAccountForJournal($nomorBantu->id);
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

                        // Calculate current balance
                        $currentBalance = static::calculateAccountBalance($n->id);
                        $kelompokNo = $n->rekening->kelompok->no_kel;

                        // Determine indicator and balance text
                        $indicator = '';
                        $balanceText = '';

                        // Accounts that require opening balance
                        $requiresOpeningBalance = in_array($kelompokNo, [10, 20, 30, 40, 70]);

                        if ($requiresOpeningBalance) {
                            if (abs($currentBalance) > 0.01) {
                                $indicator = '💰'; // Has balance
                                $balanceText = 'Saldo: Rp ' . number_format(abs($currentBalance), 0, ',', '.');
                            } else {
                                $indicator = '⚖️'; // Zero balance but valid account
                                $balanceText = 'Saldo: Rp 0';
                            }
                        } else {
                            // Operational accounts
                            if (abs($currentBalance) > 0.01) {
                                $indicator = '💰'; // Has balance
                                $balanceText = 'Saldo: Rp ' . number_format(abs($currentBalance), 0, ',', '.');
                            } else {
                                $indicator = '🆕'; // Can start from zero
                                $balanceText = 'Siap Digunakan';
                            }
                        }

                        // Format dengan line break untuk tampilan yang lebih rapi
                        $displayText = '<div style="line-height: 1.4;">
                            <div style="font-weight: 600; color: #374151;">[' . $code . '] ' . $n->nm_bantu . '</div>
                            <div style="font-size: 0.875rem; color: #6b7280; margin-top: 2px;">' . $indicator . ' ' . $balanceText . '</div>
                        </div>';

                        return [$n->id => $displayText];
                    });
                });
        });
    }

    private static function getAllNomorBantuOptionsGrouped()
    {
        return cache()->remember('all_nomor_bantu_options_with_indicators', 1800, function () {
            return NomorBantu::active()
                ->with(['rekening.kelompok', 'openingBalances', 'journalDetails'])
                ->get()
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

                        // Calculate current balance
                        $currentBalance = static::calculateAccountBalance($n->id);
                        $kelompokNo = $n->rekening->kelompok->no_kel;

                        // Determine if account requires opening balance
                        $requiresOpeningBalance = in_array($kelompokNo, [10, 20, 30, 40, 70]);

                        // Get balance info
                        $hasOpeningBalance = $n->openingBalances()->where('is_confirmed', true)->exists();
                        $hasTransactions = $n->journalDetails()->whereHas('journal', function ($q) {
                            $q->where('status', 'posted');
                        })->exists();

                        // Determine indicator and status
                        $indicator = '';
                        $isSelectable = true;
                        $balanceText = '';

                        if ($requiresOpeningBalance) {
                            if ($hasOpeningBalance || $hasTransactions) {
                                if (abs($currentBalance) > 0.01) {
                                    $indicator = '💰'; // Has balance
                                    $balanceText = ' (Saldo: Rp ' . number_format(abs($currentBalance), 0, ',', '.') . ')';
                                } else {
                                    $indicator = '⚖️'; // Zero balance but valid account
                                    $balanceText = ' (Saldo: Rp 0)';
                                }
                            } else {
                                $indicator = '❌'; // No opening balance, cannot use
                                $balanceText = ' (Tidak ada saldo awal)';
                                $isSelectable = false;
                            }
                        } else {
                            // Accounts that can be used directly (operational accounts)
                            if (abs($currentBalance) > 0.01) {
                                $indicator = '�'; // Has balance
                                $balanceText = ' (Saldo: Rp ' . number_format(abs($currentBalance), 0, ',', '.') . ')';
                            } else {
                                $indicator = '🆕'; // Can start from zero
                                $balanceText = ' (Baru/Kosong)';
                            }
                        }

                        $displayText = "{$indicator} [$code] {$n->nm_bantu}{$balanceText}";

                        // If not selectable, add to display text and disable
                        if (!$isSelectable) {
                            $displayText = '<span style="color: #999; text-decoration: line-through;">' . $displayText . '</span>';
                        }

                        return [$n->id => $displayText];
                    });
                });
        });
    }

    private static function getValidNomorBantuOptionsGrouped()
    {
        return cache()->remember('valid_nomor_bantu_options', 1800, function () {
            return NomorBantu::active()
                ->with(['rekening.kelompok', 'openingBalances', 'journalDetails'])
                ->get()
                ->filter(function ($nomorBantu) {
                    return static::isValidAccountForJournal($nomorBantu->id);
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

                        // Add validation indicator based on account type
                        $kelompokNo = $n->rekening->kelompok->no_kel;
                        $indicator = '';

                        // Accounts that require opening balance
                        $requiresOpeningBalance = in_array($kelompokNo, [10, 20, 30, 40, 70]);

                        if ($requiresOpeningBalance) {
                            $hasOpeningBalance = $n->openingBalances()->exists();
                            $hasTransactions = $n->journalDetails()->exists();

                            if ($hasOpeningBalance) {
                                $indicator = '✅ ';
                            } elseif ($hasTransactions) {
                                $indicator = '📝 ';
                            }
                        } else {
                            // Accounts that can be used directly
                            $indicator = '🔄 ';
                        }

                        return [$n->id => "{$indicator}[$code] {$n->nm_bantu}"];
                    });
                });
        });
    }

    private static function isValidAccountForJournal(int $nomorBantuId): bool
    {
        if (!$nomorBantuId) {
            return false;
        }

        $nomorBantu = NomorBantu::with(['rekening.kelompok'])->find($nomorBantuId);
        if (!$nomorBantu || !$nomorBantu->rekening || !$nomorBantu->rekening->kelompok) {
            return false;
        }

        $kelompokNo = $nomorBantu->rekening->kelompok->no_kel;

        // Tentukan akun yang WAJIB ada saldo awal vs yang bisa langsung digunakan
        $accountsRequireOpeningBalance = [
            10, // Aktiva Lancar (Kas, Bank, Piutang, dll)
            20, // Investasi Jk. Panjang
            30, // Aktiva Tetap
            40, // Aktiva Lain-lain
            70, // Modal dan Cadangan
        ];

        $accountsCanUseDirectly = [
            50, // Kewajiban Jk. Pendek (bisa mulai dari 0)
            60, // Kewajiban Jk. Panjang (bisa mulai dari 0)
            62, // Kewajiban Lain-lain (bisa mulai dari 0)
            80, // Pendapatan (dimulai dari transaksi)
            88, // Pendapatan Diluar Usaha (dimulai dari transaksi)
            91, // Biaya Sumber Air (dimulai dari transaksi)
            92, // Biaya pengolahan Air (dimulai dari transaksi)
            93, // Biaya Transmisi dan Distribusi (dimulai dari transaksi)
            94, // Biaya Air Limbah (dimulai dari transaksi)
            96, // Biaya Administrasi dan Umum (dimulai dari transaksi)
            98, // Biaya Diluar Usaha (dimulai dari transaksi)
            99, // Kerugian Luar Biasa (dimulai dari transaksi)
            41, // Aktiva Lain-lain Berwujud (bisa dimulai dari 0)
            42, // Aktiva Tak Berwujud (bisa dimulai dari 0)
            45, // aset program (bisa dimulai dari 0)
        ];

        // Jika akun bisa digunakan langsung tanpa saldo awal
        if (in_array($kelompokNo, $accountsCanUseDirectly)) {
            return true; // Akun operasional selalu bisa digunakan
        }

        // Jika akun yang wajib ada saldo awal, cek opening balance atau transaksi sebelumnya
        if (in_array($kelompokNo, $accountsRequireOpeningBalance)) {
            $hasOpeningBalance = OpeningBalance::where('nomor_bantu_id', $nomorBantuId)
                ->where('is_confirmed', true)
                ->exists();

            $hasTransactions = JournalDetail::where('nomor_bantu_id', $nomorBantuId)
                ->whereHas('journal', function ($query) {
                    $query->where('status', 'posted');
                })
                ->exists();

            // Untuk akun yang wajib ada saldo awal, harus ada opening balance atau sudah pernah ada transaksi
            if (!($hasOpeningBalance || $hasTransactions)) {
                return false;
            }

            // Additional check: untuk akun yang sudah ada opening balance atau transaksi,
            // pastikan saldo tidak kosong (kecuali memang boleh 0 seperti kas)
            // Note: Untuk kemudahan, kita izinkan saldo 0 juga
            return true;
        }

        // Default: akun bisa digunakan (untuk akun yang tidak terdefinisi di atas)
        return true;
    }

    private static function calculateTotal(Get $get, string $type): float
    {
        return collect($get('simple_entries') ?? [])
            ->where('account_type', $type)
            ->sum(function ($item) {
                $amount = $item['amount'] ?? 0;

                // Handle string input (remove formatting)
                if (is_string($amount)) {
                    $amount = str_replace(['.', ',', ' ', 'Rp'], '', $amount);
                }

                // Convert to float and ensure it's numeric
                return is_numeric($amount) ? (float)$amount : 0;
            });
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate accounts first
        foreach ($data['simple_entries'] ?? [] as $entry) {
            $nomorBantuId = $entry['nomor_bantu_id'] ?? null;
            if ($nomorBantuId && !static::isValidAccountForJournal($nomorBantuId)) {
                $nomorBantu = NomorBantu::find($nomorBantuId);
                $accountName = $nomorBantu ? $nomorBantu->nm_bantu : 'Akun tidak ditemukan';

                throw ValidationException::withMessages([
                    'data.simple_entries' => "Akun '{$accountName}' belum memiliki saldo awal atau belum pernah digunakan dalam transaksi. Silakan setup saldo awal terlebih dahulu atau gunakan akun yang sudah aktif.",
                ]);
            }
        }

        $year = now()->format('Y');
        $month = now()->format('m');
        $last = Journal::whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->count();
        $data['reference'] = "JU/$year/$month/" . str_pad($last + 1, 3, '0', STR_PAD_LEFT);

        // Process amounts - clean and convert to float
        $debit = 0;
        $credit = 0;

        foreach ($data['simple_entries'] ?? [] as &$entry) {
            $amount = $entry['amount'] ?? 0;

            // Clean amount formatting
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

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Validate accounts first
        foreach ($data['simple_entries'] ?? [] as $entry) {
            $nomorBantuId = $entry['nomor_bantu_id'] ?? null;
            if ($nomorBantuId && !static::isValidAccountForJournal($nomorBantuId)) {
                $nomorBantu = NomorBantu::find($nomorBantuId);
                $accountName = $nomorBantu ? $nomorBantu->nm_bantu : 'Akun tidak ditemukan';

                throw ValidationException::withMessages([
                    'data.simple_entries' => "Akun '{$accountName}' belum memiliki saldo awal atau belum pernah digunakan dalam transaksi. Silakan setup saldo awal terlebih dahulu atau gunakan akun yang sudah aktif.",
                ]);
            }
        }

        // Process amounts - clean and convert to float
        $debit = 0;
        $credit = 0;

        foreach ($data['simple_entries'] ?? [] as &$entry) {
            $amount = $entry['amount'] ?? 0;

            // Clean amount formatting
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

    public static function generateJournalReport(array $data): StreamedResponse
    {
        $query = static::getEloquentQuery()
            ->with([
                'details.nomorBantu.rekening.kelompok',
                'createdBy',
                'company'
            ])
            ->whereBetween('transaction_date', [$data['date_from'], $data['date_to']]);

        if ($data['status_filter'] !== 'all') {
            $query->where('status', $data['status_filter']);
        }

        $journals = $query->orderBy('transaction_date', 'desc')->get();

        $pdf = Pdf::loadView('reports.journal-report', [
            'journals' => $journals,
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'status_filter' => $data['status_filter'],
            'title' => 'Laporan Jurnal Umum',
        ]);

        $filename = 'laporan-jurnal-umum-' . $data['date_from'] . '-to-' . $data['date_to'] . '.pdf';

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public static function printJournal(Journal $record): StreamedResponse
    {
        $record->load([
            'details.nomorBantu.rekening.kelompok',
            'createdBy',
            'company'
        ]);

        $pdf = Pdf::loadView('reports.journal-print', [
            'journal' => $record,
            'title' => 'Jurnal Umum',
        ]);

        $filename = 'jurnal-umum-' . $record->reference . '.pdf';

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\JournalStatsWidget::class,
        ];
    }
}
