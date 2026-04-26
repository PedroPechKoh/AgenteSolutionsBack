<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewServiceRequested extends Notification
{
    use Queueable;

    protected $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $this->service->load('property.client');
        $clienteNombre = ($this->service->property && $this->service->property->client) ? $this->service->property->client->name : 'Un cliente';

        return [
            'service_id' => $this->service->id,
            'alert_type' => 'new_service_requested',
            'title' => '¡Nueva Solicitud de Levantamiento!',
            'message' => "{$clienteNombre} ha solicitado un nuevo levantamiento (ID: #{$this->service->id}) y está esperando que le asignes un técnico.",
            'url' => "/levantamientos" 
        ];
    }
}