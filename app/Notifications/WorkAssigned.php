<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Service;

class WorkAssigned extends Notification
{
    use Queueable;

    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'service_id' => $this->service->id,
            'title' => 'Nuevo Trabajo Asignado',
            'message' => 'Se te ha asignado el servicio #' . $this->service->id . ' con checklist.',
            'alert_type' => 'work_assigned',
        ];
    }
}
