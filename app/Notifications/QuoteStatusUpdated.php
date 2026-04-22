<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuoteStatusUpdated extends Notification
{
    use Queueable;

    protected $quote;
    protected $clientName;

    public function __construct($quote, $clientName)
    {
        $this->quote = $quote;
        $this->clientName = $clientName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $isRejected = $this->quote->status === 'Rechazado';
        
        return [
            'quote_id' => $this->quote->id,
            'alert_type' => $isRejected ? 'quote_rejected' : 'quote_accepted',
            'title' => $isRejected ? 'Cotización Rechazada ❌' : 'Cotización Aceptada ✅',
            'message' => "La cotización #{$this->quote->id} del cliente {$this->clientName} ha sido " . strtolower($this->quote->status) . ".",
            'url' => "/vista-cotizaciones"
        ];
    }
}
