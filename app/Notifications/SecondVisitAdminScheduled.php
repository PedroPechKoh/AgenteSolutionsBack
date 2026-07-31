<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SecondVisitAdminScheduled extends Notification
{
    use Queueable;

    protected $service;
    protected $fechaProgramada;
    protected $observaciones;

    public function __construct($service, $fechaProgramada, $observaciones = null)
    {
        $this->service = $service;
        $this->fechaProgramada = $fechaProgramada;
        $this->observaciones = $observaciones;
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
            'alert_type' => 'second_visit_admin_scheduled',
            'title' => 'Segunda Visita Programada por Administrador',
            'message' => "El Administrador ha programado la 2da visita para el trabajo #{$idStr} el: {$this->fechaProgramada}." . ($this->observaciones ? " NOTA: {$this->observaciones}" : ''),
            'url' => $propId ? "/propiedad/{$propId}/tablero" : "/trabajos"
        ];
    }
}
