<?php

namespace App\Filament\Resources\AccountingStandardResource\RelationManagers;

use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    protected static ?string $title = 'Companies Using This Standard';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->copyable()
                    ->placeholder('Not set'),

                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('Not set'),

                Tables\Columns\TextColumn::make('journals_count')
                    ->label('Journals')
                    ->badge()
                    ->color('info')
                    ->counts('journals'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status'),

                Tables\Filters\Filter::make('has_accounts')
                    ->label('Has Chart of Accounts')
                    ->query(fn(Builder $query): Builder => $query->has('accounts'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_company')
                    ->label('View Company')
                    ->icon('heroicon-o-eye')
                    ->url(
                        fn(Company $record): string =>
                        route('filament.admin.resources.companies.view', $record)
                    )
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('name');
    }
}
