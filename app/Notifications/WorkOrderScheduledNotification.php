<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class WorkOrderScheduledNotification extends Notification
{
    use Queueable;

    protected $workOrder;
    protected $tecnicoName;
    protected $propertyName;

    public function __construct($workOrder, $tecnicoName, $propertyName)
    {
        $this->workOrder = $workOrder;
        $this->tecnicoName = $tecnicoName;
        $this->propertyName = $propertyName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $fecha = Carbon::parse($this->workOrder->scheduled_at)->format('d/m/Y H:i');
        return [
            'work_order_id' => $this->workOrder->id,
            'property_id' => $this->workOrder->property_id,
            'alert_type' => 'work_order_scheduled',
            'title' => 'Visita de Técnico Programada',
            'message' => "El técnico {$this->tecnicoName} te visitará el {$fecha} por el problema en la propiedad {$this->propertyName}.",
            'url' => "/propiedad/{$this->workOrder->property_id}/tablero"
        ];
    }
}
