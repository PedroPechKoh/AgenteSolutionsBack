<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuoteMessageNotification extends Notification
{
    use Queueable;

    protected $quote;
    protected $senderName;

    public function __construct($quote, $senderName)
    {
        $this->quote = $quote;
        $this->senderName = $senderName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'quote_id' => $this->quote->id,
            'alert_type' => 'new_quote_message',
            'title' => 'Nuevo Mensaje 💬',
            'message' => "{$this->senderName} ha enviado un mensaje en la cotización #{$this->quote->id}.",
            'url' => "/vista-cotizaciones"
        ];
    }
}
