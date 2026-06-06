<?php

namespace App\Notifications;

use App\Models\CartellaMese;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NuovoDocumentoDisponibile extends Notification implements ShouldQueue
{
    use Queueable;

    private Collection $documenti;
    private CartellaMese $cartellaMese;

    public function __construct(array $documenti, CartellaMese $cartellaMese)
    {
        $this->documenti    = collect($documenti);
        $this->cartellaMese = $cartellaMese;
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $meseAnno = ucfirst($this->cartellaMese->meseLabel).' '.$this->cartellaMese->anno;
        $azienda  = $this->cartellaMese->azienda->nome;

        $mail = (new MailMessage)
            ->subject("Nuovi documenti disponibili — {$meseAnno} | {$azienda}")
            ->greeting("Gentile {$notifiable->name},")
            ->line("Sono disponibili **{$this->documenti->count()}** documento/i per **{$azienda}** — {$meseAnno}.")
            ->line('**Clicca sul nome del file per scaricarlo:**');

        foreach ($this->documenti as $doc) {
            $url = route('documenti.download', $doc->id);
            $mail->line("[⬇ {$doc->nome_file}]({$url})");
        }

        return $mail
            ->action("Vai all'area documenti", url('/documenti'))
            ->line('*I link sono personali e sicuri — non condividerli.*')
            ->salutation('IN & OUT HR');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'azienda_id'    => $this->cartellaMese->azienda_id,
            'azienda_nome'  => $this->cartellaMese->azienda->nome,
            'mese'          => $this->cartellaMese->label,
            'anno'          => $this->cartellaMese->anno,
            'tot_documenti' => $this->documenti->count(),
            'nomi_file'     => $this->documenti->pluck('nome_file')->toArray(),
        ];
    }
}
