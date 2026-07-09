<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TechnicianQuoteUpdated extends Notification
{
    use Queueable;

    protected $quote;

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
            'quote_id' => $this->quote->id,
            'message' => 'Un técnico ha editado/actualizado una cotización para revisión.',
            'amount' => $this->quote->estimated_amount,
            'type' => 'quote_updated',
        ];
    }
}
