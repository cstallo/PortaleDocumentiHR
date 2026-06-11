<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('cedolini:reset {--force : Salta la conferma interattiva}')]
#[Description('Azzera documenti, dati estratti e file caricati (mantiene aziende e utenti)')]
class ResetCedolini extends Command
{
    public function handle(): int
    {
        if (! $this->option('force') &&
            ! $this->confirm('Cancello TUTTI i documenti, i dati estratti e i file caricati. Aziende e utenti restano. Procedo?')) {
            $this->warn('Annullato.');
            return self::SUCCESS;
        }

        // 1) Svuota la coda: eventuali job di parsing pendenti puntano a
        //    documenti che sto per cancellare → li elimino prima.
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();

        // 2) DB in ordine FK-safe (i figli prima dei padri)
        \App\Models\DatoCedolino::query()->delete();
        \App\Models\Documento::query()->delete();
        \App\Models\CartellaMese::query()->delete();
        \App\Models\ImportLog::query()->delete();
        DB::table('notifications')->delete();

        // 3) File sul disk cedolini (documenti privati + non_elaborati)
        $disk = Storage::disk('cedolini');
        foreach ($disk->directories() as $dir) {
            $disk->deleteDirectory($dir);
        }
        foreach ($disk->files() as $file) {
            $disk->delete($file);
        }

        // 4) Ricrea la cartella radice di ogni azienda (serve ai prossimi import)
        foreach (\App\Models\Azienda::all() as $azienda) {
            $disk->makeDirectory($azienda->slug);
        }

        $this->info('Reset completato: documenti, dati e file azzerati. Aziende e utenti intatti.');
        return self::SUCCESS;
    }
}

