<?php

namespace App\Filament\Resources\EmailLogs;

use App\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Filament\Resources\EmailLogs\Schemas\EmailLogForm;
use App\Models\EmailLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    public static function form(Schema $schema): Schema
    {
        return EmailLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('stato')
                    ->badge()
                    ->color(fn (string $state) => $state === 'inviata' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'benvenuto_dipendente' => 'Benvenuto dipendente',
                        'benvenuto_hr' => 'Benvenuto HR',
                        'nuovo_documento' => 'Nuovo documento',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('destinatario')->searchable(),
                Tables\Columns\TextColumn::make('azienda.nome')->label('Azienda')->placeholder('—'),
                Tables\Columns\TextColumn::make('tentativi')->alignCenter(),
                Tables\Columns\TextColumn::make('errore')
                    ->label('Errore')->placeholder('—')->wrap()->limit(80)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(),
            ])
            ->filters([
            Tables\Filters\SelectFilter::make('stato')
                    ->options(['inviata' => 'Inviata', 'fallita' => 'Fallita']),
            Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'benvenuto_dipendente' => 'Benvenuto dipendente',
                        'benvenuto_hr' => 'Benvenuto HR',
                        'nuovo_documento' => 'Nuovo documento',
                    ]),
        ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
        ];
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-envelope';
    }

    public static function getNavigationLabel(): string
    {
        return 'Registro email';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Importazione';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    // Scope multi-azienda: HR vede solo i log delle sue aziende
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ids = $user->aziendeGestite()->pluck('aziende.id');

        return $query->whereIn('azienda_id', $ids);
    }
}
