<?php

namespace App\Services;

use App\Models\Azienda;
use Illuminate\Support\Facades\Storage;

class StorageAziendaService
{
    public function initAziendaDirectory(Azienda $azienda): void
    {
        $disk = Storage::disk('cedolini');
        if (! $disk->exists($azienda->slug)) {
            $disk->makeDirectory($azienda->slug);
        }
    }

    public function listCartelleOnDisk(Azienda $azienda): array
    {
        $disk = Storage::disk('cedolini');
        return $disk->directories($azienda->slug);
    }
}
