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
        $isReprogramar = ($this->accion === 'reprogramar');

        $alertType = $isReprogramar ? 'second_visit_reprogrammed' : 'second_visit_agreed';
        $title = $isReprogramar ? '📅 Propuesta de Nueva Fecha para 2da Visita' : '✅ ¡2da Visita Programada y Aceptada!';
        $message = $isReprogramar 
            ? "Se ha propuesto una nueva fecha ({$this->fechaConfirmada}) para la 2da visita del trabajo #{$idStr}. Revisa y confirma si la aceptas."
            : "La fecha para la 2da visita del trabajo #{$idStr} ha sido aceptada para el: {$this->fechaConfirmada}.";

        return [
            'service_id' => $idStr,
            'work_order_id' => $idStr,
            'property_id' => $propId,
            'alert_type' => $alertType,
            'title' => $title,
            'message' => $message,
            'url' => $propId ? "/propiedad/{$propId}/tablero" : "/trabajos"
        ];
    }
}
