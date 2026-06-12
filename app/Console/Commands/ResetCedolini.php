<?php

namespace App\Console\Commands;

use App\Models\CartellaMese;
use App\Models\DatoCedolino;
use App\Models\Documento;
use App\Models\ImportLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Azienda;


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

    // 1+2) DB atomico: o si cancella tutto, o niente
    DB::transaction(function () {
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();

        DatoCedolino::query()->delete();
        Documento::query()->delete();
        CartellaMese::query()->delete();
        ImportLog::query()->delete();
        DB::table('notifications')->delete();
    });

// 3) File sul disk (FUORI dalla transazione: il filesystem non fa rollback)
$disk = Storage::disk('cedolini');
// ... invariato ...


        // 3) File sul disk cedolini (documenti privati + non_elaborati)
        $disk = Storage::disk('cedolini');
        foreach ($disk->directories() as $dir) {
            $disk->deleteDirectory($dir);
        }
        foreach ($disk->files() as $file) {
            $disk->delete($file);
        }

        // 4) Ricrea la cartella radice di ogni azienda (serve ai prossimi import)
        foreach (Azienda::pluck('slug') as $slug) {
            $disk->makeDirectory($slug);
        }


        $this->info('Reset completato: documenti, dati e file azzerati. Aziende e utenti intatti.');
        return self::SUCCESS;
    }
}

