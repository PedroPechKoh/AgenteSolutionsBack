<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\WorkOrder;

class WorkOrderAssigned extends Notification
{
    use Queueable;

    protected $workOrder;

    public function __construct(WorkOrder $workOrder)
    {
        $this->workOrder = $workOrder;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'title' => 'Nueva Orden de Trabajo Asignada',
            'message' => 'Se te ha asignado la orden de trabajo #' . $this->workOrder->id . ' en ' . ($this->workOrder->property->property_name ?? $this->workOrder->property->address),
            'alert_type' => 'work_order_assigned',
        ];
    }
}
