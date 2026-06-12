<?php

namespace App\Filament\Resources\BotQueryLogs;

use App\Filament\Resources\BotQueryLogs\Pages\ListBotQueryLogs;
use App\Filament\Resources\BotQueryLogs\Tables\BotQueryLogsTable;
use App\Models\BotQueryLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BotQueryLogResource extends Resource
{
    protected static ?string $model = BotQueryLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Strumenti';

    protected static ?string $navigationLabel = 'Richieste bot';

    protected static ?string $modelLabel = 'richiesta';

    protected static ?string $pluralModelLabel = 'richieste bot';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $daGestire = BotQueryLog::query()
            ->where('esito', 'non_risolta')
            ->where('gestita', false)
            ->count();

        return $daGestire > 0 ? (string) $daGestire : null;
    }

    public static function table(Table $table): Table
    {
        return BotQueryLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBotQueryLogs::route('/'),
        ];
    }
}
