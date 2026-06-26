<?php

namespace App\Filament\Resources\Documenti\Pages;

use App\Filament\Resources\Documenti\DocumentoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumento extends EditRecord
{
    protected static string $resource = DocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
