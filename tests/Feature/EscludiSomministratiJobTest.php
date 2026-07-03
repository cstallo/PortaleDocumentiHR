<?php

use App\Enums\EsitoElaborazione;
use App\Jobs\ElaboraZipMensile;
use App\Jobs\EstraiDatiCedolino;
use App\Models\Azienda;
use App\Models\CartellaMese;
use App\Models\Documento;
use App\Models\ImportFile;
use App\Models\User;
use App\Notifications\NuovoDocumentoDisponibile;
use App\Services\CodiceFiscaleExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const CF_NORMALE = 'RSSMRA80A01H501U';
const CF_SOMMINISTRATO = 'BNCLGU85M02F205X';
const FILE_NORMALE = 'Cedolino Rossi (RSSMRA80A01H501U-1).pdf';
const FILE_SOMMINISTRATO = 'Cedolino Bianchi (BNCLGU85M02F205X-1).pdf';

/**
 * Crea uno ZIP reale sul disk `local` (fake) con i file indicati e ne ritorna il path relativo.
 */
function creaZipCedolini(array $nomiFile): string
{
    $relative = 'zip-imports-temp/test-'.uniqid().'.zip';
    $fullPath = Storage::disk('local')->path($relative);
    @mkdir(dirname($fullPath), 0777, true);

    $zip = new ZipArchive;
    $zip->open($fullPath, ZipArchive::CREATE);
    foreach ($nomiFile as $nome) {
        $zip->addFromString($nome, 'CONTENUTO-PDF-FINTO');
    }
    $zip->close();

    return $relative;
}

function eseguiImport(int $aziendaId, int $cartellaId, int $adminId, string $zip, bool $escludi): void
{
    (new ElaboraZipMensile($zip, $cartellaId, $aziendaId, $adminId, null, $escludi))
        ->handle(app(CodiceFiscaleExtractor::class));
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('cedolini');
    Notification::fake();
    Queue::fake(); // evita che EstraiDatiCedolino (parsing PDF) giri davvero

    $this->azienda = Azienda::create(['nome' => 'Alfa', 'slug' => 'alfa-srl']);
    $this->admin = User::factory()->create(['role' => 'super_admin']);

    $this->cartella = CartellaMese::create([
        'azienda_id' => $this->azienda->id,
        'anno' => 2026,
        'mese' => 7,
        'label' => '07-luglio',
        'path_relativo' => 'alfa-srl/2026/07-luglio',
        'created_by' => $this->admin->id,
    ]);

    $this->normale = User::factory()->create([
        'role' => 'dipendente',
        'azienda_id' => $this->azienda->id,
        'codice_fiscale' => CF_NORMALE,
        'somministrato' => false,
    ]);

    $this->somministrato = User::factory()->create([
        'role' => 'dipendente',
        'azienda_id' => $this->azienda->id,
        'codice_fiscale' => CF_SOMMINISTRATO,
        'somministrato' => true,
    ]);
});

it('flag spento: consegna il cedolino anche al somministrato', function () {
    $zip = creaZipCedolini([FILE_NORMALE, FILE_SOMMINISTRATO]);

    eseguiImport($this->azienda->id, $this->cartella->id, $this->admin->id, $zip, false);

    expect(Documento::where('codice_fiscale', CF_NORMALE)->exists())->toBeTrue()
        ->and(Documento::where('codice_fiscale', CF_SOMMINISTRATO)->exists())->toBeTrue();

    Notification::assertSentTo($this->normale, NuovoDocumentoDisponibile::class);
    Notification::assertSentTo($this->somministrato, NuovoDocumentoDisponibile::class);
});

it('flag acceso: esclude il somministrato ma consegna al dipendente normale', function () {
    $zip = creaZipCedolini([FILE_NORMALE, FILE_SOMMINISTRATO]);

    eseguiImport($this->azienda->id, $this->cartella->id, $this->admin->id, $zip, true);

    // Dipendente normale: consegnato regolarmente
    expect(Documento::where('codice_fiscale', CF_NORMALE)->exists())->toBeTrue();
    Notification::assertSentTo($this->normale, NuovoDocumentoDisponibile::class);

    // Somministrato: nessun Documento, nessuna email
    expect(Documento::where('codice_fiscale', CF_SOMMINISTRATO)->exists())->toBeFalse();
    Notification::assertNotSentTo($this->somministrato, NuovoDocumentoDisponibile::class);
});

it('flag acceso: traccia il file escluso in import_file e in quarantena', function () {
    $zip = creaZipCedolini([FILE_SOMMINISTRATO]);

    eseguiImport($this->azienda->id, $this->cartella->id, $this->admin->id, $zip, true);

    $riga = ImportFile::where('codice_fiscale', CF_SOMMINISTRATO)->first();
    expect($riga)->not->toBeNull()
        ->and($riga->esito_elaborazione)->toBe(EsitoElaborazione::SomministratoEscluso)
        ->and($riga->user_id)->toBe($this->somministrato->id);

    Storage::disk('cedolini')->assertExists('somministrati_esclusi/alfa-srl/'.FILE_SOMMINISTRATO);
});

it('flag acceso: un non-somministrato viene importato normalmente', function () {
    $zip = creaZipCedolini([FILE_NORMALE]);

    eseguiImport($this->azienda->id, $this->cartella->id, $this->admin->id, $zip, true);

    $riga = ImportFile::where('codice_fiscale', CF_NORMALE)->first();
    expect($riga->esito_elaborazione)->toBe(EsitoElaborazione::Importato);

    Queue::assertPushed(EstraiDatiCedolino::class);
});
