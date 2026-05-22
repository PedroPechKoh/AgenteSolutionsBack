<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Quote;

class QuotePaymentReceived extends Notification
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

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $folio = str_pad($this->quote->id, 4, '0', STR_PAD_LEFT);
        
        return [
            'quote_id' => $this->quote->id,
            'title' => 'Comprobante de Pago Recibido',
            'message' => "El cliente ha subido el comprobante de pago para la cotización #$folio.",
            'type' => 'payment_received'
        ];
    }
}
