<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Notifications\InvitoDipendente;
use App\Notifications\InvitoUtente;
use App\Notifications\NuovoDocumentoDisponibile;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

class RegistraEsitoEmail
{
    public function onSent(NotificationSent $event): void
    {
        $this->registra($event, 'inviata', null);
    }

    public function onFailed(NotificationFailed $event): void
    {
        $eccezione = $event->data['exception'] ?? null;
        $messaggio = $eccezione instanceof \Throwable ? $eccezione->getMessage() : null;

        $this->registra($event, 'fallita', $messaggio);
    }

    private function registra(NotificationSent|NotificationFailed $event, string $stato, ?string $errore): void
    {
        // tracciamo solo le email, non il canale "database" (campanella)
        if ($event->channel !== 'mail') {
            return;
        }

        try {
            $notifiable = $event->notifiable;

            $tipo = match (get_class($event->notification)) {
                InvitoDipendente::class => 'benvenuto_dipendente',
                InvitoUtente::class => 'benvenuto_hr',
                NuovoDocumentoDisponibile::class => 'nuovo_documento',
                default => 'altro',
            };

            $log = EmailLog::firstOrNew([
                'notifica_id' => $event->notification->id,
                'destinatario' => $notifiable->email ?? '',
            ]);

            $log->fill([
                'user_id' => $notifiable->id ?? null,
                'azienda_id' => $notifiable->azienda_id ?? null,
                'tipo' => $tipo,
                'stato' => $stato,
                'errore' => $errore,
                'inviata_il' => $stato === 'inviata' ? now() : $log->inviata_il,
            ]);
            $log->tentativi = ($log->tentativi ?? 0) + 1;
            $log->save();
        } catch (\Throwable) {
            // il logging dell'esito non deve MAI far fallire l'invio
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            NotificationSent::class => 'onSent',
            NotificationFailed::class => 'onFailed',
        ];
    }
}
