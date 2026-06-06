<?php

namespace App\Filament\Widgets;

use App\Models\Azienda;
use App\Models\Documento;
use App\Models\ImportLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        $docQuery  = Documento::query();
        $logQuery  = ImportLog::whereMonth('created_at', now()->month);

        if (! $user->isSuperAdmin()) {
            $ids = $user->aziendeGestite()->pluck('aziende.id');
            $docQuery->whereIn('azienda_id', $ids);
            $logQuery->whereIn('azienda_id', $ids);
        }

        return [
            Stat::make(
                'Aziende gestite',
                $user->isSuperAdmin() ? Azienda::count() : $user->aziendeGestite()->count()
            )->icon('heroicon-o-building-office'),

            Stat::make('Documenti totali', $docQuery->count())
                ->icon('heroicon-o-document-text'),

            Stat::make('Import questo mese', $logQuery->count())
                ->icon('heroicon-o-arrow-up-tray'),

            Stat::make(
                'File non assegnati',
                (clone $docQuery)->where('utente_non_trovato', true)->count()
            )
            ->icon('heroicon-o-exclamation-triangle')
            ->color('warning'),
        ];
    }
}
