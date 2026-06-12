<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Toggle::make('bot_enabled')
                    ->label('Accesso al bot HR')
                    ->helperText('Consente a questo utente di usare l\'assistente cedolini.')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

            ]);
    }
}
