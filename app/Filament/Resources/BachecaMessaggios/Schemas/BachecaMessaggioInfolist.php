<?php

namespace App\Filament\Resources\BachecaMessaggios\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class BachecaMessaggioInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Messaggio')
                    ->schema([
                        TextEntry::make('titolo'),

                        TextEntry::make('corpo')
                            ->html()                      // il corpo è HTML dal RichEditor
                            ->columnSpanFull(),

                        TextEntry::make('pubblicato_il')
                            ->label('Stato')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y H:i') : 'Bozza')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),

                        IconEntry::make('pinned')
                            ->label('In evidenza')
                            ->boolean(),

                        TextEntry::make('autore.name')
                            ->label('Autore'),
                    ])
                    ->columns(2),

                Section::make('Destinatari')
                    ->schema([
                        RepeatableEntry::make('destinatari')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nome'),

                                TextEntry::make('email')
                                    ->label('Email'),

                                IconEntry::make('pivot.notifica_inviata')
                                    ->label('Notifica inviata')
                                    ->boolean(),

                                TextEntry::make('pivot.letto_il')
                                    ->label('Letto il')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state
                                        ? Carbon::parse($state)->format('d/m/Y H:i')
                                        : 'Non letto')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
