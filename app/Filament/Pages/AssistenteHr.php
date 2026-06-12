<?php

namespace App\Filament\Pages;

use App\Models\BotQueryLog;
use App\Models\BotSetting;
use App\Services\AssistenteCedolinoService;
use App\Services\LlmQueryTranslator;
use Filament\Pages\Page;

class AssistenteHr extends Page
{
    protected string $view = 'filament.pages.assistente-hr';

    public string $domanda = '';

    public bool $inAttesa = false;

    public string $domandaPending = '';

    public ?string $pdfUrl = null;

    /** @var list<array{ruolo: string, testo: string}> */
    public array $messaggi = [];

    /**
     * Storico per l'LLM (solo domande + intent JSON, MAI valori) per i follow-up.
     *
     * @var list<array{user: string, assistant: string}>
     */
    public array $llmStorico = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->bot_enabled);
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationLabel(): string
    {
        return 'Assistente HR';
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    /**
     * Fase 1: mostra subito il messaggio dell'utente + indicatore "sta scrivendo",
     * poi innesca la fase 2 (la chiamata lenta all'LLM).
     */
    public function invia(): void
    {
        $domanda = trim($this->domanda);

        if ($domanda === '' || $this->inAttesa) {
            return;
        }

        $this->messaggi[] = ['ruolo' => 'user', 'testo' => $domanda];
        $this->domandaPending = $domanda;
        $this->domanda = '';
        $this->inAttesa = true;

        $this->dispatch('chat-scroll');
        $this->dispatch('elabora-risposta');
    }

    /**
     * Fase 2: traduce la domanda, risolve il dipendente, interroga i dati, risponde e logga.
     */
    public function rispondi(LlmQueryTranslator $translator, AssistenteCedolinoService $assistente): void
    {
        if (! $this->inAttesa) {
            return;
        }

        $domanda = $this->domandaPending;
        $richiedente = auth()->user();

        $noAnswer = BotSetting::current()->no_answer_message;
        $dipendenteRecord = null;
        $campo = $anno = $mese = null;
        $rispostaData = null;
        $motivo = null;

        try {
            $tradotta = $translator->translate($domanda, $this->llmStorico);

            if (! $tradotta['ok']) {
                $esito = 'non_risolta';
                $motivo = $tradotta['errore'] ?? null;
                $rispostaBot = $noAnswer;
            } else {
                $intento = $tradotta['intento'] ?? 'valore';
                $nome = $tradotta['dipendente'];
                $campo = $tradotta['campo'] ?? null;
                $anno = $tradotta['anno'] ?? null;
                $mese = $tradotta['mese'] ?? null;

                // Memoria di sessione: salva l'intent (senza valori) per i follow-up.
                $this->llmStorico[] = [
                    'user' => $domanda,
                    'assistant' => json_encode(
                        array_filter([
                            'intento' => $intento,
                            'dipendente' => $nome,
                            'campo' => $campo,
                            'anno' => $anno,
                            'mese' => $mese,
                        ], fn ($v) => $v !== null),
                        JSON_UNESCAPED_UNICODE
                    ),
                ];
                $this->llmStorico = array_slice($this->llmStorico, -10);

                $dipendenti = $assistente->cercaDipendenti($richiedente, $nome);

                if ($dipendenti->isEmpty()) {
                    $esito = 'non_risolta';
                    $motivo = 'dipendente non trovato';
                    $rispostaBot = "Non ho informazioni su \"{$nome}\".";
                } elseif ($dipendenti->count() > 1) {
                    $esito = 'non_risolta';
                    $motivo = 'dipendente ambiguo';
                    $elenco = $dipendenti->map(fn ($d) => $d->name.' — '.$d->email)->implode('; ');
                    $rispostaBot = "Ci sono più dipendenti con questo nome: {$elenco}. Indica il codice fiscale o l'email per distinguerli.";
                } else {
                    $dipendenteRecord = $dipendenti->first();

                    if ($intento === 'elenco') {
                        $elenco = $assistente->descriviDati($richiedente, $dipendenteRecord);
                        $esito = $elenco['ok'] ? 'risolta' : 'non_risolta';
                        $motivo = $elenco['ok'] ? null : 'nessun dato';
                        $rispostaBot = $elenco['testo'];
                    } elseif ($intento === 'riepilogo') {
                        $riepilogo = $assistente->riepilogo($richiedente, $dipendenteRecord, $anno, $mese);
                        $esito = $riepilogo['ok'] ? 'risolta' : 'non_risolta';
                        $motivo = $riepilogo['ok'] ? null : 'nessun dato';
                        $rispostaBot = $riepilogo['testo'];
                        $rispostaData = $riepilogo['ok'] ? $riepilogo['testo'] : null;
                    } elseif ($intento === 'documento') {
                        $doc = $assistente->trovaDocumento($richiedente, $dipendenteRecord, $anno, $mese);

                        if ($doc['documento']) {
                            $esito = 'risolta';
                            $this->pdfUrl = route('documenti.inline', $doc['documento']);
                            $rispostaBot = "Apro il cedolino di {$doc['periodo']} di {$dipendenteRecord->name}.";
                            $this->dispatch('apri-pdf');
                        } else {
                            $esito = 'non_risolta';
                            $motivo = 'documento non trovato';
                            $rispostaBot = "Non ho il cedolino richiesto per {$dipendenteRecord->name}.";
                        }
                    } else {
                        $risultato = $assistente->answer($richiedente, $dipendenteRecord, $campo, $anno, $mese);

                        if ($risultato['ok']) {
                            $esito = 'risolta';
                            $rispostaBot = $risultato['risposta'];
                            $rispostaData = $risultato['risposta'];
                        } else {
                            $esito = 'non_risolta';
                            $motivo = $risultato['motivo'] ?? 'nessun dato';
                            $rispostaBot = "Non ho informazioni a riguardo per {$dipendenteRecord->name}.";
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $esito = 'errore';
            $motivo = $e->getMessage();
            $rispostaBot = $noAnswer;
        }

        $this->messaggi[] = ['ruolo' => 'bot', 'testo' => $rispostaBot];
        $this->inAttesa = false;
        $this->domandaPending = '';

        BotQueryLog::create([
            'user_id' => $richiedente->id,
            'dipendente_user_id' => $dipendenteRecord?->id,
            'azienda_id' => $dipendenteRecord?->azienda_id,
            'domanda' => $domanda,
            'esito' => $esito,
            'campo' => $campo,
            'periodo_anno' => $anno,
            'periodo_mese' => $mese,
            'risposta' => $rispostaData,
            'motivo' => $motivo,
        ]);

        $this->dispatch('chat-scroll');
    }
}
