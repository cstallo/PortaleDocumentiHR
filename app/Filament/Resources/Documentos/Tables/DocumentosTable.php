<?php

namespace App\Filament\Resources\Documentos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome_file')
                    ->searchable(),
                TextColumn::make('path_storage')
                    ->searchable(),
                TextColumn::make('codice_fiscale')
                    ->searchable(),
                TextColumn::make('azienda_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cartella_mese_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('import_log_id')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('utente_non_trovato')
                    ->boolean(),
                TextColumn::make('scaricato_il')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
