<?php

namespace App\Filament\Resources\BotQueryLogs\Pages;

use App\Filament\Resources\BotQueryLogs\BotQueryLogResource;
use Filament\Resources\Pages\ListRecords;

class ListBotQueryLogs extends ListRecords
{
    protected static string $resource = BotQueryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
