<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TechnicianQuoteSubmitted extends Notification
{
    use Queueable;

    protected $quote;

    public function __construct($quote)
    {
        $this->quote = $quote;
    }

    public function via($notifiable)
    {
        return ['database']; // Opcional: ['mail', 'database']
    }

    public function toArray($notifiable)
    {
        return [
            'quote_id' => $this->quote->id,
            'message' => 'Un técnico ha enviado una nueva cotización para revisión.',
            'amount' => $this->quote->estimated_amount,
            'type' => 'quote_submitted',
        ];
    }
}
