<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpeningBalanceResource\Pages;
use App\Models\OpeningBalance;
use App\Models\NomorBantu;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OpeningBalanceResource extends Resource
{
    protected static ?string $model = OpeningBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Saldo Awal';

    protected static ?string $navigationGroup = 'Setup Saldo Awal';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 1;

    protected static ?string $pluralModelLabel = 'Saldo Awal';

    protected static ?string $slug = 'saldo-awal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === INFO & PANDUAN ===
                Forms\Components\Section::make('Panduan Saldo Awal')
                    ->description('Masukkan saldo awal untuk semua akun. Total Aktiva harus sama dengan Total Pasiva + Ekuitas.')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content('
                                **📌 Tips:**
                                - Input saldo awal sesuai Neraca periode sebelumnya
                                - Aktiva (Kelompok 10-45) = Sisi Debit
                                - Pasiva & Ekuitas (Kelompok 50-70) = Sisi Kredit
                                - Sistem akan validasi balance otomatis
                            ')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // === DATA DASAR ===
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('company_id')
                                ->label('Perusahaan')
                                ->options(Company::pluck('name', 'id'))
                                ->required()
                                ->default(Company::first()?->id)
                                ->live(),

                            Forms\Components\DatePicker::make('as_of_date')
                                ->label('Tanggal Saldo')
                                ->default(now()->startOfYear())
                                ->native(false)
                                ->required(),
                        ]),
                    ]),

                // === PILIH AKUN ===
                Forms\Components\Section::make('🔍 Pilih Akun')
                    ->schema([
                        Forms\Components\Select::make('nomor_bantu_id')
                            ->label('Akun (SAKEP)')
                            ->placeholder('Cari dan pilih akun...')
                            ->options(function () {
                                return NomorBantu::with(['rekening.kelompok'])
                                    ->get()
                                    ->sortBy(function ($item) {
                                        return $item->rekening->kelompok->no_kel . $item->rekening->no_rek . $item->no_bantu;
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
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $account = NomorBantu::with(['rekening.kelompok'])->find($state);
                                    if ($account) {
                                        // Auto-suggest based on account type
                                        $kelompok = $account->rekening->kelompok->no_kel;

                                        if (in_array($kelompok, ['10', '20', '30', '40', '41', '42', '45'])) {
                                            // Aktiva - default to debit
                                            $set('description', "Saldo awal {$account->nm_bantu}");
                                        } else {
                                            // Pasiva/Ekuitas - default to credit
                                            $set('description', "Saldo awal {$account->nm_bantu}");
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                // === INPUT SALDO ===
                Forms\Components\Section::make('💰 Input Saldo')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('debit_balance')
                                ->label('➕ Saldo Debit')
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->default(0)
                                ->live()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'autocomplete' => 'off',
                                    'style' => 'text-align: right;',
                                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, \'\').replace(/\B(?=(\d{3})+(?!\d))/g, \'.\');',
                                ])
                                ->dehydrateStateUsing(function ($state) {
                                    if (!$state) return 0;
                                    return (float) preg_replace('/[^0-9]/', '', $state);
                                })
                                ->formatStateUsing(function ($state) {
                                    if (!$state) return '';
                                    return number_format((float)$state, 0, '', '.');
                                }),

                            Forms\Components\TextInput::make('credit_balance')
                                ->label('➖ Saldo Kredit')
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->default(0)
                                ->live()
                                ->extraAttributes([
                                    'inputmode' => 'numeric',
                                    'autocomplete' => 'off',
                                    'style' => 'text-align: right;',
                                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, \'\').replace(/\B(?=(\d{3})+(?!\d))/g, \'.\');',
                                ])
                                ->dehydrateStateUsing(function ($state) {
                                    if (!$state) return 0;
                                    return (float) preg_replace('/[^0-9]/', '', $state);
                                })
                                ->formatStateUsing(function ($state) {
                                    if (!$state) return '';
                                    return number_format((float)$state, 0, '', '.');
                                }),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->placeholder('Keterangan saldo awal...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // === VALIDASI ===
                Forms\Components\Section::make('⚖️ Validasi')
                    ->schema([
                        Forms\Components\Placeholder::make('validation_note')
                            ->label('Catatan Validasi')
                            ->content('
                                - Hanya isi **SALAH SATU**: Debit ATAU Kredit (tidak boleh keduanya)
                                - **Aktiva** (Kelompok 10-45) → Isi Debit
                                - **Pasiva & Ekuitas** (Kelompok 50-70) → Isi Kredit
                                - Setelah selesai input semua akun, cek balance di halaman List
                            ')
                            ->columnSpanFull(),
                    ])
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('as_of_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sakep_code')
                    ->label('Kode')
                    ->getStateUsing(
                        fn(OpeningBalance $record) =>
                        $record->nomorBantu?->rekening?->kelompok?->no_kel .
                            $record->nomorBantu?->rekening?->no_rek .
                            str_pad($record->nomorBantu?->no_bantu, 2, '0', STR_PAD_LEFT) ?? '-'
                    )
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nomorBantu.nm_bantu')
                    ->label('Nama Akun')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('kelompok_type')
                    ->label('Tipe')
                    ->getStateUsing(function (OpeningBalance $record) {
                        $kelompok = $record->nomorBantu?->rekening?->kelompok?->no_kel;
                        return match (true) {
                            in_array($kelompok, ['10', '20', '30', '40', '41', '42', '45']) => 'Aktiva',
                            in_array($kelompok, ['50', '60', '62']) => 'Pasiva',
                            in_array($kelompok, ['70']) => 'Ekuitas',
                            default => 'Lainnya',
                        };
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Aktiva' => 'success',
                        'Pasiva' => 'warning',
                        'Ekuitas' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('debit_balance')
                    ->label('Debit')
                    ->formatStateUsing(fn($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->alignRight()
                    ->color('success'),

                Tables\Columns\TextColumn::make('credit_balance')
                    ->label('Kredit')
                    ->formatStateUsing(fn($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->alignRight()
                    ->color('danger'),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->options(Company::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('kelompok_type')
                    ->label('Tipe Akun')
                    ->options([
                        'aktiva' => 'Aktiva',
                        'pasiva' => 'Pasiva',
                        'ekuitas' => 'Ekuitas',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $kelompokCodes = match ($data['value']) {
                                'aktiva' => ['10', '20', '30', '40', '41', '42', '45'],
                                'pasiva' => ['50', '60', '62'],
                                'ekuitas' => ['70'],
                                default => [],
                            };

                            $query->whereHas('nomorBantu.rekening.kelompok', function ($q) use ($kelompokCodes) {
                                $q->whereIn('no_kel', $kelompokCodes);
                            });
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('check_balance')
                    ->label('Cek Balance')
                    ->icon('heroicon-o-scale')
                    ->color('info')
                    ->action(function () {
                        $totalDebit = OpeningBalance::sum('debit_balance');
                        $totalCredit = OpeningBalance::sum('credit_balance');
                        $diff = abs($totalDebit - $totalCredit);

                        // Get summary by type
                        $aktiva = OpeningBalance::whereHas('nomorBantu.rekening.kelompok', function ($q) {
                            $q->whereIn('no_kel', ['10', '20', '30', '40', '41', '42', '45']);
                        })->sum('debit_balance');

                        $pasiva = OpeningBalance::whereHas('nomorBantu.rekening.kelompok', function ($q) {
                            $q->whereIn('no_kel', ['50', '60', '62']);
                        })->sum('credit_balance');

                        $ekuitas = OpeningBalance::whereHas('nomorBantu.rekening.kelompok', function ($q) {
                            $q->whereIn('no_kel', ['70']);
                        })->sum('credit_balance');

                        $confirmed = OpeningBalance::where('is_confirmed', true)->count();
                        $pending = OpeningBalance::where('is_confirmed', false)->count();

                        $message = "**📊 BALANCE CHECK SALDO AWAL**\n\n";
                        $message .= "**Total Overview:**\n";
                        $message .= "• Total Debit: Rp " . number_format($totalDebit, 0, ',', '.') . "\n";
                        $message .= "• Total Kredit: Rp " . number_format($totalCredit, 0, ',', '.') . "\n\n";

                        $message .= "**Detail by Category:**\n";
                        $message .= "• Aktiva: Rp " . number_format($aktiva, 0, ',', '.') . "\n";
                        $message .= "• Pasiva: Rp " . number_format($pasiva, 0, ',', '.') . "\n";
                        $message .= "• Ekuitas: Rp " . number_format($ekuitas, 0, ',', '.') . "\n";
                        $message .= "• Total Pasiva + Ekuitas: Rp " . number_format($pasiva + $ekuitas, 0, ',', '.') . "\n\n";

                        $message .= "**Status:**\n";
                        $message .= "• Dikonfirmasi: {$confirmed} akun\n";
                        $message .= "• Pending: {$pending} akun\n\n";

                        if ($diff < 1) {
                            $message .= "✅ **SEIMBANG!**\n";
                            $message .= "Saldo awal sudah benar sesuai prinsip akuntansi:\n";
                            $message .= "**Aktiva = Pasiva + Ekuitas**";
                        } else {
                            $message .= "❌ **TIDAK SEIMBANG!**\n";
                            $message .= "Selisih: Rp " . number_format($diff, 0, ',', '.') . "\n";
                            $message .= "Pastikan: **Total Debit = Total Kredit**";
                        }

                        return $message;
                    })
                    ->modalHeading('📊 Balance Check Saldo Awal')
                    ->modalContent(fn($action) => new \Illuminate\Support\HtmlString(
                        '<div class="text-sm whitespace-pre-line">' . nl2br($action->action()) . '</div>'
                    ))
                    ->modalWidth('xl'),

                Tables\Actions\Action::make('confirm_all')
                    ->label('Konfirmasi Semua')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Semua Saldo Awal')
                    ->modalSubheading('Apakah Anda yakin ingin mengkonfirmasi semua saldo awal? Pastikan balance sudah seimbang.')
                    ->action(function () {
                        // Check balance first
                        $totalDebit = OpeningBalance::sum('debit_balance');
                        $totalCredit = OpeningBalance::sum('credit_balance');
                        $diff = abs($totalDebit - $totalCredit);

                        if ($diff >= 1) {
                            throw new \Exception('Tidak dapat konfirmasi! Balance belum seimbang. Selisih: Rp ' . number_format($diff, 0, ',', '.'));
                        }

                        OpeningBalance::where('is_confirmed', false)
                            ->update([
                                'is_confirmed' => true,
                                'confirmed_by' => \Illuminate\Support\Facades\Auth::id(),
                                'confirmed_at' => now(),
                            ]);
                    })
                    ->successNotificationTitle('Semua saldo awal berhasil dikonfirmasi'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(fn(OpeningBalance $record) => !$record->is_confirmed),

                Tables\Actions\Action::make('confirm')
                    ->label('✓ Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(OpeningBalance $record) => !$record->is_confirmed)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Saldo Awal')
                    ->modalSubheading('Apakah Anda yakin ingin mengkonfirmasi saldo awal ini? Setelah dikonfirmasi, data tidak dapat diedit.')
                    ->action(function (OpeningBalance $record) {
                        $record->confirm();
                    })
                    ->successNotificationTitle('Saldo awal berhasil dikonfirmasi'),

                Tables\Actions\Action::make('unconfirm')
                    ->label('Batal Konfirmasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn(OpeningBalance $record) => $record->is_confirmed)
                    ->requiresConfirmation()
                    ->action(function (OpeningBalance $record) {
                        $record->unconfirm();
                    })
                    ->successNotificationTitle('Konfirmasi dibatalkan'),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn(OpeningBalance $record) => !$record->is_confirmed),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('confirm_selected')
                        ->label('Konfirmasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->is_confirmed) {
                                    $record->confirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Saldo awal terpilih berhasil dikonfirmasi'),

                    Tables\Actions\BulkAction::make('unconfirm_selected')
                        ->label('↶ Batal Konfirmasi Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->is_confirmed) {
                                    $record->unconfirm();
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Konfirmasi dibatalkan'),
                ]),
            ])
            ->defaultSort('as_of_date', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpeningBalances::route('/'),
            'create' => Pages\CreateOpeningBalance::route('/create'),
            'edit' => Pages\EditOpeningBalance::route('/{record}/edit'),
        ];
    }
}
