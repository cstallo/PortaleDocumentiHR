<?php

namespace App\Filament\Resources\Aziendas;

use App\Models\Azienda;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AziendaResource extends Resource
{
    protected static ?string $model = Azienda::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationLabel(): string
    {
        return 'Aziende';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Holding';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nome')->required(),
            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Solo lettere minuscole, numeri e trattini')
                ->rules(['regex:/^[a-z0-9\-]+$/']),
            TextInput::make('partita_iva')->nullable(),
            TextInput::make('codice_fiscale')->nullable(),
            Textarea::make('indirizzo')->nullable(),
            Toggle::make('attiva')->default(true),

            Section::make('Dati per informativa privacy (GDPR)')
                ->description('Compaiono nell\'informativa che il dipendente di questa azienda vede nella sua area riservata.')
                ->schema([
                    TextInput::make('email_contatto')
                        ->label('Email / PEC del Titolare')
                        ->email()
                        ->helperText('Usata per i contatti e per l\'esercizio dei diritti (artt. 15-22 GDPR).'),
                    TextInput::make('responsabile_trattamento')
                        ->label('Responsabile del trattamento (fornitore IT)'),
                    TextInput::make('dpo_email')
                        ->label('Email del DPO')
                        ->email()
                        ->helperText('Lascia vuoto se non è stato nominato un DPO.'),
                ])->columns(1),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('dipendenti_count')
                    ->label('Dipendenti')
                    ->counts('dipendenti'),
                Tables\Columns\IconColumn::make('attiva')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('nome');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAziendas::route('/'),
            'create' => Pages\CreateAzienda::route('/create'),
            'edit' => Pages\EditAzienda::route('/{record}/edit'),
        ];
    }
}
