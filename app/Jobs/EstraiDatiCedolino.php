<?php

namespace App\Jobs;

use App\Models\DatoCedolino;
use App\Models\Documento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;   // in testa, con gli altri use


#[Tries(3)]
#[Timeout(60)]

class EstraiDatiCedolino implements ShouldQueue
{
    
    use Dispatchable, Queueable;

    public function __construct(
        public Documento $documento,
    ) {
        $this->onQueue('parsing');
    }

        public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $path = Storage::disk('cedolini')->path($this->documento->path_storage);

        $response = Http::timeout(30)->post(
        rtrim(config('services.parser.url'), '/') . '/parse',
        ['path' => $path],
        );

        $response->throw();

        $payload  = $response->json();
        $data     = $payload['data'] ?? [];
        $success  = $payload['success'] ?? false;
        $warnings = $payload['warnings'] ?? [];

        $campi = [
            'codice_fiscale', 'periodo_anno', 'periodo_mese', 'netto',
            'imponibile_contributi_mese', 'imponibile_contributi_anno',
            'irpef_pagata_mese', 'irpef_pagata_anno',
            'ferie_residue', 'permessi_residui', 'retribuzione_di_fatto',
        ];

        $valori = [];
        foreach ($campi as $c) {
            $valori[$c] = $data[$c] ?? null;
        }

        if (!empty($valori['codice_fiscale'])) {
            $valori['codice_fiscale'] = strtoupper($valori['codice_fiscale']);
        }

            $valori['documento_id']     = $this->documento->id;
            $valori['azienda_id']       = $this->documento->azienda_id;
            $valori['user_id']          = $this->documento->user_id;
            $valori['parsing_riuscito'] = $success;
            $valori['parsing_note']     = $warnings;

            // Chiave di dedup: per cedolino logico (CF+periodo) se il parsing è
            // riuscito, altrimenti per documento (CF/periodo possono essere null).
            $cedolinoValido = $success
                && !empty($valori['codice_fiscale'])
                && !empty($valori['periodo_anno'])
                && !empty($valori['periodo_mese']);

            $chiave = $cedolinoValido
                ? [
                    'azienda_id'     => $this->documento->azienda_id,
                    'codice_fiscale' => $valori['codice_fiscale'],
                    'periodo_anno'   => $valori['periodo_anno'],
                    'periodo_mese'   => $valori['periodo_mese'],
                  ]
                : ['documento_id' => $this->documento->id];

            DatoCedolino::updateOrCreate($chiave, $valori);   
    }

        public function failed(\Throwable $e): void
        {
            Log::error('Parsing cedolino fallito dopo i retry', [
                'documento_id' => $this->documento->id,
                'azienda_id'   => $this->documento->azienda_id,
                'errore'       => $e->getMessage(),
            ]);
        }

}