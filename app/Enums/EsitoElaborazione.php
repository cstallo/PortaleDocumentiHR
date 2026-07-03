<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EsitoElaborazione: string implements HasColor, HasLabel
{
    case Importato = 'importato';
    case CfNonTrovato = 'cf_non_trovato';
    case DuplicatoSaltato = 'duplicato_saltato';
    case NomeNonConforme = 'nome_non_conforme';
    case SomministratoEscluso = 'somministrato_escluso';

    public function getLabel(): string
    {
        return match ($this) {
            self::Importato => 'Importato',
            self::CfNonTrovato => 'CF non trovato',
            self::DuplicatoSaltato => 'Duplicato saltato',
            self::NomeNonConforme => 'Nome non conforme',
            self::SomministratoEscluso => 'Somministrato escluso',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Importato => 'success',
            self::CfNonTrovato => 'warning',
            self::DuplicatoSaltato => 'gray',
            self::NomeNonConforme => 'danger',
            self::SomministratoEscluso => 'info',
        };
    }
}
