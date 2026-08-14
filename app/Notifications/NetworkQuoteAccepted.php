<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\NetworkQuote;

class NetworkQuoteAccepted extends Notification
{
    use Queueable;

    public $quote;
    public $workOrderTitle;

    public function __construct(NetworkQuote $quote, $workOrderTitle = 'Trabajo')
    {
        $this->quote = $quote;
        $this->workOrderTitle = $workOrderTitle;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'network_quote_accepted',
            'title' => '¡Cotización Aceptada!',
            'message' => "¡Felicidades! Tu cotización de \${$this->quote->price} para \"{$this->workOrderTitle}\" fue aceptada. El trabajo ha sido asignado a ti.",
            'work_order_id' => $this->quote->work_order_id,
            'quote_id' => $this->quote->id,
            'url' => '/trabajos-tecnico'
        ];
    }
}
