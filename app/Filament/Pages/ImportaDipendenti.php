<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasAziendaScope;
use App\Jobs\ImportaDipendentiJob;
use App\Models\Azienda;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImportaDipendenti extends Page implements HasForms
{
    use HasAziendaScope, InteractsWithForms;

    protected string $view = 'filament.pages.importa-dipendenti';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationLabel(): string
    {
        return 'Importa dipendenti';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Importazione';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Azienda')
                    ->schema([
                        Select::make('azienda_id')
                            ->label('Azienda')
                            ->options(function () {
                                $ids = $this->getAziendeVisibiliIds();

                                return Azienda::whereIn('id', $ids)
                                    ->where('attiva', true)
                                    ->pluck('nome', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ]),

                Section::make('File dipendenti')
                    ->schema([
                        FileUpload::make('file_dipendenti')
                            ->label('File Excel/CSV')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                                'application/csv',
                                'application/octet-stream',
                            ])
                            ->helperText('Colonne richieste: Cognome, Nome, Codice Fiscale, email. Le altre (Sede, date, sesso…) se presenti vengono importate.')
                            ->required()
                            ->disk('local')
                            ->directory('import-dipendenti-temp'),
                    ]),
            ]);
    }

    public function importa(): void
    {
        $data = $this->form->getState();

        ImportaDipendentiJob::dispatch(
            filePath: $data['file_dipendenti'],
            aziendaId: (int) $data['azienda_id'],
            adminId: auth()->id(),
        );

        Notification::make()
            ->title('Import avviato')
            ->body('Il file è in elaborazione. Riceverai una notifica con il riepilogo a fine import.')
            ->success()
            ->send();

        $this->form->fill();
    }
}
