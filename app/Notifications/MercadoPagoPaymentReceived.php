<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Quote;

class MercadoPagoPaymentReceived extends Notification
{
    use Queueable;

    protected $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $folio = str_pad($this->quote->folio ?? $this->quote->id, 4, '0', STR_PAD_LEFT);
        
        return [
            'quote_id' => $this->quote->id,
            'title' => 'Pago por MercadoPago Exitoso',
            'message' => "La cotización #$folio ha sido pagada exitosamente a través de MercadoPago.",
            'type' => 'mercadopago_payment_success'
        ];
    }
}
