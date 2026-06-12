<?php

namespace App\Jobs;

use App\Models\CartellaMese;
use App\Models\Documento;
use App\Models\ImportLog;
use App\Models\User;
use App\Notifications\NuovoDocumentoDisponibile;
use App\Services\CodiceFiscaleExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

use ZipArchive;

#[Timeout(600)]
#[Tries(2)]
class ElaboraZipMensile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $zipPath,
        private int    $cartellaMeseId,
        private int    $aziendaId,
        private int    $adminId,
        private ?string $descrizione = null,
    ) {
        $this->onQueue('import');
    }


    public function handle(CodiceFiscaleExtractor $extractor): void
    {
        $cartellaMese = CartellaMese::with('azienda')->findOrFail($this->cartellaMeseId);
        $azienda      = $cartellaMese->azienda;
        $zipFullPath  = Storage::disk('local')->path($this->zipPath);

        $log = ImportLog::create([
            'azienda_id'       => $this->aziendaId,
            'zip_originale'    => basename($this->zipPath),
            'descrizione'      => $this->descrizione,
            'zip_path_temp'    => $this->zipPath,
            'cartella_mese_id' => $this->cartellaMeseId,
            'admin_id'         => $this->adminId,
            'stato'            => 'in_elaborazione',
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipFullPath) !== true) {
            $log->update(['stato' => 'errore']);
            return;
        }

        $totFile = $zip->numFiles;
        $elaborati = $erroriCf = $nomeNonValido = $duplicati = 0;
        $dettaglioErrori = [];
        $documentiPerCf  = [];
        $utentiPerCf = [];

        for ($i = 0; $i < $totFile; $i++) {
            $filename = $zip->getNameIndex($i);
            if (str_ends_with($filename, '/')) continue;
            if (str_starts_with(basename($filename), '._')) continue;


            $basename = basename($filename);
            $cf       = $extractor->extract($basename);

            if ($cf === null) {
                Storage::disk('cedolini')->put(
                    "non_elaborati/{$azienda->slug}/{$basename}",
                    $zip->getFromIndex($i)
                );
                $nomeNonValido++;
                $dettaglioErrori[] = ['file' => $basename, 'errore' => 'nome_non_conforme'];
                continue;
            }

            // Dedup: se esiste già un Documento per questa persona in questo mese,
            // salta del tutto (niente file su disco, niente Documento, niente parsing).
            $giaPresente = Documento::where('azienda_id', $this->aziendaId)
                ->where('cartella_mese_id', $this->cartellaMeseId)
                ->where('codice_fiscale', strtoupper($cf))
                ->exists();


            if ($giaPresente) {
                $duplicati++;
                $dettaglioErrori[] = ['file' => $basename, 'errore' => 'gia_presente_saltato'];
                continue;
}


            $destPath     = "{$cartellaMese->path_relativo}/{$basename}";
            $diskCedolini = Storage::disk('cedolini');

            if ($diskCedolini->exists($destPath)) {
            $destPath = $this->resolveConflict($destPath, $diskCedolini);
            $duplicati++;
            $dettaglioErrori[] = ['file' => $basename, 'errore' => 'duplicato'];
        }


            $diskCedolini->put($destPath, $zip->getFromIndex($i));

            $user = User::byCodiceFiscale($cf)
                ->where('azienda_id', $this->aziendaId)
                ->first();


            if ($user === null) {
                $erroriCf++;
                $dettaglioErrori[] = [
                    'file'    => $basename,
                    'errore'  => 'cf_non_trovato_in_azienda',
                    'cf'      => $cf,
                    'azienda' => $azienda->nome,
                ];
            }

            $documento = Documento::create([
                'nome_file'          => $basename,
                'path_storage'       => $destPath,
                'codice_fiscale'     => strtoupper($cf),
                'azienda_id'         => $this->aziendaId,
                'user_id'            => $user?->id,
                'cartella_mese_id'   => $this->cartellaMeseId,
                'import_log_id'      => $log->id,
                'utente_non_trovato' => $user === null,
            ]);

            EstraiDatiCedolino::dispatch($documento);   // ← Step 14: parsing asincrono

            if ($user) {
                $documentiPerCf[$cf][] = $documento;
                $utentiPerCf[$cf]      = $user;   // ← lo tengo, niente riquery dopo
            }



            $elaborati++;
        }

        $zip->close();
        Storage::disk('local')->delete($this->zipPath);

        foreach ($documentiPerCf as $cf => $documenti) {
            $utentiPerCf[$cf]?->notify(new NuovoDocumentoDisponibile($documenti, $cartellaMese));
        }


        $log->update([
            'stato'                => 'completato',
            'tot_file_zip'         => $totFile,
            'file_elaborati'       => $elaborati,
            'file_errore_cf'       => $erroriCf,
            'file_nome_non_valido' => $nomeNonValido,
            'file_duplicati'       => $duplicati,
            'dettaglio_errori'     => $dettaglioErrori,
            'completato_il'        => now(),
        ]);
    }

    private function resolveConflict(string $path, $disk): string
    {
        $info    = pathinfo($path);
        $version = 2;
        do {
            $newPath = "{$info['dirname']}/{$info['filename']}_v{$version}.{$info['extension']}";
            $version++;
        } while ($disk->exists($newPath));
        return $newPath;
    }

    public function failed(\Throwable $e): void
    {
        ImportLog::where('zip_path_temp', $this->zipPath)
            ->update(['stato' => 'errore']);
    }
}
