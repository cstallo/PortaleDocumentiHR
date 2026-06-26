<?php

namespace App\Filament\Resources\BachecaMessaggios;

use App\Filament\Resources\BachecaMessaggios\Pages\ListBachecaMessaggios;
use App\Filament\Resources\BachecaMessaggios\Pages\ViewBachecaMessaggio;
use App\Filament\Resources\BachecaMessaggios\Schemas\BachecaMessaggioInfolist;
use App\Filament\Resources\BachecaMessaggios\Tables\BachecaMessaggiosTable;
use App\Models\BachecaMessaggio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BachecaMessaggioResource extends Resource
{
    protected static ?string $model = BachecaMessaggio::class;

    protected static ?string $slug = 'bacheca-messaggi';

    protected static ?string $modelLabel = 'messaggio bacheca';

    protected static ?string $pluralModelLabel = 'messaggi bacheca';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function getNavigationLabel(): string
    {
        return 'Messaggi inviati';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Bacheca';
    }

    // la creazione avviene nella pagina ComposizioneBacheca
    public static function canCreate(): bool
    {
        return false;
    }

    // HR vede solo i propri messaggi; conteggi destinatari/letti precalcolati
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withCount([
            'destinatari',
            'destinatari as letti_count' => fn (Builder $q) => $q->whereNotNull('bacheca_destinatari.letto_il'),
        ]);

        $user = auth()->user();

        if (! $user->isSuperAdmin()) {
            $query->where('autore_id', $user->id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return BachecaMessaggiosTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BachecaMessaggioInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBachecaMessaggios::route('/'),
            'view' => ViewBachecaMessaggio::route('/{record}'),
        ];
    }
}
