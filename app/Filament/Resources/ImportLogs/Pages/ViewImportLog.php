<?php

namespace App\Filament\Resources\ImportLogs\Pages;

use App\Filament\Resources\ImportLogs\ImportLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\RepeatableEntry;


class ViewImportLog extends ViewRecord
{
    protected static string $resource = ImportLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Riepilogo')
                ->columns(3)
                ->components([
                    TextEntry::make('azienda.nome')->label('Azienda'),
                    TextEntry::make('admin.name')->label('Caricato da')->placeholder('—'),
                    TextEntry::make('stato')->badge(),
                    TextEntry::make('zip_originale')->label('File ZIP'),
                    TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
                    TextEntry::make('completato_il')->label('Completato il')->dateTime('d/m/Y H:i')->placeholder('—'),
                    TextEntry::make('descrizione')->label('Invio')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Conteggi')
                ->columns(5)
                ->components([
                    TextEntry::make('tot_file_zip')->label('Totale file'),
                    TextEntry::make('file_elaborati')->label('Elaborati'),
                    TextEntry::make('file_errore_cf')->label('Errore CF'),
                    TextEntry::make('file_nome_non_valido')->label('Nome non valido'),
                    TextEntry::make('file_duplicati')->label('Duplicati'),
                ]),

            Section::make('File scartati')
                ->visible(fn ($record) => ! empty($record->dettaglio_errori))
                ->components([
                    RepeatableEntry::make('dettaglio_errori')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('file')->label('File')->columnSpan(2),
                            TextEntry::make('errore')
                                ->label('Problema')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'cf_non_trovato_in_azienda' => 'CF non trovato',
                                    'nome_non_conforme'         => 'Nome non valido',
                                    'duplicato'                 => 'Duplicato',
                                    default                     => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'cf_non_trovato_in_azienda' => 'danger',
                                    'nome_non_conforme'         => 'warning',
                                    'duplicato'                 => 'gray',
                                    default                     => 'gray',
                                }),
                        ]),
                ]),
        ]);
    }
}
