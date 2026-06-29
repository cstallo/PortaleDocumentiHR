<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ImportaDipendentiService;
use App\Services\ParserExcelService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

#[Timeout(1800)]
#[Tries(1)]
class ImportaDipendentiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $filePath,   // path relativo sul disk 'local'
        private int $aziendaId,
        private int $adminId,
    ) {
        $this->onQueue('import');
    }

    public function handle(ParserExcelService $parser, ImportaDipendentiService $importer): void
    {
        $abs = Storage::disk('local')->path($this->filePath);

        $estratto = $parser->estrai($abs, includiRecord: true);
        $record = $estratto['fogli'][0]['record'] ?? [];

        $esito = $importer->importa($record, $this->aziendaId);

        Storage::disk('local')->delete($this->filePath);

        $this->avvisaAdmin($esito);
    }

    /**
     * @param  array{importati: int, saltati: array<int, array{riga: int, valore: string, motivo: string}>}  $esito
     */
    private function avvisaAdmin(array $esito): void
    {
        $admin = User::find($this->adminId);
        if ($admin === null) {
            return;
        }

        $nScartati = count($esito['saltati']);

        $corpo = "Importati: {$esito['importati']} dipendenti.";
        if ($nScartati > 0) {
            $corpo .= " Scartati: {$nScartati} (vedi dettaglio).";
        }

        Notification::make()
            ->title('Import dipendenti completato')
            ->body($corpo)
            ->success()
            ->sendToDatabase($admin);
    }

    public function failed(\Throwable $e): void
    {
        $admin = User::find($this->adminId);
        if ($admin === null) {
            return;
        }

        Notification::make()
            ->title('Import dipendenti fallito')
            ->body('Errore: '.$e->getMessage())
            ->danger()
            ->sendToDatabase($admin);
    }
}
