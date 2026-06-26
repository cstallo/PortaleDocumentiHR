<?php

namespace App\Filament\Resources\BachecaMessaggios\Pages;

use App\Filament\Resources\BachecaMessaggios\BachecaMessaggioResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBachecaMessaggio extends ViewRecord
{
    protected static string $resource = BachecaMessaggioResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
