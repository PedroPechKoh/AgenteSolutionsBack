<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CashPaymentRequested extends Notification
{
    use Queueable;

    protected $quote;
    protected $clientName;
    protected $amountType; // 'advance' o 'full'
    protected $timing;     // 'immediate' o 'on_completion'

    public function __construct($quote, $clientName, $amountType, $timing)
    {
        $this->quote = $quote;
        $this->clientName = $clientName;
        $this->amountType = $amountType;
        $this->timing = $timing;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $amountLabel = $this->amountType === 'advance' ? 'Anticipo (60%)' : 'Total (100%)';
        $timingLabel = $this->timing === 'immediate' ? 'AHORA' : 'AL FINALIZAR EL TRABAJO';

        return [
            'quote_id'   => $this->quote->id,
            'alert_type' => 'cash_payment_requested',
            'title'      => "💵 Solicitud de Pago en Efectivo",
            'message'    => "{$this->clientName} solicita pagar el {$amountLabel} en efectivo {$timingLabel} — Cotización #{$this->quote->id}",
            'url'        => '/vista-cotizaciones',
        ];
    }
}
