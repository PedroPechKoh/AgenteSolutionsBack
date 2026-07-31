<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SecondVisitRequested extends Notification
{
    use Queueable;

    protected $service;
    protected $fechaPropuesta;
    protected $motivo;
    protected $solicitanteNombre;

    public function __construct($service, $fechaPropuesta, $motivo = null, $solicitanteNombre = 'Técnico')
    {
        $this->service = $service;
        $this->fechaPropuesta = $fechaPropuesta;
        $this->motivo = $motivo;
        $this->solicitanteNombre = $solicitanteNombre;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $idStr = is_object($this->service) ? ($this->service->id ?? 'N/A') : $this->service;
        $propId = is_object($this->service) ? ($this->service->property_id ?? null) : null;

        return [
            'service_id' => $idStr,
            'property_id' => $propId,
            'alert_type' => 'second_visit_requested',
            'title' => 'Solicitud de Segunda Visita',
            'message' => "El técnico {$this->solicitanteNombre} solicitó una 2da visita para el trabajo #{$idStr} propuesta para el: {$this->fechaPropuesta}." . ($this->motivo ? " Motivo: {$this->motivo}" : ''),
            'url' => $propId ? "/propiedad/{$propId}/tablero" : "/trabajos"
        ];
    }
}
