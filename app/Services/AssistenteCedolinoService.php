<?php

namespace App\Services;

use App\Models\DatoCedolino;
use App\Models\Documento;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssistenteCedolinoService
{
    /**
     * Cerca i dipendenti per nome (parziale), ristretti a ciò che il richiedente può vedere.
     *
     * @return Collection<int, User>
     */
    public function cercaDipendenti(User $richiedente, string $nome): Collection
    {
        $nome = trim($nome);

        $query = User::query()
            ->where('role', 'dipendente')
            ->where(function ($q) use ($nome) {
                $q->where('name', 'like', '%'.$nome.'%')
                    ->orWhere('codice_fiscale', strtoupper($nome))
                    ->orWhere('email', $nome);
            });

        if ($richiedente->isHr()) {
            $query->whereIn('azienda_id', $richiedente->aziendeGestite()->pluck('aziende.id'));
        } elseif (! $richiedente->isSuperAdmin()) {
            $query->where('id', $richiedente->id);
        }

        return $query->orderBy('name')->get();
    }

    private const CAMPI_MONETARI = [
        'netto', 'retribuzione_di_fatto',
        'imponibile_contributi_mese', 'imponibile_contributi_anno',
        'irpef_pagata_mese', 'irpef_pagata_anno',
    ];

    private const LABELS = [
        'netto' => 'Il netto',
        'retribuzione_di_fatto' => 'La retribuzione di fatto',
        'imponibile_contributi_mese' => 'L\'imponibile contributi del mese',
        'imponibile_contributi_anno' => 'L\'imponibile contributi annuo',
        'irpef_pagata_mese' => 'L\'IRPEF del mese',
        'irpef_pagata_anno' => 'L\'IRPEF pagata annua',
        'ferie_residue' => 'Le ferie residue',
        'permessi_residui' => 'I permessi residui',
    ];

    private const MESI = [
        1 => 'gennaio', 2 => 'febbraio', 3 => 'marzo', 4 => 'aprile',
        5 => 'maggio', 6 => 'giugno', 7 => 'luglio', 8 => 'agosto',
        9 => 'settembre', 10 => 'ottobre', 11 => 'novembre', 12 => 'dicembre',
    ];

    private const RIEPILOGO_LABELS = [
        'netto' => 'Netto',
        'retribuzione_di_fatto' => 'Retribuzione di fatto',
        'imponibile_contributi_mese' => 'Imponibile contributi (mese)',
        'imponibile_contributi_anno' => 'Imponibile contributi (anno)',
        'irpef_pagata_mese' => 'IRPEF (mese)',
        'irpef_pagata_anno' => 'IRPEF (anno)',
        'ferie_residue' => 'Ferie residue',
        'permessi_residui' => 'Permessi residui',
    ];

    /**
     * Risponde con il valore di un campo per un dipendente.
     * Se anno/mese sono null, usa l'ULTIMO cedolino disponibile.
     *
     * @return array{ok: bool, risposta?: string, valore?: float, motivo?: string}
     */
    public function answer(User $richiedente, User $dipendente, string $campo, ?int $anno, ?int $mese): array
    {
        $builder = DatoCedolino::query()->where('user_id', $dipendente->id);
        $this->applicaScope($builder, $richiedente);

        if ($anno !== null && $mese !== null) {
            $builder->where('periodo_anno', $anno)->where('periodo_mese', $mese);
        } else {
            // periodo non indicato → ultimo cedolino disponibile
            $builder->orderByDesc('periodo_anno')->orderByDesc('periodo_mese');
        }

        $riga = $builder->first();
        $valore = $riga?->{$campo};

        if ($riga === null || $valore === null) {
            return ['ok' => false, 'motivo' => 'nessun dato'];
        }

        $periodo = (self::MESI[$riga->periodo_mese] ?? $riga->periodo_mese).' '.$riga->periodo_anno;
        $valoreFmt = number_format((float) $valore, 2, ',', '.');
        if (in_array($campo, self::CAMPI_MONETARI, true)) {
            $valoreFmt .= ' €';
        }

        $risposta = (self::LABELS[$campo] ?? ucfirst($campo))." di {$periodo}: {$valoreFmt}.";

        return ['ok' => true, 'risposta' => $risposta, 'valore' => (float) $valore];
    }

    /**
     * Elenca i periodi (cedolini) disponibili per un dipendente — solo metadati, nessun valore.
     *
     * @return array{ok: bool, testo: string}
     */
    public function descriviDati(User $richiedente, User $dipendente): array
    {
        $builder = DatoCedolino::query()->where('user_id', $dipendente->id);
        $this->applicaScope($builder, $richiedente);

        $periodi = $builder->orderByDesc('periodo_anno')->orderByDesc('periodo_mese')
            ->get(['periodo_anno', 'periodo_mese'])
            ->map(fn ($r) => (self::MESI[$r->periodo_mese] ?? $r->periodo_mese).' '.$r->periodo_anno)
            ->unique()
            ->values();

        if ($periodi->isEmpty()) {
            return ['ok' => false, 'testo' => "Non ho cedolini per {$dipendente->name}."];
        }

        return [
            'ok' => true,
            'testo' => "Per {$dipendente->name} ho i cedolini di: ".$periodi->implode(', ')
                .'. Posso dirti netto, retribuzione, imponibile contributi, IRPEF, ferie e permessi residui.',
        ];
    }

    /**
     * Trova il documento (PDF) del cedolino di un dipendente per il periodo richiesto.
     * Se anno/mese sono null, usa l'ULTIMO cedolino disponibile.
     *
     * @return array{documento: ?Documento, periodo: ?string}
     */
    public function trovaDocumento(User $richiedente, User $dipendente, ?int $anno, ?int $mese): array
    {
        $builder = DatoCedolino::query()->where('user_id', $dipendente->id);
        $this->applicaScope($builder, $richiedente);

        if ($anno !== null && $mese !== null) {
            $builder->where('periodo_anno', $anno)->where('periodo_mese', $mese);
        } else {
            $builder->orderByDesc('periodo_anno')->orderByDesc('periodo_mese');
        }

        $riga = $builder->with('documento')->first();

        if ($riga === null || $riga->documento === null) {
            return ['documento' => null, 'periodo' => null];
        }

        $periodo = (self::MESI[$riga->periodo_mese] ?? $riga->periodo_mese).' '.$riga->periodo_anno;

        return ['documento' => $riga->documento, 'periodo' => $periodo];
    }

    /**
     * Riepilogo dei dati principali di un cedolino (ultimo disponibile o periodo indicato).
     *
     * @return array{ok: bool, testo: string}
     */
    public function riepilogo(User $richiedente, User $dipendente, ?int $anno, ?int $mese): array
    {
        $builder = DatoCedolino::query()->where('user_id', $dipendente->id);
        $this->applicaScope($builder, $richiedente);

        if ($anno !== null && $mese !== null) {
            $builder->where('periodo_anno', $anno)->where('periodo_mese', $mese);
        } else {
            $builder->orderByDesc('periodo_anno')->orderByDesc('periodo_mese');
        }

        $riga = $builder->first();

        if ($riga === null) {
            return ['ok' => false, 'testo' => "Non ho cedolini per {$dipendente->name}."];
        }

        $periodo = (self::MESI[$riga->periodo_mese] ?? $riga->periodo_mese).' '.$riga->periodo_anno;

        $righe = [];
        foreach (self::RIEPILOGO_LABELS as $campo => $label) {
            $valore = $riga->{$campo};
            if ($valore === null) {
                continue;
            }
            $fmt = number_format((float) $valore, 2, ',', '.');
            if (in_array($campo, self::CAMPI_MONETARI, true)) {
                $fmt .= ' €';
            }
            $righe[] = "• {$label}: {$fmt}";
        }

        if (empty($righe)) {
            return ['ok' => false, 'testo' => "Il cedolino di {$periodo} di {$dipendente->name} non ha dati economici."];
        }

        return [
            'ok' => true,
            'testo' => "Cedolino di {$periodo} di {$dipendente->name}:\n".implode("\n", $righe),
        ];
    }

    /**
     * Limita la query a ciò che il richiedente può vedere (difesa in profondità).
     */
    private function applicaScope(Builder $builder, User $richiedente): void
    {
        if ($richiedente->isHr()) {
            $builder->visibiliPer($richiedente);          // solo le sue aziende
        } elseif (! $richiedente->isSuperAdmin()) {
            $builder->where('user_id', $richiedente->id); // dipendente: solo sé stesso
        }
    }
}
