<?php

namespace App\Observers;

use App\Models\Azienda;
use App\Services\StorageAziendaService;

class AziendaObserver
{
    public function __construct(private StorageAziendaService $storageService) {}

    public function created(Azienda $azienda): void
    {
        $this->storageService->initAziendaDirectory($azienda);
    }
}
