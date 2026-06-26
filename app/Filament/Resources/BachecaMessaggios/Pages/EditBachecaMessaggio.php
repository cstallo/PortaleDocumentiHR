<?php

namespace App\Filament\Resources\BachecaMessaggios\Pages;

use App\Filament\Resources\BachecaMessaggios\BachecaMessaggioResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBachecaMessaggio extends EditRecord
{
    protected static string $resource = BachecaMessaggioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
