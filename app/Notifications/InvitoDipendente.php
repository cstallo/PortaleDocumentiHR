<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\Tries;

#[Tries(5)]
class InvitoDipendente extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $token,
    ) {
        $this->onQueue('notifications');
    }

    public function backoff(): array
    {
        return [5, 10, 30, 60]; // secondi tra un retry e l'altro: supera il rate limit
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], false));

        $azienda = $notifiable->azienda?->nome;

        return (new MailMessage)
            ->subject('Benvenuto sul Portale Documenti — attiva il tuo accesso')
            ->greeting("Gentile {$notifiable->name},")
            ->line(($azienda ? "{$azienda} " : 'La tua azienda ').'ha attivato un nuovo servizio digitale: il **Portale Documenti**, dove potrai consultare e scaricare in autonomia i tuoi documenti di lavoro (cedolini e buste paga), in modo sicuro e riservato.')
            ->line('È stato creato il tuo account personale. Per attivarlo, imposta la tua password cliccando qui sotto:')
            ->action('Attiva il tuo accesso', $url)
            ->line('**Trattamento dei tuoi dati personali:** una volta effettuato l\'accesso, nella tua area riservata trovi l\'informativa privacy completa (artt. 13-14 GDPR) che spiega quali dati tratta la tua azienda e come esercitare i tuoi diritti.')
            ->line('Se il link è scaduto, usa "Password dimenticata?" nella pagina di accesso per riceverne uno nuovo.')
            ->line('Per sicurezza non condividere questa email con nessuno.')
            ->salutation('IN & OUT HR');
    }
}
