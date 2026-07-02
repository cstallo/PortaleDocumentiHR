<?php

namespace App\Filament\Resources\RegistroInvioCedolinis;

use App\Enums\EsitoElaborazione;
use App\Filament\Resources\RegistroInvioCedolinis\Pages\ListRegistroInvioCedolinis;
use App\Models\Azienda;
use App\Models\CartellaMese;
use App\Models\ImportFile;
use App\Models\ImportLog;
use App\Notifications\NuovoDocumentoDisponibile;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RegistroInvioCedoliniResource extends Resource
{
    protected static ?string $model = ImportFile::class;

    protected static ?string $slug = 'registro-invio-cedolini';

    protected static ?string $modelLabel = 'invio cedolino';

    protected static ?string $pluralModelLabel = 'Registro invio cedolini';

    protected const MESI = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('importLog.descrizione')
                    ->label('Titolo invio')->placeholder('—')->searchable()->sortable()->wrap()
                    ->description(fn (ImportFile $r) => trim(
                        ($r->importLog?->azienda?->nome ?? '')
                        .($r->created_at ? ' · '.$r->created_at->format('d/m/Y H:i') : ''),
                        ' ·'
                    )),
                Tables\Columns\TextColumn::make('nome_file')
                    ->label('File')->searchable()->wrap()
                    ->description(fn (ImportFile $r) => $r->codice_fiscale),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utente')->placeholder('—')->searchable()
                    ->description(fn (ImportFile $r) => $r->email_destinatario),
                Tables\Columns\TextColumn::make('esito_elaborazione')
                    ->label('Elaborazione')->badge(),
                Tables\Columns\TextColumn::make('stato_invio')
                    ->label('Invio email')
                    ->badge()
                    ->state(fn (ImportFile $r) => self::statoInvio($r))
                    ->color(fn (string $state) => match ($state) {
                        'Inviata' => 'success',
                        'Fallita' => 'danger',
                        'In attesa' => 'warning',
                        default => 'gray',
                    })
                    ->description(function (ImportFile $r) {
                        $log = $r->emailLog;
                        if (! $log) {
                            return null; // nessun log ancora: la riga è "In attesa" / "Non inviabile"
                        }

                        return $log->inviata_il
                            ? 'Inviata il '.$log->inviata_il->format('d/m/Y H:i')
                            : 'Tentativo del '.$log->updated_at->format('d/m/Y H:i');
                    })
                    ->tooltip(fn (ImportFile $r) => $r->emailLog?->stato === 'fallita'
                                            ? 'Clicca per il dettaglio errore'
                                            : null)
                    ->action(
                        Action::make('dettaglioErrore')
                            ->modalHeading('Dettaglio invio email')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Chiudi')
                            ->modalContent(function (ImportFile $r) {
                                $log = $r->emailLog;

                                // riga intestazione: quando e quanti tentativi (solo se un log esiste)
                                $intestazione = $log
                                    ? 'Ultimo tentativo: '.($log->inviata_il ?? $log->updated_at)->format('d/m/Y H:i')
                                        .' · tentativi: '.$log->tentativi."\n\n"
                                    : '';

                                $dettaglio = $log?->errore
                                    ?: match (self::statoInvio($r)) {
                                        'Inviata' => 'Email inviata correttamente.',
                                        'In attesa' => 'Email non ancora inviata (in coda o worker fermo).',
                                        'Non inviabile' => 'File non inviabile: nessun destinatario associato.',
                                        default => 'Nessun dettaglio disponibile.',
                                    };

                                return new HtmlString(
                                    '<div class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap break-words">'
                                    .e($intestazione.$dettaglio)
                                    .'</div>'
                                );
                            }),
                    ),
                Tables\Columns\TextColumn::make('emailLog.tentativi')
                    ->label('Tentativi')
                    ->badge()
                    ->color(fn (?int $state) => ($state ?? 0) > 1 ? 'warning' : 'gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('import_log_id')
                    ->label('Descrizione invio')
                    ->options(fn () => ImportLog::query()
                        ->whereIn('azienda_id', self::aziendeVisibiliIds())
                        ->orderByDesc('created_at')
                        ->get()
                        ->mapWithKeys(fn (ImportLog $log) => [
                            $log->id => $log->descrizione
                                ?: 'Import #'.$log->id.' — '.$log->created_at->format('d/m/Y H:i'),
                        ])
                        ->toArray())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('anno')
                    ->label('Anno')
                    ->options(fn () => CartellaMese::query()
                        ->distinct()->orderByDesc('anno')->pluck('anno', 'anno')->toArray())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $anno) => $q->whereHas('importLog.cartellaMese',
                            fn (Builder $c) => $c->where('anno', $anno))
                    )),

                Tables\Filters\SelectFilter::make('mese')
                    ->label('Mese')
                    ->options(self::MESI)
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $mese) => $q->whereHas('importLog.cartellaMese',
                            fn (Builder $c) => $c->where('mese', $mese))
                    )),

                Tables\Filters\SelectFilter::make('azienda')
                    ->label('Azienda')
                    ->options(fn () => Azienda::whereIn('id', self::aziendeVisibiliIds())
                        ->orderBy('nome')->pluck('nome', 'id')->toArray())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $aziendaId) => $q->whereHas('importLog',
                            fn (Builder $l) => $l->where('azienda_id', $aziendaId))
                    )),

                Tables\Filters\SelectFilter::make('esito_elaborazione')
                    ->label('Esito elaborazione')
                    ->options(collect(EsitoElaborazione::cases())
                        ->mapWithKeys(fn (EsitoElaborazione $e) => [$e->value => $e->getLabel()])->toArray()),
            ])
            ->toolbarActions([
                BulkAction::make('reinvia')
                    ->label('Reinvia cedolini')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reinvia email cedolini')
                    ->modalDescription('Verrà rispedita l\'email con i cedolini ai destinatari selezionati (solo i file "Importato" con utente valido). Viene generato un nuovo log di invio per verificarne l\'esito.')
                    ->action(function (Collection $records): void {
                        $inviate = self::reinvia($records);

                        Notification::make()
                            ->title($inviate > 0 ? 'Reinvio avviato' : 'Nessun invio effettuato')
                            ->body($inviate > 0
                                ? "$inviate email rimesse in coda. Tra poco la colonna \"Invio email\" mostrerà il nuovo esito."
                                : 'Nessun record selezionato è reinviabile: servono file con esito "Importato" e un utente associato.')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Reinvia la mail "Nuovo documento" ai destinatari dei record selezionati.
     * Raggruppa per utente + mese (una mail per gruppo), genera un nuovo notifica_id
     * e lo stampiglia sulle righe, così l'esito del nuovo invio è tracciato.
     *
     * @return int numero di email rimesse in coda
     */
    protected static function reinvia(Collection $records): int
    {
        $inviabili = $records->filter(fn (ImportFile $r) => $r->esito_elaborazione === EsitoElaborazione::Importato
            && $r->documento_id !== null
            && $r->user_id !== null);

        if ($inviabili->isEmpty()) {
            return 0;
        }

        $inviabili->loadMissing('documento.cartellaMese', 'user');

        $inviate = 0;

        $gruppi = $inviabili->groupBy(fn (ImportFile $r) => $r->user_id.'-'.$r->documento?->cartella_mese_id);

        foreach ($gruppi as $gruppo) {
            $user = $gruppo->first()->user;
            $cartella = $gruppo->first()->documento?->cartellaMese;
            $documenti = $gruppo->pluck('documento')->filter()->values();

            if ($user === null || $cartella === null || $documenti->isEmpty()) {
                continue;
            }

            $notificaId = (string) Str::uuid();
            ImportFile::whereIn('id', $gruppo->pluck('id'))->update(['notifica_id' => $notificaId]);

            $notifica = new NuovoDocumentoDisponibile($documenti->all(), $cartella);
            $notifica->id = $notificaId;
            $user->notify($notifica);

            $inviate++;
        }

        return $inviate;
    }

    /**
     * Etichetta di stato invio email calcolata per riga.
     */
    protected static function statoInvio(ImportFile $r): string
    {
        if ($r->esito_elaborazione !== EsitoElaborazione::Importato) {
            return 'Non inviabile';
        }

        return match ($r->emailLog?->stato) {
            'inviata' => 'Inviata',
            'fallita' => 'Fallita',
            default => 'In attesa',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistroInvioCedolinis::route('/'),
        ];
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function getNavigationLabel(): string
    {
        return 'Registro invio cedolini';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Importazione';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Id delle aziende visibili all'utente corrente (super_admin = tutte).
     *
     * @return array<int, int>
     */
    protected static function aziendeVisibiliIds(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Azienda::pluck('id')->toArray();
        }

        return $user->aziendeGestite()->pluck('aziende.id')->toArray();
    }

    // Scope multi-azienda: HR vede solo i file delle sue aziende (via import_log.azienda_id)
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['emailLog', 'user', 'documento', 'importLog.cartellaMese', 'importLog.azienda']);

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('importLog',
            fn (Builder $l) => $l->whereIn('azienda_id', self::aziendeVisibiliIds()));
    }
}
