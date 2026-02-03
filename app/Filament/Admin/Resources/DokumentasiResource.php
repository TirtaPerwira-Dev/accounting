<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DokumentasiResource\Pages;
use App\Models\Dokumentasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;

class DokumentasiResource extends Resource
{
    protected static ?string $model = Dokumentasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Dokumentasi & Manual Book';

    protected static ?string $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 10;

    protected static ?string $pluralModelLabel = 'Dokumentasi & Manual Book';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('judul')
                                ->label('Judul')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Select::make('kategori')
                                ->label('Kategori')
                                ->options([
                                    'Panduan' => 'Panduan',
                                    'Tutorial' => 'Tutorial',
                                    'FAQ' => 'FAQ',
                                    'SOP' => 'SOP',
                                    'Kebijakan' => 'Kebijakan',
                                    'Lainnya' => 'Lainnya',
                                ])
                                ->searchable()
                                ->required(),

                            Forms\Components\Toggle::make('is_manual_book')
                                ->label('Tipe Dokumen')
                                ->helperText('ON = Manual Book, OFF = Dokumentasi')
                                ->default(false)
                                ->inline(false),

                            Forms\Components\TextInput::make('urutan')
                                ->label('Urutan Tampilan')
                                ->numeric()
                                ->default(0)
                                ->helperText('Semakin kecil angka, semakin di atas'),

                            Forms\Components\Toggle::make('is_published')
                                ->label('Status Publikasi')
                                ->helperText('ON = Dipublikasikan, OFF = Draft')
                                ->default(false)
                                ->inline(false)
                                ->live()
                                ->afterStateUpdated(fn($state, Forms\Set $set) => 
                                    $state ? $set('published_at', now()) : $set('published_at', null)
                                ),
                        ]),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi Singkat')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Konten')
                    ->schema([
                        RichEditor::make('konten')
                            ->label('Konten Dokumentasi')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull()
                            ->required(),

                        FileUpload::make('file_attachment')
                            ->label('File Lampiran')
                            ->helperText('Upload file PDF, Word, Excel sebagai lampiran (opsional)')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->directory('dokumentasi-files')
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\Placeholder::make('created_info')
                            ->label('Dibuat oleh')
                            ->content(fn(?Dokumentasi $record) => $record?->createdBy?->name ?? '-'),

                        Forms\Components\Placeholder::make('updated_info')
                            ->label('Terakhir diubah oleh')
                            ->content(fn(?Dokumentasi $record) => $record?->updatedBy?->name ?? '-'),

                        Forms\Components\Placeholder::make('published_info')
                            ->label('Tanggal Publikasi')
                            ->content(fn(?Dokumentasi $record) => $record?->published_at?->format('d/m/Y H:i') ?? '-'),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $record !== null)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn() => auth()->id()),

                Forms\Components\Hidden::make('updated_by')
                    ->default(fn() => auth()->id()),

                Forms\Components\Hidden::make('published_at'),
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
                    ->limit(50),

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

                Tables\Columns\TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Tgl Publikasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Status Publikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Dipublikasikan')
                    ->falseLabel('Draft'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
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
            'index' => Pages\ListDokumentasis::route('/'),
            'create' => Pages\CreateDokumentasi::route('/create'),
            'view' => Pages\ViewDokumentasi::route('/{record}'),
            'edit' => Pages\EditDokumentasi::route('/{record}/edit'),
        ];
    }
}
