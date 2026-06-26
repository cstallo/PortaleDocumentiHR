<?php

namespace App\Filament\Resources\BachecaMessaggios\Pages;

use App\Filament\Resources\BachecaMessaggios\BachecaMessaggioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBachecaMessaggios extends ListRecords
{
    protected static string $resource = BachecaMessaggioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
