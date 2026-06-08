<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\HasAziendaScope;
use App\Filament\Resources\Users\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

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
                        $opts['hr']          = 'HR';
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
                    $ids  = (new static)->getAziendeVisibiliIds();
                    return \App\Models\Azienda::whereIn('id', $ids)->pluck('nome', 'id');
                })
                ->searchable()
                ->required()
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('role') === 'dipendente'),

            Select::make('aziendeGestite')
                ->label('Aziende gestite')
                ->multiple()
                ->relationship('aziendeGestite', 'nome')
                ->options(function () {
                    $ids = (new static)->getAziendeVisibiliIds();
                    return \App\Models\Azienda::whereIn('id', $ids)->pluck('nome', 'id');
                })
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('role') === 'hr'),

            TextInput::make('codice_fiscale')
                ->maxLength(16)
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('role') === 'dipendente'),

             TextInput::make('password')
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('role'), ['hr', 'super_admin']))
                ->required(fn (string $operation, \Filament\Schemas\Components\Utilities\Get $get) =>
                    $operation === 'create' && in_array($get('role'), ['hr', 'super_admin']))
                ->dehydrated(fn ($state) => filled($state)),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role')->badge(),
                Tables\Columns\TextColumn::make('azienda.nome')->label('Azienda')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ids = $user->aziendeGestite()->pluck('aziende.id');
        return $query->where('role', 'dipendente')->whereIn('azienda_id', $ids);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
