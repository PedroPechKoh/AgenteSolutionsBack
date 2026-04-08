<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RescheduleRequested extends Notification
{
    use Queueable;

    protected $service;
    protected $suggestedDate;

    public function __construct($service, $suggestedDate)
    {
        $this->service = $service;
        $this->suggestedDate = $suggestedDate;
    }

    public function via($notifiable)
    {
        return ['database']; // Tells Laravel to save this in the DB
    }

    public function toArray($notifiable)
    {
        // These keys are in English, but the message text goes in Spanish 
        // because it will be printed directly in the React Frontend.
        return [
            'service_id' => $this->service->id,
            'alert_type' => 'reschedule_request',
            'title' => 'Solicitud de Reprogramación',
            'message' => "El cliente ha solicitado cambiar la visita del servicio #{$this->service->id} para el: {$this->suggestedDate}.",
            'url' => "/detalle-reporte/{$this->service->id}"
        ];
    }
}
