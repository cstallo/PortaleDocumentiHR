<?php

namespace App\Services;

use App\Models\Azienda;
use App\Models\CartellaMese;
use Illuminate\Support\Facades\Storage;

class CartellaMeseService
{
    private const MESI = [
        1=>'gennaio',   2=>'febbraio',  3=>'marzo',    4=>'aprile',
        5=>'maggio',    6=>'giugno',    7=>'luglio',   8=>'agosto',
        9=>'settembre', 10=>'ottobre', 11=>'novembre', 12=>'dicembre',
    ];

    public function findOrCreate(int $aziendaId, int $anno, int $mese): CartellaMese
{
    $azienda  = Azienda::findOrFail($aziendaId);

    $cartella = CartellaMese::firstOrCreate(
        [
            'azienda_id' => $aziendaId,
            'anno'       => $anno,
            'mese'       => $mese,
        ],
        [
            'label'         => $this->buildLabel($mese),
            'path_relativo' => $this->buildPath($azienda->slug, $anno, $mese),
            'created_by'    => auth()->id(),
        ]
    );

    $disk = Storage::disk('cedolini');
    if (! $disk->exists($cartella->path_relativo)) {
        $disk->makeDirectory($cartella->path_relativo);
    }

    return $cartella;
}


    public function exists(int $aziendaId, int $anno, int $mese): bool
    {
        return CartellaMese::where('azienda_id', $aziendaId)
            ->where('anno', $anno)->where('mese', $mese)->exists();
    }

    private function buildLabel(int $mese): string
    {
        return sprintf('%02d-%s', $mese, self::MESI[$mese]);
    }

    private function buildPath(string $aziendaSlug, int $anno, int $mese): string
    {
        return sprintf('%s/%d/%s', $aziendaSlug, $anno, $this->buildLabel($mese));
    }
}
