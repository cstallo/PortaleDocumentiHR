<?php

namespace App\Filament\Resources\Aziendas\Pages;

use App\Filament\Resources\Aziendas\AziendaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAzienda extends EditRecord
{
    protected static string $resource = AziendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
