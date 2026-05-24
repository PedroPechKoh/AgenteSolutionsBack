<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\WorkOrder;

class WorkOrderCancelledNotification extends Notification
{
    use Queueable;

    protected $workOrder;
    protected $recipientType; // 'client' or 'technician'

    public function __construct(WorkOrder $workOrder, string $recipientType)
    {
        $this->workOrder = $workOrder;
        $this->recipientType = $recipientType;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $propertyName = $this->workOrder->property 
            ? ($this->workOrder->property->property_name ?: $this->workOrder->property->address) 
            : 'tu propiedad';

        if ($this->recipientType === 'technician') {
            return [
                'work_order_id' => $this->workOrder->id,
                'property_id' => $this->workOrder->property_id,
                'title' => 'Servicio Cancelado',
                'message' => "El servicio #{$this->workOrder->id} en la propiedad {$propertyName} al que estabas asignado ha sido cancelado.",
                'alert_type' => 'work_order_cancelled_tech',
                'url' => '/trabajos-tecnico'
            ];
        }

        return [
            'work_order_id' => $this->workOrder->id,
            'property_id' => $this->workOrder->property_id,
            'title' => 'Servicio Cancelado',
            'message' => "Tu servicio solicitado #{$this->workOrder->id} en la propiedad {$propertyName} ha sido cancelado.",
            'alert_type' => 'work_order_cancelled_client',
            'url' => "/propiedad/{$this->workOrder->property_id}/tablero"
        ];
    }
}
