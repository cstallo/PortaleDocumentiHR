<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cedolini:backfill {--all : Ridispatcha anche i documenti già con dati_cedolino}')]

#[Description('Dispatcha EstraiDatiCedolino per i documenti senza dati_cedolino')]
class BackfillDatiCedolino extends Command
{
// protected $signature = 'cedolini:backfill {--all : Ridispatcha anche i documenti già con dati_cedolino}';


public function handle(): int
{
    $query = \App\Models\Documento::query();

    if (! $this->option('all')) {
        $giaFatti = \App\Models\DatoCedolino::query()
            ->whereNotNull('documento_id')
            ->pluck('documento_id');
        $query->whereNotIn('id', $giaFatti);
    }

    $documenti = $query->get();
    $this->info("Documenti da processare: {$documenti->count()}");

    foreach ($documenti as $doc) {
        \App\Jobs\EstraiDatiCedolino::dispatch($doc);
        $this->line("  → dispatch doc #{$doc->id}");
    }

    $this->info('Fatto. I job sono in coda "parsing".');
    return self::SUCCESS;
}

}
