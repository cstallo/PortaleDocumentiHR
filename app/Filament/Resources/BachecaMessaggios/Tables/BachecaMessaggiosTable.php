<?php

namespace App\Filament\Resources\BachecaMessaggios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BachecaMessaggiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titolo')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('pubblicato_il')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y H:i') : 'Bozza')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                IconColumn::make('pinned')
                    ->label('In evidenza')
                    ->boolean(),

                TextColumn::make('destinatari_count')
                    ->label('Destinatari')
                    ->badge()
                    ->color('info'),

                TextColumn::make('letti_count')
                    ->label('Letti')
                    ->badge()
                    ->color('success'),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
