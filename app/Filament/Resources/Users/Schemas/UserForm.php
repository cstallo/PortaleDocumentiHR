<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Select::make('role')
                    ->options(['super_admin' => 'Super admin', 'hr' => 'Hr', 'dipendente' => 'Dipendente'])
                    ->default('dipendente')
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Textarea::make('two_factor_secret')
                    ->columnSpanFull(),
                Textarea::make('two_factor_recovery_codes')
                    ->columnSpanFull(),
                DateTimePicker::make('two_factor_confirmed_at'),
                TextInput::make('azienda_id')
                    ->numeric(),
                TextInput::make('codice_fiscale'),
                Section::make('Anagrafica dipendente')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('cognome'),
                            TextInput::make('nome'),
                            TextInput::make('matricola')
                                ->label('Matricola'),
                            TextInput::make('sede'),
                            Select::make('sesso')
                                ->options(['F' => 'Femmina', 'M' => 'Maschio']),
                            TextInput::make('luogo_nascita')
                                ->label('Luogo di nascita'),
                            DatePicker::make('data_nascita')
                                ->label('Data di nascita')
                                ->displayFormat('d/m/Y'),
                        ]),
                    ])
                    ->columns(1)
                    ->visible(fn (Get $get): bool => $get('role') === 'dipendente'),

                Section::make('Rapporto di lavoro')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('data_assunzione')
                                ->label('Data assunzione')
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('data_licenziamento')
                                ->label('Data licenziamento')
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('scadenza_contratto')
                                ->label('Scadenza contratto')
                                ->displayFormat('d/m/Y'),
                        ]),
                    ])
                    ->columns(1)
                    ->visible(fn (Get $get): bool => $get('role') === 'dipendente'),

                Toggle::make('bot_enabled')
                    ->label('Accesso al bot HR')
                    ->helperText('Consente a questo utente di usare l\'assistente cedolini.')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

            ]);
    }
}
