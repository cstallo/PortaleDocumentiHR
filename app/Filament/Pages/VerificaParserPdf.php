<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;


class VerificaParserPdf extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.verifica-parser-pdf';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-magnifying-glass';
    }

    public static function getNavigationLabel(): string
    {
        return 'Verifica parser PDF';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Strumenti';
    }

    public ?array $data = [];

    // qui terremo l'esito dell'analisi (lo riempiamo al Passo 4)
    public ?string $testoEstratto = null;
    public ?string $esito = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('PDF da verificare')
                    ->schema([
                        FileUpload::make('pdf')
                            ->label('File PDF cedolino')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480)
                            ->rules(['mimes:pdf', 'max:20480'])
                            ->required()
                            ->disk('local')
                            ->directory('verifica-parser-temp'),
                    ]),
            ]);
    }

    public function analizza(): void
{
    $data = $this->form->getState();
    $path = Storage::disk('local')->path($data['pdf']);

    try {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($path);
        $testo  = trim($pdf->getText());

        $this->testoEstratto = $testo;

        $numCaratteri = mb_strlen($testo);

        if ($numCaratteri === 0) {
            $this->esito = '❌ Nessun testo estratto: probabile PDF immagine/scansione (servirebbe OCR). NON consultabile via parser.';
        } elseif ($numCaratteri < 200) {
            $this->esito = "⚠️ Testo molto scarso ({$numCaratteri} caratteri): da verificare manualmente.";
        } else {
            $this->esito = "✅ Testo estratto correttamente ({$numCaratteri} caratteri): il PDF è consultabile via parser.";
        }
    } catch (\Throwable $e) {
        $this->testoEstratto = null;
        $this->esito = '❌ Errore di parsing: ' . $e->getMessage();
    } finally {
        // pulizia: il file di test non va conservato
        Storage::disk('local')->delete($data['pdf']);
    }
}

}
