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
        return [
            'service_id' => $this->service->id,
            'alert_type' => 'visit_confirmed',
            'title' => '¡Visita Confirmada!',
            'message' => "El cliente ha aceptado la fecha programada para el levantamiento #{$this->service->id}.",
            'url' => "/levantamientos" // Para que el admin vaya a ver sus levantamientos
        ];
    }
}