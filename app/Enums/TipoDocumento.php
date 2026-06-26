<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoDocumento: string implements HasLabel
{
    case Cedolino = 'cedolino';
    case Cu = 'cu';
    case Contratto = 'contratto';
    case Altro = 'altro';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cedolino => 'Cedolino',
            self::Cu => 'CU (Certificazione Unica)',
            self::Contratto => 'Contratto',
            self::Altro => 'Altro',
        };
    }

    /**
     * Categorie selezionabili nell'upload manuale dall'HR
     * (il cedolino arriva solo dall'import ZIP, non si carica a mano).
     *
     * @return array<int, self>
     */
    public static function caricabiliManualmente(): array
    {
        return [self::Cu, self::Contratto, self::Altro];
    }
}
