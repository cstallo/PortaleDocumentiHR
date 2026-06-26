<?php

namespace App\Filament\Resources\Documenti\Pages;

use App\Filament\Resources\Documenti\DocumentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumenti extends ListRecords
{
    protected static string $resource = DocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
