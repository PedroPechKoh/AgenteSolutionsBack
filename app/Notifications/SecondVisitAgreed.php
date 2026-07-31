<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SecondVisitAgreed extends Notification
{
    use Queueable;

    protected $service;
    protected $fechaConfirmada;
    protected $accion; // 'aceptar' o 'reprogramar'

    public function __construct($service, $fechaConfirmada, $accion = 'aceptar')
    {
        $this->service = $service;
        $this->fechaConfirmada = $fechaConfirmada;
        $this->accion = $accion;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $idStr = is_object($this->service) ? ($this->service->id ?? 'N/A') : $this->service;
        $propId = is_object($this->service) ? ($this->service->property_id ?? null) : null;
        $textoAccion = $this->accion === 'reprogramar' ? 'propuso una nueva fecha' : 'confirmó la fecha propuesta';

        return [
            'service_id' => $idStr,
            'property_id' => $propId,
            'alert_type' => 'second_visit_agreed',
            'title' => '¡Acuerdo de Segunda Visita!',
            'message' => "El cliente y el técnico llegaron a un acuerdo. El cliente {$textoAccion} para la 2da visita del trabajo #{$idStr}: {$this->fechaConfirmada}.",
            'url' => $propId ? "/propiedad/{$propId}/tablero" : "/trabajos"
        ];
    }
}
