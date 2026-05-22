<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Quote;

class QuotePaymentValidated extends Notification
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
        $folio = str_pad($this->quote->id, 4, '0', STR_PAD_LEFT);
        
        return [
            'quote_id' => $this->quote->id,
            'title' => 'Pago Validado ✅',
            'message' => "Tu pago para la cotización #$folio ha sido validado correctamente.",
            'type' => 'payment_validated'
        ];
    }
}
