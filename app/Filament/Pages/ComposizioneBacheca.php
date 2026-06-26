<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasAziendaScope;
use App\Models\Azienda;
use App\Models\BachecaDestinatario;
use App\Models\BachecaMessaggio;
use App\Models\User;
use App\Notifications\NuovoMessaggioBacheca;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ComposizioneBacheca extends Page implements HasForms
{
    use HasAziendaScope, InteractsWithForms;

    protected string $view = 'filament.pages.composizione-bacheca';

    public ?array $data = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationLabel(): string
    {
        return 'Nuova comunicazione';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Bacheca';
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
                Section::make('Messaggio')
                    ->schema([
                        TextInput::make('titolo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('corpo')
                            ->required()
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                            ->columnSpanFull(),

                        Toggle::make('pinned')
                            ->label('Metti in evidenza (📌)')
                            ->default(false),
                    ]),

                Section::make('Destinatari')
                    ->schema([
                        Select::make('azienda_filtro')
                            ->label('Filtra per azienda')
                            ->options(function () {
                                $ids = $this->getAziendeVisibiliIds();

                                return Azienda::whereIn('id', $ids)
                                    ->where('attiva', true)
                                    ->pluck('nome', 'id');
                            })
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('destinatari', []))
                            ->searchable()
                            ->required(),

                        CheckboxList::make('destinatari')
                            ->label('Dipendenti')
                            ->options(function (Get $get) {
                                $aziendaId = $get('azienda_filtro');

                                if (! $aziendaId) {
                                    return [];
                                }

                                return User::where('role', 'dipendente')
                                    ->where('azienda_id', $aziendaId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->descriptions(function (Get $get) {
                                $aziendaId = $get('azienda_filtro');

                                if (! $aziendaId) {
                                    return [];
                                }

                                return User::where('role', 'dipendente')
                                    ->where('azienda_id', $aziendaId)
                                    ->pluck('email', 'id')
                                    ->toArray();
                            })
                            ->bulkToggleable()
                            ->searchable()
                            ->live()
                            ->columns(2)
                            ->noSearchResultsMessage('Nessun dipendente trovato.'),

                        Placeholder::make('conteggio_selezionati')
                            ->label('')
                            ->content(fn (Get $get): string => count($get('destinatari') ?? []).' dipendenti selezionati'
                            ),
                    ]),

            ]);

    }

    public function salvaBozzaAction(): Action
    {
        return Action::make('salvaBozza')
            ->label('Salva come bozza')
            ->color('gray')
            ->action(fn () => $this->salvaBozza());
    }

    public function pubblicaAction(): Action
    {
        return Action::make('pubblica')
            ->label('Pubblica e invia')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Conferma pubblicazione')
            ->modalDescription(fn (): string => 'Stai per inviare questo messaggio a '
                .count($this->data['destinatari'] ?? [])
                .' dipendenti. Riceveranno una notifica email. Confermi?'
            )
            ->modalSubmitActionLabel('Sì, pubblica e invia')
            ->action(fn () => $this->pubblica());
    }

    public function salvaBozza(): void
    {
        $data = $this->form->getState();

        BachecaMessaggio::create([
            'autore_id' => auth()->id(),
            'titolo' => $data['titolo'],
            'corpo' => $this->sanitizzaCorpo($data['corpo']),
            'pinned' => $data['pinned'] ?? false,
            // pubblicato_il resta null → è una bozza, nessun destinatario, nessuna mail
        ]);

        Notification::make()->title('Bozza salvata')->success()->send();

        $this->form->fill();
    }

    public function pubblica(): void
    {
        $data = $this->form->getState();
        $destinatari = $data['destinatari'] ?? [];

        if (count($destinatari) === 0) {
            Notification::make()
                ->title('Seleziona almeno un destinatario')
                ->danger()
                ->send();

            return;
        }

        $messaggio = BachecaMessaggio::create([
            'autore_id' => auth()->id(),
            'titolo' => $data['titolo'],
            'corpo' => $this->sanitizzaCorpo($data['corpo']),
            'pinned' => $data['pinned'] ?? false,
            'pubblicato_il' => now(),
        ]);

        foreach ($destinatari as $userId) {
            $dest = BachecaDestinatario::updateOrCreate(
                ['messaggio_id' => $messaggio->id, 'user_id' => $userId],
                ['notifica_inviata' => false],
            );

            $utente = User::find($userId);
            if ($utente) {
                $utente->notify(new NuovoMessaggioBacheca($messaggio));
                $dest->update(['notifica_inviata' => true]);
            }
        }

        Notification::make()
            ->title('Messaggio pubblicato')
            ->body('Inviato a '.count($destinatari).' dipendenti.')
            ->success()
            ->send();

        $this->form->fill();
    }

    private function sanitizzaCorpo(string $html): string
    {
        // whitelist tag sicuri: l'output del RichEditor è HTML
        return strip_tags($html, '<p><br><strong><em><ul><ol><li>');
    }
}
