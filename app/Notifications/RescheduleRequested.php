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
        return ['database']; 
    }

    public function toArray($notifiable)
    {
        $this->service->load('property.client');
        $clienteNombre = ($this->service->property && $this->service->property->client) ? $this->service->property->client->name : 'El cliente';

        return [
            'service_id' => $this->service->id,
            'alert_type' => 'reschedule_request',
            'title' => 'Solicitud de Reprogramación',
            'message' => "{$clienteNombre} ha solicitado cambiar la visita del servicio #{$this->service->id} para el: {$this->suggestedDate}.",
            'url' => "/detalle-reporte/{$this->service->id}"
        ];
    }
}
