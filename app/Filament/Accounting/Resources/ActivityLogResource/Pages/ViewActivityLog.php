<?php

namespace App\Filament\Accounting\Resources\ActivityLogResource\Pages;

use App\Filament\Accounting\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Aktivitas')
                    ->schema([
                        Infolists\Components\TextEntry::make('log_name')
                            ->label('Modul')
                            ->badge()
                            ->color('primary'),
                            
                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi Aktivitas'),
                            
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('Tipe Data')
                            ->formatStateUsing(fn($state) => $state ? class_basename($state) : '-')
                            ->badge()
                            ->color('info'),
                            
                        Infolists\Components\TextEntry::make('subject_id')
                            ->label('ID Data'),
                            
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Detail Perubahan')
                    ->schema([
                        Infolists\Components\TextEntry::make('properties.attributes')
                            ->label('Data Baru')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '-';
                                return collect($state)->map(function ($value, $key) {
                                    return "$key: " . (is_array($value) ? json_encode($value) : $value);
                                })->join("\n");
                            })
                            ->markdown(),
                            
                        Infolists\Components\TextEntry::make('properties.old')
                            ->label('Data Lama')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '-';
                                return collect($state)->map(function ($value, $key) {
                                    return "$key: " . (is_array($value) ? json_encode($value) : $value);
                                })->join("\n");
                            })
                            ->markdown(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => !empty($record->properties)),
            ]);
    }
}
