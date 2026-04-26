<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VisitConfirmed extends Notification
{
    use Queueable;

    protected $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function via($notifiable)
    {
        return ['database']; // Guardar en la base de datos
    }

    public function toArray($notifiable)
    {
        $this->service->load('property.client');
        $clienteNombre = ($this->service->property && $this->service->property->client) ? $this->service->property->client->name : 'El cliente';

        return [
            'service_id' => $this->service->id,
            'alert_type' => 'visit_confirmed',
            'title' => '¡Visita Confirmada!',
            'message' => "{$clienteNombre} ha aceptado la fecha programada para el levantamiento #{$this->service->id}.",
            'url' => "/levantamientos"
        ];
    }
}