<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewQuoteAvailable extends Notification
{
    use Queueable;

    protected $quote;

    public function __construct($quote)
    {
        $this->quote = $quote;
    }

    public function via($notifiable)
    {
        return ['database']; // Guardar en la base de datos
    }

    public function toArray($notifiable)
    {
        return [
            'quote_id' => $this->quote->id,
            'alert_type' => 'new_quote_available',
            'title' => '¡Cotización Disponible!',
            'message' => "El administrador ha generado la cotización #{$this->quote->id} para tu revisión.",
            'url' => "/vista-cotizaciones"
        ];
    }
}
