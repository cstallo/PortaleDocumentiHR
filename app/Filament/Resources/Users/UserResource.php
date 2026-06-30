<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\HasAziendaScope;
use App\Models\Azienda;
use App\Models\User;
use App\Notifications\InvitoDipendente;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Password;

class UserResource extends Resource
{
    use HasAziendaScope;

    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return 'Utenti';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Anagrafica';
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')
                ->email()->required()->unique(ignoreRecord: true)->autocomplete('off'),

            Select::make('role')
                ->options(function () use ($user) {
                    $opts = ['dipendente' => 'Dipendente'];
                    if ($user->isSuperAdmin()) {
                        $opts['hr'] = 'HR';
                        $opts['super_admin'] = 'Super Admin';
                    }

                    return $opts;
                })
                ->default('dipendente')
                ->live()
                ->required(),

            Select::make('azienda_id')
                ->label('Azienda di appartenenza')
                ->options(function () {
                    $user = auth()->user();
                    $ids = (new static)->getAziendeVisibiliIds();

                    return Azienda::whereIn('id', $ids)->pluck('nome', 'id');
                })
                ->searchable()
                ->required()
                ->visible(fn (Get $get) => $get('role') === 'dipendente'),

            Select::make('aziendeGestite')
                ->label('Aziende gestite')
                ->multiple()
                ->relationship('aziendeGestite', 'nome')
                ->options(function () {
                    $ids = (new static)->getAziendeVisibiliIds();

                    return Azienda::whereIn('id', $ids)->pluck('nome', 'id');
                })
                ->visible(fn (Get $get) => $get('role') === 'hr'),

            TextInput::make('codice_fiscale')
                ->maxLength(16)
                ->visible(fn (Get $get) => $get('role') === 'dipendente'),

            Section::make('Anagrafica dipendente')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('cognome'),
                        TextInput::make('nome'),
                        TextInput::make('matricola')->label('Matricola'),
                        TextInput::make('sede'),
                        Select::make('sesso')
                            ->options(['F' => 'Femmina', 'M' => 'Maschio']),
                        TextInput::make('luogo_nascita')->label('Luogo di nascita'),
                        DatePicker::make('data_nascita')
                            ->label('Data di nascita')
                            ->displayFormat('d/m/Y'),
                    ]),
                ])
                ->columns(1)
                ->visible(fn (Get $get) => $get('role') === 'dipendente'),

            Section::make('Rapporto di lavoro')
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('data_assunzione')
                            ->label('Data assunzione')->displayFormat('d/m/Y'),
                        DatePicker::make('data_licenziamento')
                            ->label('Data licenziamento')->displayFormat('d/m/Y'),
                        DatePicker::make('scadenza_contratto')
                            ->label('Scadenza contratto')->displayFormat('d/m/Y'),
                    ]),
                ])
                ->columns(1)
                ->visible(fn (Get $get) => $get('role') === 'dipendente'),

            DateTimePicker::make('password_impostata_il')
                ->label('Password impostata il')
                ->helperText('Si valorizza quando il dipendente imposta la password. Svuotalo per poter reinviare il link di impostazione.')
                ->visible(fn (Get $get) => $get('role') === 'dipendente'),

            TextInput::make('password')
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->visible(fn (Get $get) => in_array($get('role'), ['hr', 'super_admin']))
                ->required(fn (string $operation, Get $get) => $operation === 'create' && in_array($get('role'), ['hr', 'super_admin']))
                ->dehydrated(fn ($state) => filled($state)),

            Toggle::make('bot_enabled')
                ->label('Accesso al bot HR')
                ->helperText('Consente a questo utente di usare l\'assistente cedolini.')
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role')->badge(),
                Tables\Columns\TextColumn::make('invito_inviato_il')
                    ->label('Invito')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Non inviato')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('password_impostata_il')
                    ->label('Attivato')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('bot_enabled')
                    ->label('Bot')
                    ->boolean()
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),
                Tables\Columns\TextColumn::make('azienda.nome')->label('Azienda')->placeholder('—'),
                Tables\Columns\TextColumn::make('sede')
                    ->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('matricola')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('data_assunzione')
                    ->label('Assunto il')->date('d/m/Y')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')->date('d/m/Y')->sortable(),
            ])
            ->toolbarActions([
                BulkAction::make('reinvia_invito')
                    ->label('Invia nuovo invito')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->modalHeading('Invia nuovo invito')
                    ->modalDescription('Invia (o reinvia) la mail di benvenuto ai dipendenti selezionati. Chi ha già impostato la password riceve il link di accesso; gli altri il link per impostarla.')
                    ->action(function (Collection $records) {
                        $inviati = 0;

                        foreach ($records as $user) {
                            if (! $user->isDipendente()) {
                                continue;
                            }

                            $token = $user->password_impostata_il
                                ? null
                                : Password::broker()->createToken($user);

                            $user->notify(new InvitoDipendente($token));
                            $user->update(['invito_inviato_il' => now()]);
                            $inviati++;
                        }

                        Notification::make()
                            ->title("Invito inviato a {$inviati} dipendenti")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('name');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ids = $user->aziendeGestite()->pluck('aziende.id');

        return $query->where('role', 'dipendente')->whereIn('azienda_id', $ids);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
