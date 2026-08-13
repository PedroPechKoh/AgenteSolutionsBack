<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NetworkQuoteRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public $quote;

    public function __construct($quote)
    {
        $this->quote = $quote;
    }

    public function via($notifiable)
    {
        return ['database']; 
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Cotización Rechazada',
            'message' => 'Tu cotización fue rechazada. Por favor, edita tu propuesta y envíala de nuevo.',
            'type' => 'network_quote_rejected',
            'quote_id' => $this->quote->id,
            'work_order_id' => $this->quote->work_order_id
        ];
    }
}
