<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VisitRescheduled extends Notification
{
    use Queueable;

    protected $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function via($notifiable)
    {
        return ['database']; // Guardar en la BD
    }

    public function toArray($notifiable)
    {
        // Formateamos la fecha para que se vea bonita
        $fechaBonita = date('d/m/Y h:i A', strtotime($this->service->scheduled_start));

        return [
            'service_id' => $this->service->id,
            'alert_type' => 'visit_rescheduled',
            'title' => '¡Nueva fecha de visita!',
            'message' => "El administrador ha programado tu levantamiento #{$this->service->id} para el: {$fechaBonita}.",
            'url' => "/levantamientos" // Redirige al cliente a su tabla
        ];
    }
}