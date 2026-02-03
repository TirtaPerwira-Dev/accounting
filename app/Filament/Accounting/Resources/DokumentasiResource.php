<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\DokumentasiResource\Pages;
use App\Models\Dokumentasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class DokumentasiResource extends Resource
{
    protected static ?string $model = Dokumentasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Dokumentasi';

    protected static ?string $navigationGroup = 'Bantuan';

    protected static ?int $navigationSort = 100;

    protected static ?string $pluralModelLabel = 'Dokumentasi';

    // Disable Create, Edit, Delete di Accounting Panel
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form kosong karena read-only
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informasi Dokumen')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Components\TextEntry::make('judul')
                                ->label('Judul')
                                ->size('lg')
                                ->weight('bold')
                                ->columnSpanFull(),

                            Components\TextEntry::make('kategori')
                                ->label('Kategori')
                                ->badge()
                                ->color('primary'),

                            Components\TextEntry::make('is_manual_book')
                                ->label('Tipe Dokumen')
                                ->formatStateUsing(fn($state) => $state ? 'Manual Book' : 'Dokumentasi')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'info'),

                            Components\TextEntry::make('deskripsi')
                                ->label('Deskripsi')
                                ->columnSpanFull(),
                        ]),
                    ]),

                Components\Section::make('Konten')
                    ->schema([
                        Components\TextEntry::make('konten')
                            ->label('')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('File Lampiran')
                    ->schema([
                        Components\TextEntry::make('file_attachment')
                            ->label('File')
                            ->url(fn($record) => $record->file_attachment ? asset('storage/' . $record->file_attachment) : null)
                            ->openUrlInNewTab()
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->formatStateUsing(fn($state) => $state ? 'Download File' : 'Tidak ada file')
                            ->placeholder('Tidak ada file lampiran'),
                    ])
                    ->visible(fn($record) => $record->file_attachment !== null),

                Components\Section::make('Informasi')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('createdBy.name')
                                ->label('Dibuat oleh'),

                            Components\TextEntry::make('created_at')
                                ->label('Dibuat pada')
                                ->dateTime('d/m/Y H:i'),

                            Components\TextEntry::make('published_at')
                                ->label('Dipublikasikan')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('Belum dipublikasikan'),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(60),

                Tables\Columns\BadgeColumn::make('kategori')
                    ->label('Kategori')
                    ->colors([
                        'primary' => 'Panduan',
                        'success' => 'Tutorial',
                        'warning' => 'FAQ',
                        'info' => 'SOP',
                        'danger' => 'Kebijakan',
                        'secondary' => 'Lainnya',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('is_manual_book')
                    ->label('Tipe')
                    ->formatStateUsing(fn($state) => $state ? 'Manual Book' : 'Dokumentasi')
                    ->colors([
                        'success' => fn($state) => $state === true,
                        'primary' => fn($state) => $state === false,
                    ]),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('file_attachment')
                    ->label('File')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Tgl Publikasi')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('urutan', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'Panduan' => 'Panduan',
                        'Tutorial' => 'Tutorial',
                        'FAQ' => 'FAQ',
                        'SOP' => 'SOP',
                        'Kebijakan' => 'Kebijakan',
                        'Lainnya' => 'Lainnya',
                    ]),

                Tables\Filters\SelectFilter::make('is_manual_book')
                    ->label('Tipe Dokumen')
                    ->options([
                        0 => 'Dokumentasi',
                        1 => 'Manual Book',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                // Tidak ada bulk action untuk read-only
            ])
            ->emptyStateHeading('Belum ada dokumentasi')
            ->emptyStateDescription('Dokumentasi akan ditampilkan di sini setelah dipublikasikan oleh admin')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getEloquentQuery(): Builder
    {
        // Hanya tampilkan yang sudah published
        return parent::getEloquentQuery()
            ->published()
            ->orderBy('urutan', 'asc');
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
            'index' => Pages\ListDokumentasis::route('/'),
            'view' => Pages\ViewDokumentasi::route('/{record}'),
        ];
    }
}
