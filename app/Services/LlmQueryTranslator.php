<?php

namespace App\Services;

use App\Models\BotSetting;
use Illuminate\Support\Facades\Http;

class LlmQueryTranslator
{
    /** Campi interrogabili (devono combaciare con le colonne di dati_cedolino). */
    public const CAMPI_AMMESSI = [
        'netto',
        'retribuzione_di_fatto',
        'imponibile_contributi_mese',
        'imponibile_contributi_anno',
        'irpef_pagata_mese',
        'irpef_pagata_anno',
        'ferie_residue',
        'permessi_residui',
    ];

    /**
     * Traduce una domanda HR in una query strutturata (dipendente + campo + periodo).
     *
     * Lo storico contiene SOLO domande dell'utente e interpretazioni strutturate
     * (JSON intent, senza valori) dei turni precedenti, per risolvere i riferimenti
     * impliciti ("e a maggio?"). Nessun valore di cedolino viene mai inviato all'LLM.
     *
     * @param  list<array{user: string, assistant: string}>  $storico
     * @return array{ok: bool, intento?: string, dipendente?: string, campo?: string, anno?: int|null, mese?: int|null, errore?: string}
     */
    public function translate(string $domanda, array $storico = []): array
    {
        $settings = BotSetting::current();
        $key = config('services.anthropic.key');

        if (blank($key)) {
            return ['ok' => false, 'errore' => 'Configurazione LLM mancante (chiave API).'];
        }

        $messages = [];
        foreach ($storico as $turno) {
            $messages[] = ['role' => 'user', 'content' => $turno['user']];
            $messages[] = ['role' => 'assistant', 'content' => $turno['assistant']];
        }
        $messages[] = ['role' => 'user', 'content' => $domanda];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(30)->post(rtrim($settings->endpoint, '/').'/v1/messages', [
                'model' => $settings->model,
                'max_tokens' => 256,
                'system' => $settings->system_prompt,
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'errore' => 'Errore di connessione al servizio LLM.'];
        }

        if ($response->failed()) {
            return ['ok' => false, 'errore' => 'Errore dal servizio LLM ('.$response->status().').'];
        }

        $text = collect($response->json('content', []))
            ->firstWhere('type', 'text')['text'] ?? '';

        $parsed = $this->extractJson($text);

        if ($parsed === null) {
            return ['ok' => false, 'errore' => 'Risposta del bot non interpretabile.'];
        }

        if (isset($parsed['errore'])) {
            return ['ok' => false, 'errore' => (string) $parsed['errore']];
        }

        $dipendente = $parsed['dipendente'] ?? null;

        if (blank($dipendente)) {
            return ['ok' => false, 'errore' => 'Indica di quale dipendente vuoi l\'informazione.'];
        }

        $dipendente = trim((string) $dipendente);
        $intento = $parsed['intento'] ?? 'valore';
        $campoRichiesto = $this->detectRequestedField($domanda);
        if ($campoRichiesto !== null) {
            $intento = 'valore';
        } elseif ($this->looksLikeDocumentRequest($domanda)) {
            $intento = 'documento';
        }

        $anno = $parsed['anno'] ?? null;
        $mese = $parsed['mese'] ?? null;
        $anno = is_numeric($anno) ? (int) $anno : null;
        $mese = (is_numeric($mese) && $mese >= 1 && $mese <= 12) ? (int) $mese : null;

        if ($intento === 'elenco') {
            return ['ok' => true, 'intento' => 'elenco', 'dipendente' => $dipendente];
        }

        if ($intento === 'documento') {
            return ['ok' => true, 'intento' => 'documento', 'dipendente' => $dipendente, 'anno' => $anno, 'mese' => $mese];
        }

        $campo = $campoRichiesto ?? $parsed['campo'] ?? null;

        // Nessun campo specifico (o intento riepilogo) → riepilogo dell'ultimo cedolino
        // (o del periodo indicato), invece di rispondere in modo generico.
        if ($intento === 'riepilogo' || ! in_array($campo, self::CAMPI_AMMESSI, true)) {
            return ['ok' => true, 'intento' => 'riepilogo', 'dipendente' => $dipendente, 'anno' => $anno, 'mese' => $mese];
        }

        return [
            'ok' => true,
            'intento' => 'valore',
            'dipendente' => $dipendente,
            'campo' => $campo,
            'anno' => $anno,
            'mese' => $mese,
        ];
    }

    /**
     * Estrae il primo oggetto JSON dalla risposta (tollerante a fence/testo extra).
     *
     * @return array<string, mixed>|null
     */
    private function extractJson(string $text): ?array
    {
        $decoded = json_decode(trim($text), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function looksLikeDocumentRequest(string $domanda): bool
    {
        $domanda = mb_strtolower($domanda);

        $asksToOpen = preg_match('/\b(mostra(?:mi)?|fammi vedere|vedere|vedi|visualizza|apri|aprire|scarica)\b/u', $domanda) === 1;
        $mentionsPayslip = preg_match('/\b(cedolino|cedolini|busta paga|pdf)\b/u', $domanda) === 1;

        return $asksToOpen && $mentionsPayslip;
    }

    private function detectRequestedField(string $domanda): ?string
    {
        $domanda = mb_strtolower($domanda);

        return match (true) {
            preg_match('/\bnetto\b/u', $domanda) === 1 => 'netto',
            preg_match('/\bretribuzione\b|\bpaga\b|\bstipendio\b/u', $domanda) === 1 => 'retribuzione_di_fatto',
            preg_match('/\bimponibile\b/u', $domanda) === 1 && preg_match('/\bann(?:o|uo|ua|uale|uali)\b|\bprogressiv/u', $domanda) === 1 => 'imponibile_contributi_anno',
            preg_match('/\bimponibile\b/u', $domanda) === 1 => 'imponibile_contributi_mese',
            preg_match('/\birpef\b/u', $domanda) === 1 && preg_match('/\bann(?:o|uo|ua|uale|uali)\b|\bprogressiv/u', $domanda) === 1 => 'irpef_pagata_anno',
            preg_match('/\birpef\b/u', $domanda) === 1 => 'irpef_pagata_mese',
            preg_match('/\bferie\b/u', $domanda) === 1 => 'ferie_residue',
            preg_match('/\bpermessi\b|\brol\b/u', $domanda) === 1 => 'permessi_residui',
            default => null,
        };
    }
}
