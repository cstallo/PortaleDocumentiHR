<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    public function download(User $user, Documento $documento): bool
    {
        if ($user->isDipendente()) {
            return $user->id === $documento->user_id
                && $user->azienda_id === $documento->azienda_id;
        }

        if ($user->isHr()) {
            $ids = $user->aziendeGestite()->pluck('aziende.id');
            return $ids->contains($documento->azienda_id);
        }

        return $user->isSuperAdmin();
    }
}
