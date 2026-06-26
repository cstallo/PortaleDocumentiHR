<?php

namespace App\Filament\Pages;

use App\Enums\TipoDocumento;
use App\Filament\Concerns\HasAziendaScope;
use App\Models\Azienda;
use App\Models\Documento;
use App\Models\User;
use App\Notifications\NuovoDocumentoPersonale;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CaricaDocumentoAziendale extends Page implements HasForms
{
    use HasAziendaScope, InteractsWithForms;

    protected string $view = 'filament.pages.carica-documento-aziendale';

    public ?array $data = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-arrow-up';
    }

    public static function getNavigationLabel(): string
    {
        return 'Carica documento';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Documenti';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Documento del dipendente')
                    ->schema([
                        Select::make('azienda_id')
                            ->label('Azienda')
                            ->options(fn () => Azienda::whereIn('id', $this->getAziendeVisibiliIds())
                                ->where('attiva', true)
                                ->pluck('nome', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),

                        Select::make('user_id')
                            ->label('Dipendente')
                            ->options(function (Get $get) {
                                $aziendaId = $get('azienda_id');
                                if (! $aziendaId) {
                                    return [];
                                }

                                return User::where('role', 'dipendente')
                                    ->where('azienda_id', $aziendaId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn (Get $get) => ! $get('azienda_id')),

                        Select::make('tipo')
                            ->label('Categoria documento')
                            ->options(collect(TipoDocumento::caricabiliManualmente())
                                ->mapWithKeys(fn (TipoDocumento $t) => [$t->value => $t->getLabel()]))
                            ->required(),

                        TextInput::make('nome')
                            ->label('Nome documento')
                            ->placeholder('Es. CU 2025')
                            ->required()
                            ->maxLength(255),

                        FileUpload::make('file')
                            ->label('File PDF')
                            ->disk('cedolini')
                            ->visibility('private')
                            ->directory(fn (Get $get) => $get('azienda_id').'/documenti/'.($get('tipo') ?? 'altro'))
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),

                        Textarea::make('descrizione')
                            ->label('Descrizione (facoltativa)')
                            ->rows(3),

                        DatePicker::make('data_documento')
                            ->label('Data del documento (facoltativa)'),
                    ]),
            ]);
    }

    public function salvaAction(): Action
    {
        return Action::make('salva')
            ->label('Salva documento')
            ->color('primary')
            ->action(fn () => $this->salva());
    }

    public function salva(): void
    {
        $data = $this->form->getState();

        $dipendente = User::findOrFail($data['user_id']);

        $documento = Documento::create([
            'azienda_id' => $data['azienda_id'],
            'user_id' => $dipendente->id,
            'codice_fiscale' => $dipendente->codice_fiscale,
            'tipo' => $data['tipo'],
            'nome_file' => $data['nome'],
            'path_storage' => $data['file'],
            'descrizione' => $data['descrizione'] ?? null,
            'data_documento' => $data['data_documento'] ?? null,
            'cartella_mese_id' => null,
        ]);

        // notifica email al dipendente (sincrona, come la bacheca)
        $dipendente->notify(new NuovoDocumentoPersonale($documento));

        Notification::make()
            ->title('Documento caricato')
            ->body("Assegnato a {$dipendente->name}. Notifica email inviata.")
            ->success()
            ->send();

        $this->form->fill();
    }
}
