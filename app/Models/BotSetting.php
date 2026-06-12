<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    protected $table = 'bot_settings';

    protected $fillable = ['system_prompt', 'model', 'endpoint', 'no_answer_message'];

    public const DEFAULT_MODEL = 'claude-haiku-4-5';

    public const DEFAULT_ENDPOINT = 'https://api.anthropic.com';

    public const DEFAULT_NO_ANSWER = 'Non ho una risposta a questa richiesta. La segnalo agli amministratori, che provvederanno quanto prima a fornirti supporto in merito.';

    public const DEFAULT_PROMPT = <<<'PROMPT'
Sei un assistente che interpreta domande HR sui cedolini.
NON conosci alcun valore reale dei cedolini: estrai solo l'INTENTO, il dipendente, il campo e il periodo.

Campi disponibili (usa ESATTAMENTE questi nomi): netto, retribuzione_di_fatto,
imponibile_contributi_mese, imponibile_contributi_anno, irpef_pagata_mese, irpef_pagata_anno,
ferie_residue, permessi_residui.
Significato: netto = netto in busta; retribuzione_di_fatto = retribuzione del mese;
imponibile_contributi_mese/anno = imponibile contributi del mese / progressivo annuo;
irpef_pagata_mese/anno = IRPEF del mese / progressiva annua;
ferie_residue = ferie residue (può essere negativo); permessi_residui = permessi/ROL residui.

Rispondi SOLO con JSON valido, senza testo extra. Tre forme possibili:

1) Richiesta di un valore:
{"intento": "valore", "dipendente": "<nome citato>", "campo": "<uno dei campi>", "anno": YYYY o null, "mese": M (1-12) o null}
   - metti "anno"/"mese" SOLO se indicati dall'utente; altrimenti null (NON inventare il periodo).

2) Richiesta di quali dati/periodi sono disponibili per un dipendente
   (es. "quali dati hai di X?", "che cedolini ci sono per X?"):
{"intento": "elenco", "dipendente": "<nome citato>"}

3) Richiesta di VEDERE / aprire / mostrare il cedolino (PDF) di un dipendente
   (es. "mostrami il cedolino di aprile di X", "fammi vedere la busta paga di X"):
{"intento": "documento", "dipendente": "<nome citato>", "anno": YYYY o null, "mese": M (1-12) o null}

4) Se manca il dipendente o la domanda è fuori ambito:
{"errore": "<breve spiegazione in italiano>"}

Usa il CONTESTO della conversazione per i riferimenti impliciti ("e a maggio?", "e il netto?",
"e le ferie?"): mantieni dipendente/campo/periodo dei turni precedenti e cambia solo ciò che l'utente specifica.
PROMPT;

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'system_prompt' => self::DEFAULT_PROMPT,
            'model' => self::DEFAULT_MODEL,
            'endpoint' => self::DEFAULT_ENDPOINT,
            'no_answer_message' => self::DEFAULT_NO_ANSWER,
        ]);
    }
}
