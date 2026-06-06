<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasAziendaScope;
use App\Jobs\ElaboraZipMensile;
use App\Services\CartellaMeseService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;


class ImportazioneMensile extends Page implements HasForms
{
    use InteractsWithForms, HasAziendaScope;

    protected string $view = 'filament.pages.importazione-mensile';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationLabel(): string
    {
        return 'Importa cedolini';
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
                Section::make('Azienda e periodo')
                    ->schema([
                        Select::make('azienda_id')
                            ->label('Azienda')
                            ->options(function () {
                                $ids = $this->getAziendeVisibiliIds();
                                return \App\Models\Azienda::whereIn('id', $ids)
                                    ->where('attiva', true)
                                    ->pluck('nome', 'id');
                            })
                            ->required()
                            ->searchable(),
                        Select::make('anno')
                            ->options(array_combine(
                                range(date('Y') - 1, date('Y') + 1),
                                range(date('Y') - 1, date('Y') + 1)
                            ))
                            ->default(date('Y'))
                            ->required(),
                        Select::make('mese')
                            ->options([
                                1=>'Gennaio',  2=>'Febbraio', 3=>'Marzo',
                                4=>'Aprile',   5=>'Maggio',   6=>'Giugno',
                                7=>'Luglio',   8=>'Agosto',   9=>'Settembre',
                                10=>'Ottobre', 11=>'Novembre',12=>'Dicembre',
                            ])
                            ->default((int) date('m'))
                            ->required(),
                            TextInput::make('descrizione')
                                ->label('Descrizione invio')
                                ->placeholder('Es. Cedolini marzo 2026')
                                ->maxLength(255)
                                ->columnSpanFull(),

                    ])->columns(3),

                Section::make('Archivio ZIP')
                    ->schema([
                        FileUpload::make('archivio_zip')
                            ->label('File ZIP cedolini')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->maxSize(204800)
                            ->rules(['mimes:zip', 'max:204800'])
                            ->required()
                            ->disk('local')
                            ->directory('zip-imports-temp'),
                    ]),
            ]);
    }

    public function importa(): void
    {
        $data     = $this->form->getState();
        $cartella = app(CartellaMeseService::class)
            ->findOrCreate($data['azienda_id'], (int) $data['anno'], (int) $data['mese']);

                ElaboraZipMensile::dispatch(
            zipPath:        $data['archivio_zip'],
            cartellaMeseId: $cartella->id,
            aziendaId:      $data['azienda_id'],
            adminId:        auth()->id(),
            descrizione:    $data['descrizione'] ?? null,
        );


        Notification::make()
            ->title('Importazione avviata')
            ->body('Lo ZIP è in elaborazione per ' . $cartella->azienda->nome)
            ->success()
            ->send();

        $this->form->fill();
    }
}
