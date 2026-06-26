<?php

namespace App\Notifications;

use App\Models\BachecaMessaggio;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NuovoMessaggioBacheca extends Notification
{
    use Queueable;

    public function __construct(
        private BachecaMessaggio $messaggio,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estratto = Str::of(strip_tags($this->messaggio->corpo))
            ->squish()        // normalizza spazi/newline lasciati da strip_tags
            ->limit(300);     // tronca a ~300 caratteri con "..."

        return (new MailMessage)
            ->subject("Nuovo messaggio in bacheca: {$this->messaggio->titolo}")
            ->greeting("Gentile {$notifiable->name},")
            ->line('Hai ricevuto una nuova comunicazione in bacheca:')
            ->line("**{$this->messaggio->titolo}**")
            ->line($estratto)
            ->action('Vai alla bacheca', route('documenti.index'))
            ->line('Accedi al portale per leggere il messaggio completo.')
            ->salutation('IN & OUT HR');
    }
}
