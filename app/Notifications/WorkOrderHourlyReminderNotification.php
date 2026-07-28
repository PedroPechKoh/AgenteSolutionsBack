<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkOrderHourlyReminderNotification extends Notification
{
    use Queueable;

    protected $jobItem;
    protected $isWorkOrder;

    public function __construct($jobItem, bool $isWorkOrder = true)
    {
        $this->jobItem = $jobItem;
        $this->isWorkOrder = $isWorkOrder;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $propertyName = $this->jobItem->property->property_name ?? $this->jobItem->property->address ?? 'la propiedad';
        $jobId = $this->jobItem->id;
        $compositeId = $this->isWorkOrder ? "work_order-{$jobId}" : "servicio-{$jobId}";

        return [
            'work_order_id' => $this->isWorkOrder ? $jobId : null,
            'service_id' => !$this->isWorkOrder ? $jobId : null,
            'composite_id' => $compositeId,
            'title' => '⏰ Recordatorio de Trabajo (En 1 hora)',
            'message' => "Recuerda asistir a tu siguiente trabajo en '{$propertyName}'. Inicia aproximadamente en 1 hora.",
            'alert_type' => 'hourly_reminder',
            'url' => "/trabajo-propiedad/{$compositeId}"
        ];
    }
}
