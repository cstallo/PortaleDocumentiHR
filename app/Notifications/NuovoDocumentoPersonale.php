<?php

namespace App\Notifications;

use App\Models\Documento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuovoDocumentoPersonale extends Notification
{
    use Queueable;

    public function __construct(
        private Documento $documento,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $categoria = $this->documento->tipo->getLabel();
        $url = route('documenti.download', $this->documento->id);

        return (new MailMessage)
            ->subject("Nuovo documento disponibile: {$categoria}")
            ->greeting("Gentile {$notifiable->name},")
            ->line("È disponibile un nuovo documento per te: **{$this->documento->nome_file}** ({$categoria}).")
            ->line("[⬇ Scarica {$this->documento->nome_file}]({$url})")
            ->action("Vai all'area documenti", url('/documenti'))
            ->line('*Il link è personale e sicuro — non condividerlo.*')
            ->salutation('IN & OUT HR');
    }
}
