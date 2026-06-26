<?php

namespace App\Filament\Resources\Documenti;

use App\Filament\Concerns\HasAziendaScope;
use App\Models\Documento;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentoResource extends Resource
{
    use HasAziendaScope;

    protected static ?string $model = Documento::class;

    protected static ?string $slug = 'documenti';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documenti';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return 'Documenti';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Archivio';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('azienda.nome')
                    ->label('Azienda')->sortable()
                    ->visible(auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('nome_file')->searchable(),
                Tables\Columns\TextColumn::make('codice_fiscale')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dipendente')->placeholder('— non trovato —'),
                Tables\Columns\TextColumn::make('cartellaMese.label')->label('Mese'),
                Tables\Columns\IconColumn::make('utente_non_trovato')
                    ->label('Problemi')->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')->trueColor('warning'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('azienda_id')
                    ->label('Azienda')
                    ->relationship('azienda', 'nome')
                    ->visible(auth()->user()?->isSuperAdmin()),
                Tables\Filters\SelectFilter::make('cartella_mese_id')
                    ->relationship('cartellaMese', 'label')->label('Mese'),
                Tables\Filters\TernaryFilter::make('utente_non_trovato')
                    ->label('Solo problemi'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return (new static)->scopePerAziende(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumenti::route('/'),
        ];
    }
}
