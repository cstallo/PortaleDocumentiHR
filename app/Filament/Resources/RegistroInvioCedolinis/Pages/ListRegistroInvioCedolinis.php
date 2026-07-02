<?php

namespace App\Filament\Resources\RegistroInvioCedolinis\Pages;

use App\Filament\Resources\RegistroInvioCedolinis\RegistroInvioCedoliniResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListRegistroInvioCedolinis extends ListRecords
{
    protected static string $resource = RegistroInvioCedoliniResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
