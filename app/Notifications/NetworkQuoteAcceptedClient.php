<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\NetworkQuote;

class NetworkQuoteAcceptedClient extends Notification
{
    use Queueable;

    public $quote;
    public $techName;
    public $workOrderTitle;

    public function __construct(NetworkQuote $quote, $techName, $workOrderTitle = 'Trabajo')
    {
        $this->quote = $quote;
        $this->techName = $techName;
        $this->workOrderTitle = $workOrderTitle;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'network_quote_accepted_client',
            'title' => 'Trabajo Asignado',
            'message' => "Has aceptado la cotización de {$this->techName} por \${$this->quote->price} para \"{$this->workOrderTitle}\".",
            'work_order_id' => $this->quote->work_order_id,
            'quote_id' => $this->quote->id,
            'url' => '/red-autonomos'
        ];
    }
}
