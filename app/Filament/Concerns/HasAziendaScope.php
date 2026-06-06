<?php

namespace App\Filament\Concerns;

use App\Models\Azienda;
use Illuminate\Database\Eloquent\Builder;

trait HasAziendaScope
{
    protected function getAziendeVisibiliIds(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Azienda::pluck('id')->toArray();
        }

        return $user->aziendeGestite()->pluck('aziende.id')->toArray();
    }

    protected function scopePerAziende(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ids = $user->aziendeGestite()->pluck('aziende.id');
        return $query->whereIn('azienda_id', $ids);
    }
}
