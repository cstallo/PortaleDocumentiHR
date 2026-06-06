<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitoUtente extends Notification
{
    use Queueable;

    public function __construct(
        private string $passwordTemporanea,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Il tuo accesso al Portale Documenti')
            ->greeting("Gentile {$notifiable->name},")
            ->line('È stato creato un account per te sul Portale Documenti HR.')
            ->line('Ecco le tue credenziali di **primo accesso**:')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Password temporanea:** {$this->passwordTemporanea}")
            ->action('Accedi al portale', route('login'))
            ->line('Al primo accesso ti verrà richiesto di **scegliere una nuova password personale**.')
            ->line('Per sicurezza non condividere queste credenziali con nessuno.')
            ->salutation('IN & OUT HR');
    }
}
