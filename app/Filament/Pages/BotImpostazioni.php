<?php

namespace App\Filament\Pages;

use App\Models\BotSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class BotImpostazioni extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.bot-impostazioni';

    public ?array $data = [];

    /** @var array<string, string> id modello => etichetta, popolato da "Verifica modelli" */
    public array $availableModels = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cpu-chip';
    }

    public static function getNavigationLabel(): string
    {
        return 'Bot · Impostazioni';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Strumenti';
    }

    public function mount(): void
    {
        $this->form->fill(
            BotSetting::current()->only(['system_prompt', 'no_answer_message', 'model', 'endpoint'])
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('system_prompt')
                    ->label('Prompt di sistema')
                    ->rows(14)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('no_answer_message')
                    ->label('Messaggio quando il bot non sa rispondere')
                    ->helperText('Mostrato all\'utente per le richieste non risolte (vengono comunque registrate).')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('endpoint')
                    ->label('Endpoint API')
                    ->required(),
                Select::make('model')
                    ->label('Modello')
                    ->options(fn (): array => $this->modelOptions())
                    ->helperText('Usa "Verifica modelli disponibili" per caricare la lista live dall\'API.')
                    ->native(false)
                    ->required(),
            ])
            ->statePath('data');
    }

    /**
     * Opzioni del Select modello: i modelli rilevati live (se presenti),
     * altrimenti i 3 noti; include sempre il modello attualmente salvato.
     *
     * @return array<string, string>
     */
    protected function modelOptions(): array
    {
        $options = $this->availableModels ?: [
            'claude-haiku-4-5' => 'claude-haiku-4-5',
            'claude-sonnet-4-6' => 'claude-sonnet-4-6',
            'claude-opus-4-8' => 'claude-opus-4-8',
        ];

        $current = $this->data['model'] ?? null;
        if ($current && ! array_key_exists($current, $options)) {
            $options[$current] = $current;
        }

        return $options;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verificaModelli')
                ->label('Verifica modelli disponibili')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('verificaModelli'),

            Action::make('salva')
                ->label('Salva impostazioni')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function verificaModelli(): void
    {
        $key = config('services.anthropic.key');

        if (blank($key)) {
            Notification::make()
                ->title('Chiave API mancante')
                ->body('Imposta ANTHROPIC_API_KEY nel .env del server.')
                ->danger()
                ->send();

            return;
        }

        $endpoint = rtrim($this->data['endpoint'] ?? BotSetting::DEFAULT_ENDPOINT, '/');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(15)->get($endpoint.'/v1/models');
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Errore di connessione')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($response->failed()) {
            Notification::make()
                ->title('Errore API ('.$response->status().')')
                ->body((string) $response->json('error.message', 'Verifica chiave ed endpoint.'))
                ->danger()
                ->send();

            return;
        }

        $models = collect($response->json('data', []))
            ->mapWithKeys(fn (array $m): array => [$m['id'] => ($m['display_name'] ?? $m['id'])])
            ->all();

        if (empty($models)) {
            Notification::make()
                ->title('Nessun modello restituito')
                ->warning()
                ->send();

            return;
        }

        $this->availableModels = $models;

        Notification::make()
            ->title('Modelli aggiornati')
            ->body(count($models).' modelli disponibili. Seleziona quello desiderato e salva.')
            ->success()
            ->send();
    }

    public function save(): void
    {
        BotSetting::current()->update($this->form->getState());

        Notification::make()->title('Impostazioni salvate')->success()->send();
    }
}
