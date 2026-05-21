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
    protected $senderRole;

    public function __construct($quote, $senderName, $senderRole)
    {
        $this->quote = $quote;
        $this->senderName = $senderName;
        $this->senderRole = $senderRole;
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
            'message' => "Nuevo mensaje de: ({$this->senderRole}) {$this->senderName} en la cotización #{$this->quote->id}.",
            'url' => "/vista-cotizaciones"
        ];
    }
}
