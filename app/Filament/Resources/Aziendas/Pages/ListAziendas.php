<?php

namespace App\Filament\Resources\Aziendas\Pages;

use App\Filament\Resources\Aziendas\AziendaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAziendas extends ListRecords
{
    protected static string $resource = AziendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
