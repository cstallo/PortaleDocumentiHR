<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitoDipendente extends Notification
{
    use Queueable;

    public function __construct(
        private string $token,
    ) {}

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

        return (new MailMessage)
            ->subject('Benvenuto sul Portale Documenti — imposta la tua password')
            ->greeting("Gentile {$notifiable->name},")
            ->line('È stato creato il tuo account sul Portale Documenti.')
            ->line('Per attivarlo, imposta la tua password personale cliccando qui sotto:')
            ->action('Imposta la tua password', $url)
            ->line('Se il link è scaduto, usa "Password dimenticata?" nella pagina di accesso per riceverne uno nuovo.')
            ->line('Per sicurezza non condividere questa email con nessuno.')
            ->salutation('IN & OUT HR');
    }
}
