<?php

namespace App\Filament\Resources\Documentos\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome_file')
                    ->required(),
                TextInput::make('path_storage')
                    ->required(),
                TextInput::make('codice_fiscale')
                    ->required(),
                TextInput::make('azienda_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('cartella_mese_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                TextInput::make('import_log_id')
                    ->numeric(),
                Toggle::make('utente_non_trovato')
                    ->required(),
                DateTimePicker::make('scaricato_il'),
            ]);
    }
}
