<?php

namespace App\Filament\Resources\ImportLogs\Tables;

use App\Models\ImportLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('azienda.nome')
                    ->label('Azienda')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('admin.name')
                    ->label('Caricato da')
                    ->placeholder('—'),

                TextColumn::make('zip_originale')
                    ->label('File ZIP')
                    ->limit(30)
                    ->tooltip(fn (ImportLog $r) => $r->zip_originale),
                
                TextColumn::make('descrizione')
                    ->label('Invio')
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(),


                TextColumn::make('stato')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'completato' => 'success',
                        'errore'     => 'danger',
                        'in_elaborazione'   => 'warning',
                        default      => 'gray',
                    }),

                TextColumn::make('file_elaborati')
                    ->label('Elaborati')
                    ->numeric()
                    ->badge()
                    ->color('success'),

                TextColumn::make('file_errore_cf')
                    ->label('Errore CF')
                    ->numeric(),

                TextColumn::make('file_nome_non_valido')
                    ->label('Nome non valido')
                    ->numeric(),

                TextColumn::make('file_duplicati')
                    ->label('Duplicati')
                    ->numeric(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}

