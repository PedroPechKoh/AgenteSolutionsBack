<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class TechnicianMissedVisitNotification extends Notification
{
    use Queueable;

    protected $service;
    protected $technicianName;

    public function __construct($service, $technicianName)
    {
        $this->service = $service;
        $this->technicianName = $technicianName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $propertyName = $this->service->property_name ?? 'Propiedad desconocida';
        $fecha = date('d/m/Y', strtotime($this->service->scheduled_start));
        
        return [
            'title' => '⚠️ VISITA NO REALIZADA',
            'message' => "El técnico {$this->technicianName} no se presentó a la visita programada para el {$fecha} en {$propertyName}.",
            'service_id' => $this->service->id,
            'type' => 'missed_visit',
            'icon' => 'AlertTriangle'
        ];
    }
}
