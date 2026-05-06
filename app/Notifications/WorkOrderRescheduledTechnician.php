<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\WorkOrder;

class WorkOrderRescheduledTechnician extends Notification
{
    use Queueable;

    protected $workOrder;
    protected $adminName;

    /**
     * Create a new notification instance.
     */
    public function __construct(WorkOrder $workOrder, $adminName)
    {
        $this->workOrder = $workOrder;
        $this->adminName = $adminName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        $propertyName = $this->workOrder->property ? ($this->workOrder->property->property_name ?: $this->workOrder->property->address) : 'Propiedad';
        $newDate = $this->workOrder->scheduled_at ? $this->workOrder->scheduled_at->format('d/m/Y h:i A') : 'Nueva fecha';

        return [
            'work_order_id' => $this->workOrder->id,
            'alert_type' => 'work_order_rescheduled',
            'title' => '¡Cambio en tu agenda!',
            'message' => "{$this->adminName} ha reasignado el trabajo de {$propertyName} para la nueva fecha: {$newDate}.",
            'url' => "/detalle-servicio/{$this->workOrder->id}"
        ];
    }
}
